<?php

/**
 * Fan-Out Monitor
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
 * CompositeMonitor
 *
 * Reports to several monitors at once, e.g. Sentry for error tracking and
 * Prometheus for metrics simultaneously.
 *
 * Example:
 * ```php
 * (new Error())->setMonitor(new CompositeMonitor([$sentryAdapter, $prometheusMonitor]));
 * ```
 *
 * @category  Monitoring
 * @package   Core\Model\Monitoring
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
final class CompositeMonitor implements MonitorInterface
{
    /**
     * @var array<int,MonitorInterface>
     */
    private array $monitors;

    /**
     * @param array<int,MonitorInterface> $monitors Monitors to fan out to, in order.
     */
    public function __construct(array $monitors = [])
    {
        $this->monitors = $monitors;
    }

    public function captureException(Throwable $exception, array $context = []): void
    {
        foreach ($this->monitors as $monitor) {
            $monitor->captureException($exception, $context);
        }
    }

    public function recordMetric(string $name, float $value, array $tags = []): void
    {
        foreach ($this->monitors as $monitor) {
            $monitor->recordMetric($name, $value, $tags);
        }
    }
}
