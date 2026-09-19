<?php

/**
 * Callback-Based Monitor Adapter
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
 * CallbackMonitor
 *
 * Adapts arbitrary closures to {@see MonitorInterface} so a third-party APM
 * client (the Sentry PHP SDK, New Relic's agent functions, a custom HTTP
 * client, ...) can be wired in without the framework depending on it.
 *
 * Example (Sentry):
 * ```php
 * $monitor = new CallbackMonitor(
 *     onException: fn (Throwable $e, array $context) => \Sentry\captureException($e),
 *     onMetric: fn (string $name, float $value, array $tags) =>
 *         \Sentry\metrics()->gauge($name, $value, unit: null, tags: $tags),
 * );
 * (new Error())->setMonitor($monitor);
 * ```
 *
 * Example (New Relic agent extension):
 * ```php
 * $monitor = new CallbackMonitor(
 *     onException: fn (Throwable $e) => newrelic_notice_error($e->getMessage(), $e),
 *     onMetric: fn (string $name, float $value) => newrelic_custom_metric($name, $value),
 * );
 * ```
 *
 * @category  Monitoring
 * @package   Core\Model\Monitoring
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
final class CallbackMonitor implements MonitorInterface
{
    /**
     * @var (callable(Throwable, array<string,mixed>): void)|null
     */
    private $onException;

    /**
     * @var (callable(string, float, array<string,string>): void)|null
     */
    private $onMetric;

    /**
     * @param callable(Throwable, array<string,mixed>): void|null $onException Called from captureException().
     * @param callable(string, float, array<string,string>): void|null $onMetric Called from recordMetric().
     */
    public function __construct(?callable $onException = null, ?callable $onMetric = null)
    {
        $this->onException = $onException;
        $this->onMetric = $onMetric;
    }

    public function captureException(Throwable $exception, array $context = []): void
    {
        if ($this->onException !== null) {
            ($this->onException)($exception, $context);
        }
    }

    public function recordMetric(string $name, float $value, array $tags = []): void
    {
        if ($this->onMetric !== null) {
            ($this->onMetric)($name, $value, $tags);
        }
    }
}
