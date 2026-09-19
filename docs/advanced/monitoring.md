# Monitoring & APM Integration

OmnioPHP ships no vendor SDKs — no bundled Sentry, New Relic, or Prometheus
client, and nothing that phones home. Instead `Core\Model\Monitoring`
provides a small vendor-agnostic contract, `MonitorInterface`, that
`Core\Model\Error` reports critical and fatal exceptions to. Wire in a real
service by implementing the interface, or by wrapping a vendor client in the
provided adapters.

```php
interface MonitorInterface
{
    public function captureException(Throwable $exception, array $context = []): void;
    public function recordMetric(string $name, float $value, array $tags = []): void;
}
```

## Registering a monitor

`Error` defaults to `NullMonitor` (a no-op), so nothing needs configuring
until you want it. Register a monitor with `setMonitor()`:

```php
$error = new Error();
$error->setMonitor($monitor);
```

`handleException()` calls `captureException()` on the registered monitor for
`SEVERITY_CRITICAL` and `SEVERITY_FATAL` errors.

## Adapters

### CallbackMonitor — wrap any vendor SDK

`CallbackMonitor` adapts closures to `MonitorInterface`, so a third-party
client can be wired in without the framework depending on it.

Sentry:

```php
$monitor = new CallbackMonitor(
    onException: fn (Throwable $e, array $context) => \Sentry\captureException($e),
    onMetric: fn (string $name, float $value, array $tags) =>
        \Sentry\metrics()->gauge($name, $value, unit: null, tags: $tags),
);
$error->setMonitor($monitor);
```

New Relic (agent extension):

```php
$monitor = new CallbackMonitor(
    onException: fn (Throwable $e) => newrelic_notice_error($e->getMessage(), $e),
    onMetric: fn (string $name, float $value) => newrelic_custom_metric($name, $value),
);
$error->setMonitor($monitor);
```

### PrometheusMonitor — self-contained, no vendor client needed

`PrometheusMonitor` is a concrete, fully in-process `MonitorInterface`
implementation: it accumulates exception counts and metric samples in memory
and renders them in Prometheus text exposition format. Expose it behind a
`/metrics` route:

```php
$monitor = new PrometheusMonitor();
$error->setMonitor($monitor);

$router->get('/metrics', function () use ($monitor) {
    header('Content-Type: text/plain; version=0.0.4');
    echo $monitor->render();
});
```

Record custom metrics anywhere in your application:

```php
$monitor->recordMetric('http_request_duration_ms', 42.5, ['route' => '/users']);
```

Because state only lives for the current PHP process, this suits short-lived
requests scraped per-request, or long-running workers (queue consumers) with
a periodic scrape — not classic PHP-FPM request isolation, where in-memory
state does not survive between requests. For that case, either persist
samples to shared storage (Redis, APCu) before rendering, or point Prometheus
at a push gateway from a `CallbackMonitor` instead.

### CompositeMonitor — report to several backends at once

```php
$error->setMonitor(new CompositeMonitor([
    $sentryCallbackMonitor,
    $prometheusMonitor,
]));
```

Every registered monitor receives every `captureException()` and
`recordMetric()` call, in order.

## Writing your own

Implement `MonitorInterface` directly when a `CallbackMonitor` closure isn't
enough — for example, to hold a persistent HTTP client or batch metrics
before flushing them.
