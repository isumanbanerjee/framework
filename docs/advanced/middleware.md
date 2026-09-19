# Middleware

`Core\Model\Middleware` implements a request/response pipeline (array_reduce over a stack of handlers), integrated directly with the router.

## Built-in middleware

| Alias | Behavior |
|---|---|
| `auth` | Requires an authenticated session |
| `guest` | Redirects already-authenticated users |
| `csrf` | Validates the CSRF token on unsafe verbs |
| `cors` | Applies `CORS_ALLOWED_*` headers (see [Configuration](../getting-started/configuration.md)) |
| `throttle` | Rate limiting via `Core\Model\RateLimit` |
| `admin` | Requires an admin role |
| `json` | Forces `Content-Type: application/json` |
| `log` | Request logging |

## Built-in groups

```php
'web'   => ['csrf', 'log'],
'api'   => ['throttle', 'json', 'cors', 'log'],
'admin' => ['auth', 'admin', 'csrf', 'log'],
```

## Applying middleware

### Per-route (via `Route`)

```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', 'admin']);
```

### Route groups

```php
$router->group(['middleware' => ['api']], function ($router) {
    $router->post('/users', [UserController::class, 'store']);
});
```

### Manually

```php
$middleware = new Middleware($request, $response);

$middleware->handle(['auth', 'csrf'], function ($req, $res) {
    // protected logic
});

$middleware->handle('api', function ($req, $res) {
    // runs the whole 'api' group: throttle, json, cors, log
});
```

## Custom middleware

Register a class with a `handle(Request $request, Response $response, Closure $next)` method, or pass a closure directly:

```php
$middleware->alias('locale', LocaleMiddleware::class);

$middleware->add(function ($req, $res, $next) {
    // do something before
    $result = $next($req, $res);
    // do something after
    return $result;
});
```

Scaffold a new middleware class:

```bash
php console make:middleware LocaleMiddleware
```

## Input sanitization

`Core\Model\SanitizeMiddleware` trims leading/trailing whitespace from every
string in the request's GET/POST input, and converts empty strings to `null`
(mirroring Laravel's `TrimStrings` + `ConvertEmptyStringsToNull` pair). It's
not aliased or grouped by default — opt in per app:

```php
$middleware->alias('sanitize', \Core\Model\SanitizeMiddleware::class);
$middleware->group('web', ['sanitize', 'csrf', 'log']);
```

This runs before controllers/validation and is independent of
`Request::input()`'s own per-call `htmlspecialchars()` escaping — sanitize
normalizes raw input structure, `input()` still escapes for safe HTML output.

See also: [Routing](../fundamentals/routing.md).
