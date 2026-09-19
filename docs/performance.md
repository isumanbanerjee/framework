# Performance Guide

Practical settings for running OmnioPHP in production.

## OPcache

Enable and tune OPcache in your production `php.ini`. Because application code
does not change between deploys, timestamp validation can be disabled for a
meaningful throughput gain (remember to clear OPcache on each deploy).

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

With `opcache.validate_timestamps=0`, run `opcache_reset()` (or restart PHP-FPM)
as part of your deployment so new code is picked up.

## Configuration Caching

Compile the environment configuration into a single PHP array so it is not
re-parsed on every request:

```bash
php console config:cache
```

This writes `Configuration/config_compiled.php`, which the framework prefers
over `config.env` when present. Re-run it after changing configuration.

## Autoloader Optimization

`optimize-autoloader: true` is set in `composer.json`'s `config` block, so
every `composer install`/`composer dump-autoload` — including local dev —
generates a class-map autoloader instead of relying on PSR-4 rule scanning
at runtime.

For production builds, add `--classmap-authoritative` (already applied in
the `Dockerfile`) so the autoloader trusts the compiled class map completely
and skips the filesystem fallback lookup for classes it doesn't find —
faster, and safe here since every class the app loads is covered by the
`psr-4`/`classmap` entries in `composer.json`:

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

## Database Connection Pooling

`Core\Model\Database\Connection::make()` pools PDO instances in-process,
keyed by DSN + username. Calling it multiple times with the same
configuration — e.g. a console command that runs migrations and then seeds
data, or several services in one request each needing a database handle —
reuses the same PDO connection instead of opening a new one each time. Every
pooled connection is also opened with `PDO::ATTR_PERSISTENT`, so PHP-FPM (or
any persistent-worker SAPI) can additionally keep the OS-level connection
alive across requests where the driver supports it.

```php
use Core\Model\Database\Connection;

$pdo = Connection::make(App::all()); // opens (or reuses) the pooled connection
$again = Connection::make(App::all()); // same PDO instance, no new connection

Connection::poolSize();   // number of distinct pooled connections
Connection::resetPool();  // force reconnection (e.g. in a long-running queue worker)
```

This is process-level pooling, not a separate pooler process (PgBouncer,
ProxySQL) — it eliminates redundant connections within one PHP process/request
but doesn't cap total connections across many PHP-FPM workers. For that, run
a dedicated external pooler in front of the database.

## Query Result Caching

For read-heavy endpoints, cache expensive query results with the cache layer:

```php
$users = $cache->remember('users.active', 3600, fn () =>
    $db->fetchAll('SELECT * FROM users WHERE active = 1')
);
```

## Response Time Targets

Rough targets for a well-tuned deployment:

| Operation           | Target   |
|---------------------|----------|
| Simple route        | < 5 ms   |
| Database query      | < 10 ms  |
| Template rendering  | < 15 ms  |
| API endpoint        | < 20 ms  |
| Full page load      | < 50 ms  |
