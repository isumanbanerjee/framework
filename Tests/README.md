# Test Suite

Three testsuites are registered in `phpunit.xml`, all run by default via `php resources/vendor/bin/phpunit` (or `composer test`).

## `Tests/Unit`

Exercises a single class in isolation — a `Router` test registers routes and asserts on `resolve()` without any real middleware; a `SanitizeMiddleware` test calls `->handle()` directly with a hand-written `$next` closure. Prefer this for anything that doesn't need more than one component wired together.

## `Tests/Feature`

Exercises a full request lifecycle: real `Router` + `Middleware` pipeline + a controller-style callback, wired together the way `index.php`/a route file would wire them. Use this to catch bugs that only show up at the seams — e.g. a middleware that mutates the `Request` in a way a controller doesn't expect, or a group's prefix/middleware not composing the way a single-component test would assume.

### Why feature tests need a `Response` test double

`Core\Model\Response::json()`/`html()`/`text()`/`redirect()` all end by calling `exit` (see `Response::sendBody()`), and several built-in middleware classes (`AuthMiddleware`, `CsrfMiddleware`, `ThrottleMiddleware`, `AdminMiddleware`, `CorsMiddleware`) call one of those methods on the failure path. That's correct for a real HTTP response, but it would kill the PHPUnit process mid-suite.

`Tests/Feature/RouterMiddlewareFlowTest.php` defines a `RecordingResponse extends Response` that overrides `setStatusCode()`, `setHeader()`, `json()`, and `redirect()` to record the would-be status code/headers/body onto public properties instead of calling `header()`/`echo`/`exit`. Pass a `RecordingResponse` instead of a real `Response` when a feature test's route or middleware might call a terminating method, then assert against its `statusCode`/`headers`/`body` properties instead of capturing real HTTP output.

```php
$response = new RecordingResponse();
$router = new Router($request, $response);

// ... register routes/middleware, call $router->resolve() ...

$this->assertSame(201, $response->statusCode);
$payload = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
```

Built-in middleware that constructs real dependencies internally (`AuthMiddleware`/`AdminMiddleware` via `new Session()`/`new Auth()`, `ThrottleMiddleware` via `new Cache('file')`) is easiest to exercise in a feature test by writing an equivalent closure or lightweight test-only middleware class instead of the real alias — that keeps the test focused on the router/middleware/controller *wiring*, not on faking sessions or the filesystem.

## `Tests/Integration`

Exercises a component against a real external engine instead of a mock or in-process double — e.g. `QueryBuilderIntegrationTest` runs the query builder against a real `sqlite::memory:` PDO connection rather than asserting on generated SQL strings. Use this instead of `Tests/Unit` when a test's value comes from actually round-tripping through the real engine (catching SQL dialect mistakes, type coercion, etc.), not just from exercising the class's code paths.

## Superglobals

`Core\Model\Request` reads `$_GET`/`$_POST`/`$_SERVER`/`$_FILES`/`$_COOKIE` directly in its constructor. Set the superglobals you need *before* calling `new Request()`, and restore `$_GET`/`$_POST` in `tearDown()` so tests don't leak state into each other:

```php
protected function tearDown(): void
{
    $_GET = $this->originalGet;
    $_POST = $this->originalPost;
}
```
