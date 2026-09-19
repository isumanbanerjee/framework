# Views & Templates

`Core\Model\Template` is a Blade-inspired template engine: `.php` view files with a small set of directives, layout inheritance, and compiled-template caching.

## Rendering

```php
use Core\Model\Template;

$template = new Template('views');
echo $template->render('welcome', ['name' => 'John']);
```

`render()` looks up `views/welcome.php` relative to the base directory passed to the constructor.

## Directives

| Directive | Purpose |
|---|---|
| `{{ $expr }}` | Escaped output |
| `{!! $expr !!}` | Raw, unescaped output |
| `@if` / `@elseif` / `@else` / `@endif` | Conditionals |
| `@foreach` / `@endforeach` | Loops |
| `@extends('layout')` | Declare the parent layout |
| `@section('name')` / `@endsection` | Define a block to be injected into the layout |
| `@yield('name')` | Output a section's content from within a layout |

```php
{{-- views/welcome.php --}}
@extends('layouts.app')

@section('content')
    <h1>Hello {{ $name }}!</h1>

    @if($user->isAdmin)
        <p>Admin Panel Access</p>
    @endif

    @foreach($items as $item)
        <div>{{ $item->name }}</div>
    @endforeach
@endsection
```

```php
{{-- views/layouts/app.php --}}
<!DOCTYPE html>
<html>
<body>
    @yield('content')
</body>
</html>
```

## Custom directives

```php
$template->directive('upper', function (string $expression) {
    return "<?php echo strtoupper({$expression}); ?>";
});
```

## Shared data

```php
$template->share('appName', 'OmnioPHP'); // available in every render() call
```

## Caching

Compiled templates are cached to disk automatically; `Template::clearCache()` removes them (useful after a deploy or when developing directive changes).

## Error views

`resources/views/errors/{404,403,500,503}.php` are the framework's own branded error pages, rendered by the error-handling layer — see [Error.php](../../Core/Model/Error.php) and [Response.php](../../Core/Model/Response.php) for how they're dispatched.

## View scaffolding

`resources/views/` ships with a starter structure to build on:

```
resources/views/
├── layouts/
│   ├── app.php      — full page shell with header/footer, @yield('content')
│   └── guest.php     — centered card layout for unauthenticated pages
├── components/
│   ├── header.php
│   └── footer.php
├── auth/
│   ├── login.php     — @extends('layouts.guest')
│   └── register.php  — @extends('layouts.guest')
└── errors/
    └── {404,403,500,503}.php
```

`layouts/app.php` and `layouts/guest.php` are plain example layouts, not wired to any router/controller — copy and adapt them once real auth routes exist. `components/header.php` and `components/footer.php` read `$appName` from **shared** data (`$template->share('appName', 'MyApp')`), not from `render()`'s `$data` argument — see the note below on `@include` scoping.

## `@include` does not inherit the caller's variables

Unlike Blade, `@include('view')` here compiles to a fresh `$this->render('view', [])` call — it does **not** automatically see the including template's local variables. Pass data explicitly:

```php
@include('components.header', ['title' => $pageTitle])
```

or, for data that every included component needs (like an app name in a header/footer), use `$template->share()` once instead of threading it through every `@include`/`@extends` call.
