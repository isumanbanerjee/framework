# Configuration

OmnioPHP reads configuration from `.env`-style files in `Configuration/`, parsed via `EnvFileParser` and exposed through `Core\Model\App`.

## Files

| File | Purpose |
|---|---|
| `Configuration/config.env` | Application settings (database, cache, app name/env/debug) |
| `Configuration/error.env` | Human-readable error messages keyed by error code |
| `Configuration/config.env.example` | Placeholder template — copy to `config.env` |
| `Configuration/config.env.testing.example` | SQLite-based config for the test suite |
| `Configuration/config.env.development.example` | Local development template — verbose logging, debug on |
| `Configuration/config.env.staging.example` | Staging template — production-like infra, `info`-level logging |
| `Configuration/config.env.production.example` | Production template — debug off, locked-down CORS, `warning`-level logging |

## Environment-specific config files

`App::config()` normally reads `Configuration/config.env`. If the `APP_ENV` **process** environment variable is set (e.g. exported by your web server, container, or process manager — distinct from the `APP_ENV` *key* stored inside `config.env`) and a matching `Configuration/config.env.{APP_ENV}` file exists, that file is loaded instead:

```bash
APP_ENV=staging php -S localhost:8000   # loads Configuration/config.env.staging if present
```

Falls back to plain `config.env` when `APP_ENV` is unset or no matching file exists, so this is fully opt-in — existing single-`config.env` deployments are unaffected. To adopt it, copy the relevant example (e.g. `config.env.production.example` → `config.env.production`) and set the real `APP_ENV` process variable in that environment's deploy configuration. As with `config.env` itself, never commit the real per-environment files — only the `.example` templates belong in version control (see `.gitignore`).

Resolution order is the same regardless of environment: `config_compiled.php` (if present) always wins over any `.env` file — see [Config caching](#config-caching) below.

## Reading configuration

```php
use Core\Model\App;

$dbHost = App::config('DB_HOST', 'localhost');

if (App::has('API_KEY')) {
    $apiKey = App::config('API_KEY');
}

$all = App::all();
```

Or via the global helpers (`Core/helpers.php`, autoloaded through Composer's `files` directive):

```php
$dbHost = config('DB_HOST', 'localhost');
$apiKey = env('API_KEY'); // reads $_ENV / $_SERVER directly
```

## Config caching

Parsing an `.env` file on every request has overhead. Compile it to a plain PHP array ahead of time:

```bash
php console config:cache
```

This writes `Configuration/config_compiled.php` (already excluded via `.gitignore`), which `App` loads instead of re-parsing the `.env` file when present. Re-run the command whenever `config.env` changes; there is no cache-busting on file mtime by design — see [Performance Tuning](../performance.md).

## Key settings

```env
# Database
DB_TYPE=mysql
DB_HOST=localhost
DB_NAME=omniophp
DB_USERNAME=root
DB_PASSWORD=secret

# Application
APP_NAME=OmnioPHP
APP_ENV=production
APP_DEBUG=false

# Cache
CACHE_DRIVER=redis
CACHE_REDIS_HOST=127.0.0.1
CACHE_DEFAULT_TTL=3600

# CORS (read by Core\Model\Middleware::CorsMiddleware)
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=GET, POST, PUT, DELETE, OPTIONS
CORS_ALLOWED_HEADERS=Content-Type, Authorization
```

Never commit `Configuration/config.env` — it's already covered by `.gitignore`. Only the `.example` templates belong in version control.
