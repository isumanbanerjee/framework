# Routing

`Core\Model\Router` maps HTTP verbs and paths to callables, controller-action pairs, or arrays of `[ControllerClass::class, 'method']`.

## Basic routes

```php
use Core\Model\Router;

$router = new Router($request, $response);

$router->get('/', fn($req, $res) => $res->html('Home'));
$router->post('/users', fn($req, $res) => $res->json(['created' => true]));
$router->put('/users/{id}', fn($req, $res, $id) => /* ... */ null);
$router->patch('/users/{id}', fn($req, $res, $id) => /* ... */ null);
$router->delete('/users/{id}', fn($req, $res, $id) => /* ... */ null);
$router->options('/users/{id}', fn($req, $res, $id) => /* ... */ null);

$router->resolve();
```

## Route parameters

```php
$router->get('/users/{id}', function ($req, $res, $id) {
    $res->json(['id' => $id]);
});

$router->get('/posts/{postId}/comments/{commentId}', function ($req, $res, $postId, $commentId) {
    // ...
});
```

## Matching multiple verbs

```php
$router->match(['GET', 'POST'], '/search', $handler);
$router->any('/webhook', $handler); // all verbs
```

## Per-route middleware and naming

`Route` is a fluent value object returned by every registration method:

```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', 'admin'])
    ->name('admin.index');
```

## Groups

Groups apply a shared prefix and/or middleware stack to a set of routes and can be nested:

```php
$router->group(['prefix' => '/api/v1', 'middleware' => ['throttle']], function ($router) {
    $router->get('/users', [UserController::class, 'index']);

    $router->group(['prefix' => '/admin', 'middleware' => ['admin']], function ($router) {
        // final path: /api/v1/admin/...
        $router->get('/stats', [AdminController::class, 'stats']);
    });
});
```

## RESTful resources

```php
$router->resource('posts', PostController::class);
```

Registers the standard seven routes (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) — see [Controllers](controllers.md) for the corresponding `ResourceController` base class.

## Dispatching

`$router->resolve()` matches the current request against registered routes and runs the matched route's handler through the middleware pipeline (see [Middleware](../advanced/middleware.md)).

## Route caching

Route registration (building the group stack, normalizing paths) is cheap for
a few dozen routes but still redone on every request. For apps with routes
defined in a dedicated file that `return`s a configured `Router` (see
`routes/api.php` for the pattern), pre-compile them:

```bash
php console route:cache --routes=routes/api.php
php console route:clear
```

`route:cache` writes a plain-array dump to `Configuration/routes_compiled.php`
via `Router::toCacheable()` / `var_export()`. Routes whose action or
middleware is a `Closure` can't be serialized this way and are skipped — the
command reports how many and prompts you to switch them to
`"Controller@method"` or `[Controller::class, 'method']` syntax.

To actually use the cache at request time, check for it before re-running
route definitions:

```php
$router = new Router($request, $response);

$cachePath = __DIR__ . '/Configuration/routes_compiled.php';
if (file_exists($cachePath)) {
    $router->loadCached(require $cachePath);
} else {
    require __DIR__ . '/routes/web.php'; // defines routes on $router
}
```

Re-run `route:cache` (or `route:clear`, then let routes register normally)
whenever route definitions change — nothing auto-invalidates the compiled
file, matching the `config:cache` behavior it mirrors.
