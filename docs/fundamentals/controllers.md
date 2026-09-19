# Controllers

Controllers are plain PHP classes referenced as `[ControllerClass::class, 'method']` in route definitions. There is no required base class for ad hoc controllers — the framework only provides `Core\Controller\ResourceController` as an opinionated base for RESTful resources.

## Plain controllers

```php
namespace App\Controller;

use Core\Model\Request;
use Core\Model\Response;

class UserController
{
    public function index(Request $request, Response $response): void
    {
        $response->json(['users' => User::all()]);
    }

    public function show(Request $request, Response $response, string $id): void
    {
        $user = User::find((int) $id);
        $user ? $response->json($user->toArray()) : $response->json(['error' => 'Not found'], 404);
    }
}
```

```php
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);
```

## Resource controllers

`Core\Controller\ResourceController` provides the seven conventional REST actions (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`). Each defaults to a `501 Not Implemented` response — override only the actions you need:

```php
use Core\Controller\ResourceController;

class PhotoController extends ResourceController
{
    public function index(Request $request, Response $response)
    {
        return $response->json(['photos' => Photo::all()]);
    }

    public function store(Request $request, Response $response)
    {
        $photo = Photo::create(['url' => $request->input('url')]);
        return $response->json($photo->toArray(), 201);
    }
}
```

Pair it with `Router::resource()` to register all seven routes at once:

```php
$router->resource('photos', PhotoController::class);
```

## Scaffolding a new controller

```bash
php console make:controller UserController
```

Generates a stub under the conventional controller directory using `resources/stubs/controller.stub`.

See also: [Routing](routing.md), [Models](models.md).
