# API Documentation (OpenAPI)

OmnioPHP generates an OpenAPI 3.0.3 document directly from your registered
routes — no docblock annotations to keep in sync, no extra dependency to
parse them. `Core\Model\OpenApiGenerator` introspects a `Router` instance's
routes and produces a plain array you can `json_encode()`.

## Generating a spec

Write a routes file that builds and returns a `Router` (see
[`routes/api.php`](../../routes/api.php) for a full example covering
[versioning](#versioning) and [rate limiting](#rate-limiting)):

```php
// routes/api.php
use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Router;

$router = new Router(new Request(), new Response());

$router->group(['prefix' => '/api/v1', 'middleware' => ['api']], function (Router $router) {
    $router->get('/users', 'System\Controller\UserController@index')->name('users.index');
    $router->get('/users/{id}', 'System\Controller\UserController@show')->name('users.show');
});

return $router;
```

Then generate the spec via the console:

```bash
php console docs:generate --routes=routes/api.php --output=public/openapi.json --title="My API" --api-version=1.0.0
```

This writes an OpenAPI JSON document to `public/openapi.json`. Point any
OpenAPI-compatible viewer at it — for example, Swagger UI's CDN build:

```html
<!DOCTYPE html>
<html>
<head><title>API Docs</title>
<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css"></head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
<script>
  SwaggerUIBundle({ url: '/openapi.json', dom_id: '#swagger-ui' });
</script>
</body>
</html>
```

## What gets generated

For every registered route, the generator produces one OpenAPI Operation
Object:

| Field | Source |
|---|---|
| `summary` / `operationId` | The route's `->name()`, or a slug derived from the controller action |
| `tags` | The first path segment, skipping a leading version segment like `v1` |
| `parameters` | `{param}` placeholders in the path, as required string path parameters |
| `x-middleware` | The route's attached middleware names, so auth/throttle requirements stay visible in the spec |
| `responses` | A generic `200` placeholder — see [Limitations](#limitations) |

## Versioning

Group routes under a version prefix with `Router::group()`:

```php
$router->group(['prefix' => '/api/v1', 'middleware' => ['api']], function (Router $router) {
    // ...
});
```

`OpenApiGenerator` skips a `v1`/`v2`/... segment when deriving a route's tag,
so `/api/v1/users` and a future `/api/v2/users` group under the same `users`
tag instead of splitting the docs by version.

## Rate limiting

The built-in `api` middleware group already includes `throttle` (see
`Core\Model\Middleware::registerBuiltInMiddleware()`, which bundles
`throttle`, `json`, `cors`, and `log`). Apply it at the group level as shown
above, or stack an additional `->middleware('throttle')` on a specific route
for a tighter, route-level limit. Either way it shows up in the generated
spec under `x-middleware`.

## Limitations

The generator works from route metadata only — it does not read method
bodies, so request/response bodies, status codes beyond a generic `200`, and
schemas are not inferred. Add them by post-processing the generated array
before writing it out, e.g.:

```php
$spec = (new OpenApiGenerator())->generate($router);
$spec['paths']['/users']['post']['requestBody'] = [
    'required' => true,
    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/User']]],
];
file_put_contents('public/openapi.json', json_encode($spec, JSON_PRETTY_PRINT));
```
