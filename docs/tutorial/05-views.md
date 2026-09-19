# 5. Views

So far `TaskController` only returns JSON. Add an HTML index page using `Core\Model\Template` (see [Views & Templates](../fundamentals/views.md) for the full directive reference).

## Layout

`resources/views/` already ships `layouts/app.php` as a starter shell — copy/adapt it, or reuse it directly:

```php
{{-- resources/views/layouts/app.php --}}
<!DOCTYPE html>
<html>
<head><title>Tasks</title></head>
<body>
    @yield('content')
</body>
</html>
```

## Task list view

```php
{{-- resources/views/tasks/index.php --}}
@extends('layouts.app')

@section('content')
    <h1>Tasks</h1>

    <form method="POST" action="/tasks">
        <input type="hidden" name="csrf_token" value="{{ $csrfToken }}">
        <input type="text" name="title" placeholder="New task">
        <button type="submit">Add</button>
    </form>

    <ul>
        @foreach($tasks as $task)
            <li>
                {{ $task->title }}
                @if($task->completed)
                    (done)
                @endif
            </li>
        @endforeach
    </ul>
@endsection
```

`{{ $expr }}` auto-escapes; the `csrf_token` hidden field is required because task routes sit behind the `web` middleware group, which includes `csrf` (see [Middleware](../advanced/middleware.md)) — omit it and `POST /tasks` gets rejected.

## Render it from the controller

```php
use Core\Model\Template;
use Core\Model\Session;

public function index(Request $request, Response $response): void
{
    $session = new Session();
    $userId = $session->get('user_id');
    $tasks = Task::query()->where('user_id', $userId)->orderBy('created_at DESC')->get();

    $template = new Template('resources/views');
    $response->html($template->render('tasks.index', [
        'tasks' => $tasks,
        'csrfToken' => $session->generateCsrfToken(),
    ]));
}
```

Next: [Authentication & Middleware](06-authentication-and-middleware.md).
