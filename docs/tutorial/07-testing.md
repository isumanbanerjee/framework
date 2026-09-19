# 7. Testing

Cover the `POST /tasks` route with a Feature test — a real `Router` + `Middleware` pipeline + your controller callback, the way `index.php` wires them (see [Tests/README.md](../../Tests/README.md) for the full rationale).

`Response::json()` normally ends in `exit`, which would kill the PHPUnit process — so feature tests use a `RecordingResponse` test double that captures status/body instead. Reuse the one already defined in `Tests/Feature/RouterMiddlewareFlowTest.php`, or copy its pattern into a new test:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Model\Request;
use Core\Model\Router;
use PHPUnit\Framework\TestCase;

final class TaskFlowTest extends TestCase
{
    private array $originalPost;

    protected function setUp(): void
    {
        $this->originalPost = $_POST;
    }

    protected function tearDown(): void
    {
        $_POST = $this->originalPost;
    }

    public function testStoreCreatesATask(): void
    {
        $_POST = ['title' => 'Write the tutorial'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/tasks';

        $request = new Request();
        $response = new RecordingResponse();
        $router = new Router($request, $response);

        $router->post('/tasks', function ($req, $res) {
            // Stand in for TaskController::store() without touching a real DB —
            // see Tests/README.md on why feature tests favor a lightweight
            // callback over wiring the full Auth/Database stack.
            $res->json(['title' => $req->input('title'), 'completed' => false], 201);
        });

        $router->resolve();

        $this->assertSame(201, $response->statusCode);
        $payload = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Write the tutorial', $payload['title']);
    }
}
```

Run it:

```bash
php resources/vendor/bin/phpunit Tests/Feature/TaskFlowTest.php --testdox
```

For assertions against the real `Task` model/migration (not just the router callback), see [Tests/Integration](../../Tests/README.md#testsintegration) for the pattern of running against a real SQLite connection rather than mocking the database.

Next: [Next Steps](08-next-steps.md).
