<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Error;
use Core\Model\Monitoring\MonitorInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorMonitoringTest extends TestCase
{
    public function testHandleExceptionReportsCriticalErrorsToTheMonitor(): void
    {
        $exception = new RuntimeException('boom');
        $monitor = $this->createMock(MonitorInterface::class);
        $monitor->expects($this->once())
            ->method('captureException')
            ->with($exception, $this->callback(fn ($context) => $context['severity'] === Error::SEVERITY_CRITICAL));

        $error = new Error('production', false);
        $error->setMonitor($monitor);

        $error->handleException($exception, null, Error::SEVERITY_CRITICAL);
    }

    public function testHandleExceptionReportsFatalErrorsToTheMonitor(): void
    {
        $exception = new RuntimeException('boom');
        $monitor = $this->createMock(MonitorInterface::class);
        $monitor->expects($this->once())->method('captureException');

        $error = new Error('production', false);
        $error->setMonitor($monitor);

        $error->handleException($exception, null, Error::SEVERITY_FATAL);
    }

    public function testHandleExceptionDoesNotReportLowerSeverityErrorsToTheMonitor(): void
    {
        $exception = new RuntimeException('boom');
        $monitor = $this->createMock(MonitorInterface::class);
        $monitor->expects($this->never())->method('captureException');

        $error = new Error('production', false);
        $error->setMonitor($monitor);

        $error->handleException($exception, null, Error::SEVERITY_WARNING);
    }

    public function testSetMonitorReturnsSelfForFluentChaining(): void
    {
        $error = new Error('production', false);
        $monitor = $this->createMock(MonitorInterface::class);

        $this->assertSame($error, $error->setMonitor($monitor));
    }
}
