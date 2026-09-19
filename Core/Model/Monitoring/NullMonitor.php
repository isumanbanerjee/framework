<?php

/**
 * No-Op Monitor
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
 * NullMonitor
 *
 * The default {@see MonitorInterface} implementation: discards everything.
 * Used so the framework always has a monitor to call without requiring an
 * application to configure one.
 *
 * @category  Monitoring
 * @package   Core\Model\Monitoring
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
final class NullMonitor implements MonitorInterface
{
    public function captureException(Throwable $exception, array $context = []): void
    {
    }

    public function recordMetric(string $name, float $value, array $tags = []): void
    {
    }
}
