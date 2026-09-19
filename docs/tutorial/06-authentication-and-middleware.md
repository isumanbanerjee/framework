# 6. Authentication & Middleware

`routes/web.php` already wires `GET`/`POST /login` and `/register` to `System\Controller\AuthController`, rendering the `auth.login`/`auth.register` views shipped in `resources/views/auth/` (see [View scaffolding](../fundamentals/views.md#view-scaffolding)). Use that as-is for signup/login — this part is about protecting the task routes you already registered in [part 2](02-routes-and-controllers.md).

## The `auth` middleware

```php
$router->group(['prefix' => '/tasks', 'middleware' => ['web', 'auth']], function (Router $router) {
    // ...
});
```

`auth` requires an authenticated session; unauthenticated requests are rejected before the controller runs. That's why `(new Session())->get('user_id')` in the controller can be trusted — by the time a handler executes, `auth` middleware has already confirmed the session belongs to a logged-in user.

## Where `user_id` comes from

`Auth::login()` (called from `AuthController@login`) stores the authenticated user's ID in the session on success:

```php
use Core\Model\Auth;
use Core\Model\Session;
use Core\Model\Database\Database;
use Core\Model\Logger;

$session = new Session();
$auth = new Auth(new Database(new Logger('logs/app.log')), $session);

if ($auth->login($request->input('email'), $request->input('password'))) {
    // Auth stores the user id on $session — later requests read it back
    // via (new Session())->get('user_id') as in the TaskController examples.
}
```

You don't need to write this yourself for the tutorial — `AuthController` already does it — but it's why scoping `Task::query()->where('user_id', ...)` works: every task route runs after `auth` middleware has established a valid session.

## Full middleware group recap

```php
'web'   => ['csrf', 'log'],
'api'   => ['throttle', 'json', 'cors', 'log'],
'admin' => ['auth', 'admin', 'csrf', 'log'],
```

Tasks use `['web', 'auth']` explicitly rather than the `admin` group, since tasks belong to any logged-in user, not just admins. See [Middleware](../advanced/middleware.md) for the full alias table and how to register custom middleware (e.g. if you later add an `admin`-only task-archive feature).

Next: [Testing](07-testing.md).
