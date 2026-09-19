<?php

/**
 * In-Process Prometheus Metrics Monitor
 *
 * PHP version 8.1
 *
 * @category  Monitoring
 * @package   Core\Model\Monitoring
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model\Monitoring;

use Throwable;

/**
 * PrometheusMonitor
 *
 * Accumulates exceptions and metrics in memory and renders them in
 * Prometheus text exposition format, with no external client library. Ship
 * it behind a route (see docs/advanced/monitoring.md) for a scrapeable
 * `/metrics` endpoint, or fan it out alongside a real APM via
 * {@see CompositeMonitor}.
 *
 * Since this holds state only for the life of one PHP process/request, it
 * suits short-lived requests scraped per-request or long-running workers
 * (queue consumers) with a periodic scrape — not classic PHP-FPM request
 * isolation, where state does not survive between requests.
 *
 * @category  Monitoring
 * @package   Core\Model\Monitoring
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
final class PrometheusMonitor implements MonitorInterface
{
    /**
     * Exception counts keyed by exception class name.
     *
     * @var array<string,int>
     */
    private array $errorCounts = [];

    /**
     * Recorded metric samples keyed by "name|serialized-tags".
     *
     * @var array<string,array{name:string,tags:array<string,string>,value:float}>
     */
    private array $samples = [];

    public function captureException(Throwable $exception, array $context = []): void
    {
        $class = get_class($exception);
        $this->errorCounts[$class] = ($this->errorCounts[$class] ?? 0) + 1;
    }

    public function recordMetric(string $name, float $value, array $tags = []): void
    {
        ksort($tags);
        $key = $name . '|' . http_build_query($tags);

        $this->samples[$key] = ['name' => $name, 'tags' => $tags, 'value' => $value];
    }

    /**
     * Clear all accumulated counts and samples.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->errorCounts = [];
        $this->samples = [];
    }

    /**
     * Render everything collected so far in Prometheus text exposition
     * format (suitable for a `/metrics` endpoint).
     *
     * @return string
     */
    public function render(): string
    {
        $lines = [];

        if (!empty($this->errorCounts)) {
            $lines[] = '# TYPE app_errors_total counter';
            foreach ($this->errorCounts as $class => $count) {
                $lines[] = sprintf('app_errors_total{exception="%s"} %d', $this->escapeLabelValue($class), $count);
            }
        }

        $byName = [];
        foreach ($this->samples as $sample) {
            $byName[$sample['name']][] = $sample;
        }

        foreach ($byName as $name => $samples) {
            $metric = $this->sanitizeMetricName($name);
            $lines[] = "# TYPE {$metric} gauge";
            foreach ($samples as $sample) {
                $lines[] = $metric . $this->formatLabels($sample['tags']) . ' ' . $sample['value'];
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string,string> $tags
     *
     * @return string
     */
    private function formatLabels(array $tags): string
    {
        if (empty($tags)) {
            return '';
        }

        $pairs = [];
        foreach ($tags as $key => $value) {
            $pairs[] = $key . '="' . $this->escapeLabelValue($value) . '"';
        }

        return '{' . implode(',', $pairs) . '}';
    }

    private function escapeLabelValue(string $value): string
    {
        return addcslashes($value, "\\\"\n");
    }

    private function sanitizeMetricName(string $name): string
    {
        return (string) preg_replace('/[^a-zA-Z0-9_:]/', '_', $name);
    }
}
