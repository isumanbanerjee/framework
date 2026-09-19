<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Monitoring\CallbackMonitor;
use Core\Model\Monitoring\CompositeMonitor;
use Core\Model\Monitoring\MonitorInterface;
use Core\Model\Monitoring\NullMonitor;
use Core\Model\Monitoring\PrometheusMonitor;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MonitoringTest extends TestCase
{
    public function testNullMonitorDiscardsEverything(): void
    {
        $monitor = new NullMonitor();

        $monitor->captureException(new RuntimeException('boom'));
        $monitor->recordMetric('http.requests', 1.0);

        $this->addToAssertionCount(1);
    }

    public function testCallbackMonitorInvokesExceptionCallback(): void
    {
        $captured = null;
        $context = null;

        $monitor = new CallbackMonitor(
            onException: function ($exception, $ctx) use (&$captured, &$context) {
                $captured = $exception;
                $context = $ctx;
            }
        );

        $exception = new RuntimeException('failure');
        $monitor->captureException($exception, ['request_id' => 'abc']);

        $this->assertSame($exception, $captured);
        $this->assertSame(['request_id' => 'abc'], $context);
    }

    public function testCallbackMonitorInvokesMetricCallback(): void
    {
        $seen = [];

        $monitor = new CallbackMonitor(
            onMetric: function ($name, $value, $tags) use (&$seen) {
                $seen = [$name, $value, $tags];
            }
        );

        $monitor->recordMetric('queue.depth', 42.0, ['queue' => 'default']);

        $this->assertSame(['queue.depth', 42.0, ['queue' => 'default']], $seen);
    }

    public function testCallbackMonitorToleratesMissingCallbacks(): void
    {
        $monitor = new CallbackMonitor();

        $monitor->captureException(new RuntimeException('boom'));
        $monitor->recordMetric('metric', 1.0);

        $this->addToAssertionCount(1);
    }

    public function testCompositeMonitorFansOutToAllMonitors(): void
    {
        $a = $this->createMock(MonitorInterface::class);
        $b = $this->createMock(MonitorInterface::class);

        $exception = new RuntimeException('boom');

        $a->expects($this->once())->method('captureException')->with($exception, ['k' => 'v']);
        $b->expects($this->once())->method('captureException')->with($exception, ['k' => 'v']);
        $a->expects($this->once())->method('recordMetric')->with('m', 1.5, ['t' => '1']);
        $b->expects($this->once())->method('recordMetric')->with('m', 1.5, ['t' => '1']);

        $composite = new CompositeMonitor([$a, $b]);
        $composite->captureException($exception, ['k' => 'v']);
        $composite->recordMetric('m', 1.5, ['t' => '1']);
    }

    public function testCompositeMonitorWithNoMonitorsIsANoop(): void
    {
        $composite = new CompositeMonitor();

        $composite->captureException(new RuntimeException('boom'));
        $composite->recordMetric('m', 1.0);

        $this->addToAssertionCount(1);
    }

    public function testPrometheusMonitorRendersErrorCounters(): void
    {
        $monitor = new PrometheusMonitor();

        $monitor->captureException(new RuntimeException('one'));
        $monitor->captureException(new RuntimeException('two'));
        $monitor->captureException(new InvalidArgumentException('bad'));

        $output = $monitor->render();

        $this->assertStringContainsString('# TYPE app_errors_total counter', $output);
        $this->assertStringContainsString('app_errors_total{exception="RuntimeException"} 2', $output);
        $this->assertStringContainsString('app_errors_total{exception="InvalidArgumentException"} 1', $output);
    }

    public function testPrometheusMonitorRendersGaugesWithLabels(): void
    {
        $monitor = new PrometheusMonitor();

        $monitor->recordMetric('http_request_duration_ms', 12.5, ['route' => '/users']);
        $monitor->recordMetric('http_request_duration_ms', 8.0, ['route' => '/posts']);

        $output = $monitor->render();

        $this->assertStringContainsString('# TYPE http_request_duration_ms gauge', $output);
        $this->assertStringContainsString('http_request_duration_ms{route="/users"} 12.5', $output);
        $this->assertStringContainsString('http_request_duration_ms{route="/posts"} 8', $output);
    }

    public function testPrometheusMonitorOverwritesSameSeriesOnRepeatedRecord(): void
    {
        $monitor = new PrometheusMonitor();

        $monitor->recordMetric('queue_depth', 5.0, ['queue' => 'default']);
        $monitor->recordMetric('queue_depth', 9.0, ['queue' => 'default']);

        $output = $monitor->render();

        $this->assertSame(1, substr_count($output, 'queue_depth{queue="default"}'));
        $this->assertStringContainsString('queue_depth{queue="default"} 9', $output);
    }

    public function testPrometheusMonitorSanitizesMetricNames(): void
    {
        $monitor = new PrometheusMonitor();
        $monitor->recordMetric('http.request.duration', 1.0);

        $output = $monitor->render();

        $this->assertStringContainsString('http_request_duration', $output);
        $this->assertStringNotContainsString('http.request.duration', $output);
    }

    public function testPrometheusMonitorResetClearsState(): void
    {
        $monitor = new PrometheusMonitor();
        $monitor->captureException(new RuntimeException('boom'));
        $monitor->recordMetric('m', 1.0);

        $monitor->reset();

        $this->assertSame("\n", $monitor->render());
    }
}
