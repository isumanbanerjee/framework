# OmnioPHP

**A lightweight, batteries-included PHP 8.1+ framework for building web applications and REST APIs.**

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-777BB4?style=flat&logo=php)](https://www.php.net/)
[![License: GPL v3](https://img.shields.io/badge/License-GPL--3.0-green.svg)](LICENSE)
[![Tests](https://github.com/isumanbanerjee/framework/actions/workflows/tests.yml/badge.svg)](https://github.com/isumanbanerjee/framework/actions/workflows/tests.yml)

OmnioPHP is a full-stack PHP framework that ships routing, an Active Record ORM with a fluent query builder and schema migrations, a middleware pipeline, a Blade-inspired template engine, sessions/authentication, a PSR-11 dependency injection container, caching (Redis/Memcached/File/APCu), a queue/job system, an event dispatcher, file storage, mail, an HTTP client, and a CLI — as a single Composer package, so there's no assembly required to get a production-ready application running.

**Created by:** [Suman Banerjee](https://www.isumanbanerjee.com) · **Maintained by:** [AnteOmnio](https://www.anteomnio.com)

---

## Why OmnioPHP?

- **Complete stack** — 23 components cover routing, persistence, auth, caching, queues, templating, and more without pulling in a dozen separate packages
- **Familiar patterns** — Active Record models, Blade-like templates, route groups/middleware, and a fluent query builder, if you've used a modern PHP or Laravel-style framework
- **Security by default** — CSRF protection, parameterized queries, auto-escaping templates, rate limiting, and secure session handling built in
- **Tested** — a growing PHPUnit suite (unit, feature, and integration tests) and PHPStan level 5 static analysis, both enforced in CI
- **Documented** — PHPDoc on every public API, long-form guides under [`docs/`](docs/README.md), and a [step-by-step tutorial](docs/tutorial/README.md)

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Modules](#modules)
- [Core Components](#core-components)
- [Configuration](#configuration)
- [Security](#security)
- [Testing](#testing)
- [CLI Tools](#cli-tools)
- [Documentation](#documentation)
- [Project Structure](#project-structure)
- [Contributing](#contributing)
- [License](#license)

---

## Requirements

- PHP 8.1 or higher
- Composer
- A web server (Apache with `mod_rewrite`, or Nginx) or PHP's built-in server

**Required extensions:** `curl`, `fileinfo`, `openssl`, `pdo`, `pdo_mysql`, `mbstring`, `json`, `session`
**Optional extensions:** `xdebug` (development), `opcache` (production), `redis`, `memcached`

---

## Installation

```bash
git clone https://github.com/isumanbanerjee/framework.git
cd framework
composer install
```

Composer installs into `resources/vendor` (see `config.vendor-dir` in `composer.json`), not the default `vendor/` directory.

Configure your environment:

```bash
cp Configuration/config.env.example Configuration/config.env
# edit Configuration/config.env with your database and application settings
```

Serve the application:

```bash
# Docker (recommended — a consistent PHP 8.1 + Apache environment)
docker compose up -d --build

# or PHP's built-in server
php -S localhost:8000 -t . index.php
```

Verify it's running:

```bash
curl http://localhost:8080/health
# {"status":"healthy","timestamp":...}
```

---

## Quick Start

### Hello world

```php
<?php
require_once 'resources/vendor/autoload.php';

use Core\Model\{Request, Response, Router};

$request = new Request();
$response = new Response();
$router = new Router($request, $response);

$router->get('/', function ($req, $res) {
    $res->html('<h1>Welcome to OmnioPHP!</h1>');
});

$router->get('/api/status', function ($req, $res) {
    $res->json(['status' => 'ok', 'framework' => 'OmnioPHP']);
});

$router->resolve();
```

### Middleware-protected routes

```php
use Core\Model\Middleware;

$middleware = new Middleware($request, $response);

$middleware->handle(['auth', 'csrf'], function ($req, $res) use ($router) {
    $router->get('/dashboard', function ($req, $res) {
        $res->json(['user' => 'authenticated']);
    });
    $router->resolve();
});
```

### Migrations and Active Record models

```php
use Core\Model\Database\{Migration, Schema, Blueprint};

class CreateUsersTable extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('users');
    }
}
```

```php
use Core\Model\Database\Model;

class User extends Model
{
    protected array $fillable = ['email', 'password'];
}

Model::setConnection($pdo);

$user = User::create(['email' => 'ada@example.com', 'password' => $hash]);
$found = User::find($user->id);
$found->email = 'ada.l@example.com';
$found->save();

$posts = $user->hasMany(Post::class, 'user_id');
$author = $post->belongsTo(User::class, 'user_id');
```

### Dependency injection container

A PSR-11 compliant container with autowiring:

```php
use Core\Model\Container;

$container = new Container();

$container->bind('uuid', fn () => \Ramsey\Uuid\Uuid::uuid4()->toString());     // transient
$container->singleton(Logger::class, fn () => new Logger());                    // shared
$container->instance('config', $configArray);                                   // pre-built

// Autowiring — constructor dependencies resolved recursively, no registration needed
$service = $container->get(UserService::class);
```

Group related bindings in a service provider:

```php
use Core\Model\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Database::class, fn ($c) =>
            new Database($c->get(Logger::class), $c->get(Error::class))
        );
    }

    public function boot(): void
    {
        // runs after all providers are registered
    }
}
```

### Other building blocks

```php
// Cache
use Core\Model\Cache;
$cache = new Cache('redis');
$cache->remember('users', 3600, fn () => $db->fetchAll("SELECT * FROM users"));

// Events
use Core\Model\Event;
Event::listen('user.registered', fn ($user) => Mail::send($user->email, 'Welcome!'));
Event::fire('user.registered', $user);

// Queue
use Core\Model\Queue;
$queue = new Queue('database');
$queue->push(SendEmailJob::class, ['to' => 'user@example.com']);

// Collections
$emails = collect($users)->pluck('email')->unique()->all();

// Storage
use Core\Model\Storage;
$storage = new Storage();
$path = $storage->putFile('avatar', 'users/avatars');

// Notifications
use Core\Model\{Notification, Notifier, DatabaseChannel};

class WelcomeNotification extends Notification
{
    public function via($notifiable): array { return ['database']; }
    public function toArray($notifiable): array { return ['message' => 'Welcome!']; }
}

$notifier = (new Notifier())->extend('database', new DatabaseChannel($pdo));
$notifier->send($user, new WelcomeNotification());
```

New to the framework? Follow the [full tutorial](docs/tutorial/README.md) to build a small login-gated task-tracker app end to end.

---

## Modules

| Module | Description |
|---|---|
| **Router/Middleware** | Dynamic routing with parameters, groups, resource routes, and an 8-middleware pipeline |
| **Database/ORM** | PDO-based query builder, Active Record models, migrations, and sharding |
| **Cache** | Redis, Memcached, File, and APCu drivers with atomic operations |
| **Template** | Blade-like syntax with layout inheritance and compiled caching |
| **Queue/Jobs** | Background processing with retries and delayed execution |
| **Events** | Event-driven architecture with listeners, subscribers, and wildcards |
| **Storage** | Unified file storage abstraction (Local, S3, FTP) with streaming |
| **Mail** | SMTP sending with templates and queue integration |
| **HTTP Client** | A modern client for calling external APIs |
| **Collections** | Fluent array manipulation with 25+ chainable methods |
| **Pagination** | Database pagination with Bootstrap 5 styling and JSON output |
| **Console** | A CLI with generators, migrations, and custom commands |
| **Rate Limiting** | Configurable throttling per route or middleware group |
| **Localization** | Multi-language support with placeholder interpolation |
| **Container** | PSR-11 dependency injection with autowiring and service providers |
| **Auth/Session** | Login, registration, remember-me, CSRF-protected sessions |
| **Validation** | 17+ built-in validation rules |
| **Error Handling** | Centralized error management with 120+ predefined error codes |
| **Logging** | Multi-level, rotation-aware logging |

Expand any module below for usage examples, or see the [docs index](docs/README.md) for longer guides.

<details>
<summary><strong>Cache</strong></summary>

```php
use Core\Model\Cache;

$cache = new Cache('redis', ['host' => '127.0.0.1', 'port' => 6379]);

$cache->put('key', 'value', 3600);
$value = $cache->get('key', 'default');
$cache->forget('key');

$data = $cache->remember('expensive-query', 3600, fn () => $db->fetchAll("SELECT * FROM large_table"));

$cache->increment('page_views');
$cache->decrement('stock', 5);

$cache->putMany(['key1' => 'val1', 'key2' => 'val2'], 3600);
```

```env
CACHE_DRIVER=redis
CACHE_REDIS_HOST=127.0.0.1
CACHE_REDIS_PORT=6379
CACHE_DEFAULT_TTL=3600
```
</details>

<details>
<summary><strong>Middleware</strong></summary>

```php
use Core\Model\Middleware;

$middleware = new Middleware($request, $response);

$middleware->handle(['auth'], fn ($req, $res) => /* protected route */ null);
$middleware->handle(['auth', 'csrf', 'admin'], fn ($req, $res) => /* admin-only route */ null);
$middleware->handle('api', fn ($req, $res) => /* group: throttle, json, cors, log */ null);
```

Built-in aliases: `auth`, `guest`, `csrf`, `cors`, `throttle`, `admin`, `json`, `log`.
</details>

<details>
<summary><strong>Templates</strong></summary>

```php
use Core\Model\Template;

$template = new Template('views');
echo $template->render('welcome', ['name' => 'John', 'items' => $items]);
```

```blade
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

Directives: `{{ }}` (escaped), `{!! !!}` (raw), `@if`/`@elseif`/`@else`/`@endif`, `@foreach`/`@endforeach`, `@extends`, `@section`/`@endsection`, `@yield`, plus custom directives via `$template->directive()`.
</details>

<details>
<summary><strong>Queue / Jobs</strong></summary>

```php
use Core\Model\Queue;

$queue = new Queue('database');
$queue->push(SendEmailJob::class, ['to' => 'user@example.com', 'subject' => 'Hello']);
$queue->later(60, ProcessVideoJob::class, ['video_id' => 123]);
$queue->work();
```

```php
class SendEmailJob
{
    public function handle(array $data)
    {
        (new Mail())->send($data['to'], $data['subject'], $data['body']);
    }
}
```

Database, Redis, and File drivers; delayed execution; automatic retries; failed-job tracking.
</details>

<details>
<summary><strong>Events</strong></summary>

```php
use Core\Model\Event;

Event::listen('user.registered', function ($user) {
    Mail::send($user->email, 'Welcome!', 'emails.welcome');
    Log::info("User registered: {$user->email}");
});

Event::fire('user.registered', $user);
Event::listen('user.*', fn ($payload, $event) => Log::info("User event: $event"));
Event::queue('send.newsletter', $subscribers);
```
</details>

<details>
<summary><strong>Storage</strong></summary>

```php
use Core\Model\Storage;

$storage = new Storage('local', ['root' => 'storage/app']);

$storage->put('documents/readme.txt', 'File contents');
$path = $storage->putFile('avatar', 'users/avatars');
$contents = $storage->get('documents/readme.txt');

$storage->copy('old.txt', 'new.txt');
$storage->move('temp.txt', 'archived/temp.txt');
$storage->delete('old-file.txt');

$url = $storage->url('images/logo.png');
$tempUrl = $storage->temporaryUrl('private/document.pdf', 3600);
```

```env
STORAGE_DRIVER=local
STORAGE_ROOT=storage/app
STORAGE_MAX_FILE_SIZE=10485760
STORAGE_ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,pdf,doc,docx
```
</details>

<details>
<summary><strong>Mail</strong></summary>

```php
use Core\Model\Mail;

$mail = new Mail([
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your@gmail.com',
    'password' => 'app-password',
    'encryption' => 'tls',
]);

$mail->send('user@example.com', 'Welcome!', '<h1>Welcome to OmnioPHP</h1>');
$mail->sendTemplate('user@example.com', 'Welcome', 'emails.welcome', ['name' => 'John']);
$mail->queue('user@example.com', 'Newsletter', $htmlBody);
```
</details>

<details>
<summary><strong>HTTP Client</strong></summary>

```php
use Core\Model\Http;

$response = Http::get('https://api.example.com/users');
$users = $response->json();

$response = Http::post('https://api.example.com/users', ['name' => 'John Doe']);

$response = Http::get('https://api.example.com/protected', [], [
    'Authorization' => 'Bearer ' . $token,
]);

if ($response->successful()) {
    $data = $response->json();
}
```
</details>

<details>
<summary><strong>Collections</strong></summary>

```php
use Core\Model\Collection;

$result = collect([1, 2, 3, 4, 5])
    ->filter(fn ($n) => $n > 2)
    ->map(fn ($n) => $n * 2)
    ->sum(); // 24

$activeUsers = collect($users)->where('active', true)->pluck('email')->unique()->all();
```

`map`, `filter`, `where`, `pluck`, `unique`, `sort`, `reverse`, `chunk`, `take`, `skip`, `sum`, `avg`, `min`, `max`, `groupBy`, `first`, `last`, `isEmpty`, `count`, `toJson`, and more.
</details>

<details>
<summary><strong>Pagination</strong></summary>

```php
use Core\Model\Pagination;

$paginator = new Pagination($items, $totalCount, 15, $currentPage);

foreach ($paginator->items() as $item) {
    echo $item->name;
}

echo $paginator->links();     // Bootstrap 5 styled links
$json = $paginator->toJson(); // API output
```
</details>

<details>
<summary><strong>CLI Console</strong></summary>

```bash
php console help
php console cache:clear
php console queue:work
php console queue:work emails --timeout=60
php console make:controller UserController
php console make:model User
php console serve --port=8000
```

```php
use Core\Model\Console;

$console = new Console();

$console->register('greet', function ($args, $options) use ($console) {
    $name = $args[0] ?? 'World';
    $console->success("Hello, $name!");
});
// Usage: php console greet John
```
</details>

<details>
<summary><strong>Rate Limiting</strong></summary>

```php
use Core\Model\RateLimit;

$limiter = RateLimit::for('api'); // 60 attempts per minute
$key = 'api:' . $request->ip();

if ($limiter->tooManyAttempts($key)) {
    $response->json(['error' => "Too many requests. Try again in {$limiter->availableIn($key)}s."], 429);
    return;
}

$limiter->hit($key);
$response->setHeader('X-RateLimit-Remaining', (string) $limiter->retriesLeft($key));
```

Predefined limits: `api` (60/min), `login` (5/min), `global` (1000/min).
</details>

<details>
<summary><strong>Localization (i18n)</strong></summary>

```php
use Core\Model\Lang;

Lang::setLocale('es');
echo Lang::get('welcome');                             // "Bienvenido"
echo __('messages.greeting', ['name' => 'John']);      // "Hola, John!"
```

Ships with `en`, `es`, `fr`, `de` locales (`resources/lang/{locale}.php`), tested for structural parity by `Tests/Unit/LangFilesTest.php`.
</details>

---

## Core Components

<details>
<summary><strong>Session Management</strong></summary>

```php
use Core\Model\Session;

$session = new Session();

$session->set('user_id', 123);
$userId = $session->get('user_id');

$session->setFlash('success', 'User created successfully!');
$message = $session->getFlash('success');

$token = $session->generateCsrfToken();
if ($session->validateCsrfToken($_POST['csrf_token'])) {
    // valid request
}
```

HTTPOnly cookies, secure cookies over HTTPS, `SameSite=Strict`, and session regeneration on login.
</details>

<details>
<summary><strong>Authentication</strong></summary>

```php
use Core\Model\Auth;

$auth = new Auth($db, $session);

if ($auth->login('user@example.com', 'password', $rememberMe = true)) {
    // success
}

$userId = $auth->register(['email' => 'new@example.com', 'password' => 'secure123', 'name' => 'John Doe']);

if ($auth->check()) {
    $user = $auth->user();
}

$auth->logout();
```
</details>

<details>
<summary><strong>Routing</strong></summary>

```php
use Core\Model\Router;

$router = new Router($request, $response);

$router->get('/', fn ($req, $res) => $res->html('Home'));
$router->post('/users', fn ($req, $res) => $res->json(['created' => true]));

$router->get('/users/{id}', function ($req, $res, $id) {
    $res->json($db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]));
});

$router->get('/posts/{postId}/comments/{commentId}', fn ($req, $res, $postId, $commentId) => null);

$router->resolve();
```
</details>

<details>
<summary><strong>Database</strong></summary>

```php
use Core\Model\Database\Database;

$db = new Database($logger);

$users = $db->fetchAll("SELECT * FROM users WHERE active = ?", [1]);
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [123]);
$db->executeQuery("UPDATE users SET status = ? WHERE id = ?", ['active', 123]);

$db->beginTransaction();
try {
    $db->executeQuery("INSERT INTO ...");
    $db->executeQuery("UPDATE ...");
    $db->commitTransaction();
} catch (\Exception $e) {
    $db->rollbackTransaction();
}

$users = $db->queryBuilder()
    ->select('*')
    ->from('users')
    ->where('active = ?', [1])
    ->orderBy('created_at DESC')
    ->limit(10)
    ->fetchAll();
```
</details>

<details>
<summary><strong>Validation</strong></summary>

```php
use Core\Model\Validation;

$validation = new Validation($db);

$rules = [
    'name' => 'required|min:2|max:100|alpha',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8',
    'age' => 'numeric',
];

if ($validation->make($_POST, $rules)) {
    // valid
} else {
    $errors = $validation->errors();
    $firstError = $validation->firstError('email');
}
```

Rules: `required`, `email`, `min`, `max`, `numeric`, `alpha`, `alphanumeric`, `match`, `unique`, `url`.
</details>

---

## Configuration

All configuration is centralized in `.env`-style files under `Configuration/`.

**`Configuration/config.env`** — application settings:

```env
DB_TYPE=mysql
DB_HOST=localhost
DB_NAME=omniophp
DB_USERNAME=root
DB_PASSWORD=secret

CACHE_DRIVER=redis
CACHE_REDIS_HOST=127.0.0.1
CACHE_DEFAULT_TTL=3600

APP_NAME=OmnioPHP
APP_ENV=production
APP_DEBUG=false
```

**`Configuration/error.env`** — error messages, keyed by error code:

```env
DATABASE_CONNECTION_FAILED='Database connection failed.'
AUTH_LOGIN_FAILED='Login failed. Invalid credentials.'
CACHE_CONNECTION_FAILED='Failed to connect to cache server.'
```

Environment-specific overrides (`config.env.staging`, `config.env.production`, etc.) are picked up automatically based on `APP_ENV` — see [Configuration](docs/getting-started/configuration.md) for details.

Access configuration from code via `Core\Model\App`:

```php
use Core\Model\App;

$dbHost = App::config('DB_HOST', 'localhost');
if (App::has('API_KEY')) {
    $apiKey = App::config('API_KEY');
}
$config = App::all();
```

---

## Security

- **CSRF protection** — every form includes a token generated by `Session::generateCsrfToken()` and validated by the `csrf` middleware before the request reaches a controller.
- **SQL injection prevention** — always use parameterized queries (`$db->fetchOne("... WHERE email = ?", [$email])`); never concatenate user input into SQL.
- **XSS prevention** — `{{ $var }}` in templates auto-escapes; use `{!! $var !!}` only for trusted HTML.
- **Password security** — `Auth::register()`/`Auth::login()` hash with `password_hash()`/`password_verify()`; passwords are never stored or compared in plaintext.
- **Security headers, rate limiting, and audited dependencies** — see the [security headers middleware](docs/advanced/middleware.md), [rate limiting](#modules), and [`composer audit`](https://getcomposer.org/doc/03-cli.md#audit) (run in CI on every push).

Found a vulnerability? See [SECURITY.md](SECURITY.md) for how to report it responsibly.

---

## Testing

```bash
# Run all tests
php resources/vendor/bin/phpunit

# Run a specific test file
php resources/vendor/bin/phpunit Tests/Unit/CollectionTest.php

# Readable output
php resources/vendor/bin/phpunit --testdox

# Coverage (requires Xdebug or PCOV)
php resources/vendor/bin/phpunit --coverage-html coverage/
```

Three testsuites, registered in `phpunit.xml`:

- **Unit** (`Tests/Unit`) — collections, pagination, validation, query builder SQL compilation, env parsing, the DI container, router/middleware/resource routing in isolation, migrations, the Active Record model, notifications, and helpers.
- **Feature** (`Tests/Feature`) — end-to-end request-lifecycle tests exercising `Router`, `Middleware`, `Request`, and a controller together. See [Tests/README.md](Tests/README.md).
- **Integration** (`Tests/Integration`) — tests against a real external engine (e.g. the query builder against a real SQLite connection) rather than a mock.

Run `composer test` for the current count, or target a suite with `--testsuite Unit`/`Feature`/`Integration`. CI runs the full matrix against PHP 8.1, 8.2, and 8.3 with a real MySQL service; static analysis runs via `composer analyse` (PHPStan level 5).

---

## CLI Tools

```bash
php console help
php console cache:clear
php console queue:work
php console queue:work emails --timeout=60

php console make:controller UserController
php console make:model User
php console make:middleware AdminMiddleware
php console make:migration create_users_table

php console migrate
php console migrate:rollback
php console db:seed "System\Model\UserSeeder"

php console config:cache
php console serve --port=8000
```

Register your own commands:

```php
use Core\Model\Console;

$console = new Console();

$console->register('db:seed', function ($args, $options) use ($console, $db) {
    $console->info('Seeding database...');
    // seed logic
    $console->success('Database seeded successfully!');
});
```

---

## Documentation

- **[Tutorial: Build a Task Tracker](docs/tutorial/README.md)** — a step-by-step walkthrough of one app (routing, migrations/models, validation, views, auth/middleware, tests), the best starting point if you're new to the framework.
- **[Guides](docs/README.md)** — longer-form guides per subsystem: [Routing](docs/fundamentals/routing.md), [Controllers](docs/fundamentals/controllers.md), [Models](docs/fundamentals/models.md), [Views](docs/fundamentals/views.md), [Middleware](docs/advanced/middleware.md), [Caching](docs/advanced/caching.md), and more.
- **API reference** — generate class-by-class docs from the PHPDoc-annotated source with [phpDocumentor](https://phpdoc.org/):
  ```bash
  ./generate-docs.sh
  ```
  then open `Documentation/index.html`.
- **[Tests/README.md](Tests/README.md)** — how the test suite is organized and how to write new tests.

---

## Project Structure

```
framework/
├── Configuration/          # Config files (config.env, error.env, ...)
├── Core/Model/              # Framework internals — Router, Middleware, Cache,
│                            # Database, Auth, Session, Template, Queue, Event, ...
├── System/Controller/       # Built-in controllers (e.g. AuthController)
├── routes/                  # Route definitions (web.php, api.php)
├── database/migrations/     # Schema migrations
├── Tests/                   # Unit, Feature, and Integration test suites
├── resources/
│   ├── lang/                # Language files
│   └── vendor/               # Composer packages (non-default vendor dir)
├── docs/                    # Guides and the step-by-step tutorial
├── storage/                  # Logs, cache, file storage
├── views/                    # Templates
├── composer.json
├── phpunit.xml
└── console                   # CLI entry point
```

---

## Contributing

Contributions are welcome:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing`)
3. Commit your changes (`git commit -m 'Add feature'`)
4. Push to the branch (`git push origin feature/amazing`)
5. Open a Pull Request

Follow PSR-12 coding standards, add PHPDoc to public APIs, and include tests for new behavior — see [CONTRIBUTING.md](CONTRIBUTING.md) for the full guide and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) for community expectations.

---

## License

OmnioPHP is open-source software licensed under the [GPL-3.0-or-later](LICENSE) license.

---

## Author & Support

Built by [Suman Banerjee](https://www.isumanbanerjee.com) ([@isumanbanerjee](https://github.com/isumanbanerjee)), maintained by [AnteOmnio](https://www.anteomnio.com).

- **Bugs & feature requests:** [open an issue](https://github.com/isumanbanerjee/framework/issues)
- **Questions:** [contact@isumanbanerjee.com](mailto:contact@isumanbanerjee.com)
- **Security issues:** see [SECURITY.md](SECURITY.md)
