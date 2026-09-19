# Logging Strategy

`Core\Model\Logger` writes timestamped, leveled log lines with JSON context. It currently exposes two methods — `logInfo()` and `logError()` — which is enough to cover the practical dev/staging/production split described below.

```php
use Core\Model\Logger;

$logger = new Logger();

$logger->logInfo('User registered', ['user_id' => $user->id]);
$logger->logError('Payment failed', ['order_id' => $order->id, 'reason' => $e->getMessage()]);
```

Output format: `[YYYY-MM-DD HH:MM:SS] [LEVEL] Message {"context":"data"}`.

## Recommended levels per environment

| Environment | What to log | Rationale |
|---|---|---|
| **Development** (`APP_ENV=development`, `APP_DEBUG=true`) | Both `logInfo()` and `logError()` liberally, including request-level breadcrumbs | Maximum visibility while iterating locally; noise is cheap |
| **Staging** | `logInfo()` for key lifecycle events (auth, payments, job runs) + all `logError()` calls | Enough signal to reproduce staging-only bugs without drowning in output |
| **Production** (`APP_ENV=production`, `APP_DEBUG=false`) | `logError()` for failures/exceptions; `logInfo()` only for events with audit or business value (e.g. "order placed") | Keeps log volume and storage cost proportional to what's actionable |

## Practical guidance

- **Never log secrets.** Don't pass raw passwords, tokens, or full credit card numbers into `$context` — log an identifier instead (`user_id`, last 4 digits, etc).
- **Attach correlation data.** Include `request_id`/`user_id` in `$context` wherever available so related log lines can be grouped.
- **Errors always log, regardless of environment.** `Core\Model\Error::registerGlobalHandlers()` should route uncaught exceptions through `logError()` in every environment, not just development — see [Error.php](../Core/Model/Error.php).
- **Respect `APP_DEBUG` for response bodies, not for logging.** Whether a stack trace is shown to the *client* (via `APP_DEBUG`) is a separate decision from whether it's *logged* — always log the full exception server-side even when the HTTP response returns a generic error page.

## Log rotation and storage

The logger writes to a configurable file path with a configurable size threshold (`$logFile`, `$maxFileSize` on the `Logger` constructor — defaults to `framework_error.log` capped at 5MB) and rotates on its own once that threshold is hit. For multi-instance deployments, still plan for centralized aggregation (see below) since size-based rotation alone won't merge logs across hosts.

## External aggregation

For centralized log search across multiple instances, ship the log file with a standard collector (Filebeat, Fluent Bit, Vector, etc.) into your log platform of choice. The framework does not bundle a specific integration — see the note on monitoring in [IMPROVEMENT_SUGGESTIONS.md](../IMPROVEMENT_SUGGESTIONS.md) (§10.5) for why that's left as an infrastructure decision rather than a hard dependency.
