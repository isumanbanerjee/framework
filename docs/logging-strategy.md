# Logging Strategy

`Core\Model\Logger` writes timestamped, leveled log lines with JSON context. It exposes five level methods — `logDebug()`, `logInfo()`, `logWarning()`, `logError()`, and `logCritical()` — covering the practical dev/staging/production split described below.

```php
use Core\Model\Logger;

$logger = new Logger();

$logger->logDebug('Cache miss', ['key' => 'users:active']);
$logger->logInfo('User registered', ['user_id' => $user->id]);
$logger->logWarning('Slow query', ['duration_ms' => 850]);
$logger->logError('Payment failed', ['order_id' => $order->id, 'reason' => $e->getMessage()]);
$logger->logCritical('Database unreachable', ['host' => $host]);
```

Output format: `[YYYY-MM-DD HH:MM:SS] [LEVEL] Message {"context":"data"}`.

## Level filtering

Severity ranks lowest to highest as `DEBUG < INFO < WARNING < ERROR < CRITICAL`. The `LOG_LEVEL` configuration setting (`Configuration/config.env`) sets the minimum level that actually gets written — entries below it are silently discarded, including skipping the rotation check and file write entirely:

```env
LOG_LEVEL=info
```

```php
$logger = new Logger();
$logger->logDebug('never written when LOG_LEVEL=info');
$logger->logInfo('written');
```

Override it at runtime (e.g. in tests, or to temporarily raise verbosity) with `setMinLevel()`:

```php
$logger->setMinLevel('debug'); // case-insensitive
```

With no `LOG_LEVEL` set, the default is `DEBUG` — every level is logged, matching the logger's pre-filtering behavior.

## Recommended levels per environment

| Environment | `LOG_LEVEL` | What gets written | Rationale |
|---|---|---|---|
| **Development** (`APP_ENV=development`, `APP_DEBUG=true`) | `debug` | Everything, including request-level breadcrumbs | Maximum visibility while iterating locally; noise is cheap |
| **Staging** | `info` | Lifecycle events (auth, payments, job runs) plus all warnings/errors/critical | Enough signal to reproduce staging-only bugs without drowning in output |
| **Production** (`APP_ENV=production`, `APP_DEBUG=false`) | `warning` or `error` | Failures/exceptions and above; routine info-level noise dropped | Keeps log volume and storage cost proportional to what's actionable |

## Practical guidance

- **Never log secrets.** Don't pass raw passwords, tokens, or full credit card numbers into `$context` — log an identifier instead (`user_id`, last 4 digits, etc).
- **Attach correlation data.** Include `request_id`/`user_id` in `$context` wherever available so related log lines can be grouped.
- **Errors always log, regardless of environment.** `Core\Model\Error::registerGlobalHandlers()` should route uncaught exceptions through `logError()` in every environment, not just development — see [Error.php](../Core/Model/Error.php).
- **Respect `APP_DEBUG` for response bodies, not for logging.** Whether a stack trace is shown to the *client* (via `APP_DEBUG`) is a separate decision from whether it's *logged* — always log the full exception server-side even when the HTTP response returns a generic error page.

## Log rotation and storage

The logger writes to a configurable file path with a configurable size threshold (`$logFile`, `$maxFileSize` on the `Logger` constructor — defaults to `framework_error.log` capped at 5MB) and rotates on its own once that threshold is hit. For multi-instance deployments, still plan for centralized aggregation (see below) since size-based rotation alone won't merge logs across hosts.

## External aggregation

For centralized log search across multiple instances, ship the log file with a standard collector (Filebeat, Fluent Bit, Vector, etc.) into your log platform of choice. The framework does not bundle a specific integration, keeping this an infrastructure decision rather than a hard dependency — see [Monitoring & APM Integration](advanced/monitoring.md) for the hooks it does provide.
