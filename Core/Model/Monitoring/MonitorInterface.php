<?php

/**
 * Monitoring Hook Contract
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
 * MonitorInterface
 *
 * A minimal contract external monitoring/APM services (Sentry, New Relic,
 * Prometheus, or any in-house system) implement to receive exceptions and
 * metrics from the framework. The framework ships no vendor SDKs — wire a
 * real service by implementing this interface (directly, or by wrapping the
 * vendor's client inside a {@see CallbackMonitor}) and registering it with
 * {@see \Core\Model\Error::setMonitor()}.
 *
 * @category  Monitoring
 * @package   Core\Model\Monitoring
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
interface MonitorInterface
{
    /**
     * Report a captured exception/error to the monitoring backend.
     *
     * @param Throwable            $exception The captured throwable.
     * @param array<string,mixed>  $context   Additional context (severity, request info, ...).
     *
     * @return void
     */
    public function captureException(Throwable $exception, array $context = []): void;

    /**
     * Record a numeric metric (counter, gauge, timing, ...).
     *
     * @param string               $name  Metric name (e.g. "http.request.duration_ms").
     * @param float                $value Metric value.
     * @param array<string,string> $tags  Optional key/value labels.
     *
     * @return void
     */
    public function recordMetric(string $name, float $value, array $tags = []): void;
}
