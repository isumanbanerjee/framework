# Caching

`Core\Model\Cache` is a multi-driver cache abstraction over Redis, Memcached, File, and APCu, with a consistent API regardless of driver.

## Choosing a driver

```php
use Core\Model\Cache;

$cache = new Cache('redis', ['host' => '127.0.0.1', 'port' => 6379]);
$cache = new Cache('file');   // no external service required
$cache = new Cache('apcu');
$cache = new Cache('memcached', ['servers' => [['127.0.0.1', 11211]]]);
```

Or configure it via environment:

```env
CACHE_DRIVER=redis
CACHE_REDIS_HOST=127.0.0.1
CACHE_REDIS_PORT=6379
CACHE_DEFAULT_TTL=3600
```

## Basic operations

```php
$cache->put('key', 'value', 3600); // TTL in seconds
$value = $cache->get('key', 'default');
$cache->forever('permanent-key', 'value'); // no expiry
$cache->forget('key');
$cache->has('key');
$cache->flush();
```

## Remember pattern

Compute and cache a value in one call — the callback only runs on a miss:

```php
$users = $cache->remember('users:active', 3600, function () use ($db) {
    return $db->fetchAll('SELECT * FROM users WHERE active = ?', [1]);
});

$config = $cache->rememberForever('app:config', fn() => loadExpensiveConfig());
```

## Atomic counters

```php
$cache->increment('page_views');
$cache->decrement('stock', 5);
```

## Bulk writes

```php
$cache->putMany(['key1' => 'val1', 'key2' => 'val2'], 3600);
```

## When to reach for caching

- **Query result caching** — wrap expensive, infrequently-changing queries in `remember()`.
- **Config caching** — a separate, file-based mechanism; see `php console config:cache` in [Configuration](../getting-started/configuration.md).
- **Route/template caching** — templates are compiled and cached to disk automatically by `Core\Model\Template` (see [Views](../fundamentals/views.md)).

For OPcache and other server-level performance tuning, see [Performance Tuning](../performance.md).

## HTTP response caching (ETag / 304)

`Core\Model\Response` can opt in to ETag-based conditional responses on `json()`, `text()`, and `html()`. When enabled, the response body is hashed into a weak identity `ETag` header; if the client's `If-None-Match` request header matches, the server replies `304 Not Modified` with an empty body instead of resending the payload:

```php
$response->withEtag()->json($data);
```

Notes:

- Opt-in only — `withEtag(false)` (the default) skips hashing entirely, since computing an `ETag` for every response has a real cost that not every route should pay.
- Only applies to `200` responses; redirects and error statuses are left untouched, since conditional revalidation is only meaningful for a successful, cacheable payload.
- The comparison honors `*`, comma-separated lists, and weak (`W/`) validators per the `If-None-Match` spec.
