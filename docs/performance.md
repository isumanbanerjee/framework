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

Generate an optimized, class-map authoritative autoloader for production:

```bash
composer install --no-dev --optimize-autoloader
```

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
