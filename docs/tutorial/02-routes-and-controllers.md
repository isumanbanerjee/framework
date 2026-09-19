# 2. Routes & Controllers

## Register the routes

Add a new file, `routes/tasks.php`, following the same pattern as `routes/web.php` — it only adds routes to the shared `$router` created in `index.php`:

```php
<?php

declare(strict_types=1);

use Core\Model\Router;
use App\Controller\TaskController;

/** @var Router $router */

$router->group(['prefix' => '/tasks', 'middleware' => ['web', 'auth']], function (Router $router) {
    $router->get('/', [TaskController::class, 'index'])->name('tasks.index');
    $router->post('/', [TaskController::class, 'store'])->name('tasks.store');
    $router->post('/{id}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    $router->delete('/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});
```

Require it from `index.php` alongside the existing `routes/web.php` include (see [Routing](../fundamentals/routing.md) for the full picture on groups, middleware, and naming).

`auth` here is the built-in middleware alias — it's what gates every task route behind login. That's covered in [part 6](06-authentication-and-middleware.md); routes registered against it will 401/redirect until then, which is fine at this stage.

## Scaffold the controller

```bash
php console make:controller TaskController
```

Fill it in with plain method handlers — no required base class, per [Controllers](../fundamentals/controllers.md):

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Core\Model\Request;
use Core\Model\Response;

class TaskController
{
    public function index(Request $request, Response $response): void
    {
        $response->json(['tasks' => []]); // wired to real data in part 3
    }

    public function store(Request $request, Response $response): void
    {
        $response->json(['created' => true], 201);
    }

    public function complete(Request $request, Response $response, string $id): void
    {
        $response->json(['id' => $id, 'completed' => true]);
    }

    public function destroy(Request $request, Response $response, string $id): void
    {
        $response->json(['id' => $id, 'deleted' => true]);
    }
}
```

At this point the routes resolve and return stub JSON — enough to confirm wiring before adding persistence.

Next: [Database & Models](03-database-and-models.md).
