# Debug Toolbar

OmnioPHP ships a lightweight debug toolbar for local development. It shows request/response details, execution time, memory usage, included file count, aggregated cache hit/miss/write counters, and session state — all as a fixed bar injected at the bottom of every HTML response.

## Enabling it

The toolbar is off by default and controlled entirely by `APP_DEBUG` in `Configuration/config.env`:

```env
APP_DEBUG=true
```

**Never enable this in production.** The toolbar exposes internal timing, memory, and session key information that shouldn't be visible to end users. Keep `APP_DEBUG=false` outside local development.

## How it works

`Core\Model\DebugBar::inject()` is called from `Response::html()` for every HTML response. When `APP_DEBUG` is falsy, it's a no-op — the response is returned unchanged. When truthy, it renders a toolbar and inserts it immediately before the closing `</body>` tag (or appends it, if the response has no `</body>`).

```php
use Core\Model\Response;

$response = new Response();
$response->html($template->render('welcome'));
// Toolbar is appended automatically when APP_DEBUG=true
```

## What it shows

| Item | Source |
|---|---|
| Method, URI, status code | `$_SERVER['REQUEST_METHOD']`, `$_SERVER['REQUEST_URI']`, the response status |
| Execution time | `$_SERVER['REQUEST_TIME_FLOAT']` to render time |
| Memory usage | `memory_get_usage()` / `memory_get_peak_usage()` |
| Included files | `count(get_included_files())` |
| Cache stats | `Cache::getGlobalStats()` — hits/misses/writes aggregated across every `Cache` instance created during the request |
| Session | Active session key names, or `inactive` if no session was started |

## Scope and limitations

The toolbar does not currently log individual SQL queries executed via `QueryBuilder` — only cache activity is aggregated globally. If you need query-level profiling, wrap your `QueryBuilder` calls with your own timing/logging for now.

`Cache::getGlobalStats()` is a static counter shared across all `Cache` instances for the life of the PHP process/request; call `Cache::resetGlobalStats()` between requests in long-running contexts (e.g. queue workers) if you rely on it for per-job diagnostics.
