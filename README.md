# OmnioPHP

**An enterprise-grade PHP 8.4 framework with 23 built-in components for building scalable, production-ready applications**

[![PHP Version](https://img.shields.io/badge/phil-8.4%2B-777BB4?style=flat&logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL--3.0-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-188%20Total-brightgreen.svg)]()
[![Coverage](https://img.shields.io/badge/Coverage-95%25-success.svg)]()

**Maintained by:** [AnteOmnio](https://www.anteomnio.com)  
**Created by:** [Suman Banerjee](https://www.isumanbanerjee.com)

---

## 🎯 What is OmnioPHP?

OmnioPHP is a comprehensive PHP framework that brings together **everything you need** to ship production applications—from caching and queues to templates, events, storage, and more. Built on PHP 8.4+, it combines the simplicity of lightweight frameworks with the power of enterprise-grade features.

### Why OmnioPHP?

- **🚀 Complete Stack** - 23 components covering all common app needs
- **⚡ High Performance** - Multi-layer caching, queue system, optimized database access
- **🔒 Security First** - CSRF, XSS protection, rate limiting, secure sessions
- **📦 All-in-One** - No need to hunt for packages—it's already included
- **🧪 100% Tested Core** - All critical components have full test coverage
- **📚 Well Documented** - PHPDoc comments + extensive guides

---

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Enterprise Modules](#-enterprise-modules)
- [Core Components](#-core-components)
- [Configuration](#-configuration)
- [Security](#-security)
- [Testing](#-testing)
- [CLI Tools](#-cli-tools)
- [Documentation](#-documentation)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### **Enterprise Modules** (13 Modules)

| Module | Description |
|--------|-------------|
| **Cache** | Redis, Memcached, File, APCu drivers with atomic operations |
| **Middleware** | Pipeline architecture with 8 built-in middleware |
| **Templates** | Blade-like syntax with inheritance and caching |
| **Queue/Jobs** | Background processing with retries and delayed execution |
| **Events** | Event-driven architecture with listeners and wildcards |
| **Storage** | Unified file storage (Local, S3, FTP) with streaming |
| **Mail** | SMTP with templates and queue integration |
| **HTTP Client** | Modern API client for external requests |
| **Collections** | Fluent array manipulation (25+ chainable methods) |
| **Pagination** | Database pagination with Bootstrap styling |
| **CLI Console** | Command-line interface with custom commands |
| **Rate Limiting** | Advanced throttling with multiple strategies |
| **Localization** | Multi-language support with placeholders |

### **Core Components** (10 Modules)

- **Session Management** - Secure sessions with CSRF protection
- **Authentication** - Login, registration, remember me
- **Request/Response** - Clean HTTP abstractions
- **Routing** - Dynamic routes with parameter extraction
- **Database** - PDO-based with query builder, transactions, sharding
- **Validation** - 17+ validation rules
- **Error Management** - Centralized error handling (120+ error codes)
- **Logging** - Multi-level logging with rotation
- **Configuration** - Environment-based config via `.env` files
- **EnvFileParser** - Parse `.env`-style configuration files

---

## 🔧 Requirements

### Minimum Requirements
- **PHP 8.4 or higher**
- **Composer** for dependency management
- **Web Server** (Apache/Nginx) or PHP built-in server

### Required PHP Extensions
```
curl, fileinfo, openssl, pdo, pdo_mysql, mbstring, json, session
```

### Optional Extensions
```
xdebug (development), opcache (production), redis, memcached
```

---

## 📦 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/isumanbanerjee/omniophp.git
cd omniophp
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
# Copy configuration templates
cp Configuration/config.env.example Configuration/config.env
cp Configuration/error.env.example Configuration/error.env

# Edit with your settings
nano Configuration/config.env
```

### 4. Set Permissions

```bash
chmod -R 755 .
chmod -R 777 storage/logs storage/cache
```

### 5. Verify Setup

```bash
./verify-setup.sh
```

---

## 🚀 Quick Start

### Hello World Example

Create `index.php`:

```php
<?php
require_once 'resources/vendor/autoload.php';

use Core\Model\{Request, Response, Router};

$request = new Request();
$response = new Response();
$router = new Router($request, $response);

$router->get('/', function($req, $res) {
    $res->html('<h1>Welcome to OmnioPHP!</h1>');
});

$router->get('/api/status', function($req, $res) {
    $res->json(['status' => 'ok', 'framework' => 'OmnioPHP']);
});

$router->resolve();
```

### With Middleware Protection

```php
use Core\Model\Middleware;

$middleware = new Middleware($request, $response);

$middleware->handle(['auth', 'csrf'], function($req, $res) use ($router) {
    $router->get('/dashboard', function($req, $res) {
        $res->json(['user' => 'authenticated']);
    });
    $router->resolve();
});
```

### Using Enterprise Features

```php
// Cache
use Core\Model\Cache;
$cache = new Cache('redis');
$cache->remember('users', 3600, fn() => $db->fetchAll("SELECT * FROM users"));

// Events
use Core\Model\Event;
Event::listen('user.registered', fn($user) => Mail::send($user->email, 'Welcome!'));
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
```

---

## 🏗️ Enterprise Modules

### 1. Cache System

Multi-driver caching with Redis, Memcached, File, and APCu support.

```php
use Core\Model\Cache;

$cache = new Cache('redis', ['host' => '127.0.0.1', 'port' => 6379]);

// Basic operations
$cache->put('key', 'value', 3600);
$value = $cache->get('key', 'default');
$cache->forget('key');

// Remember pattern
$data = $cache->remember('expensive-query', 3600, function() use ($db) {
    return $db->fetchAll("SELECT * FROM large_table");
});

// Atomic operations
$cache->increment('page_views');
$cache->decrement('stock', 5);

// Bulk operations
$cache->putMany(['key1' => 'val1', 'key2' => 'val2'], 3600);
```

**Configuration** (`config.env`):
```env
CACHE_DRIVER=redis
CACHE_REDIS_HOST=127.0.0.1
CACHE_REDIS_PORT=6379
CACHE_DEFAULT_TTL=3600
```

---

### 2. Middleware System

Request/response pipeline with 8 built-in middleware.

```php
use Core\Model\Middleware;

$middleware = new Middleware($request, $response);

// Single middleware
$middleware->handle(['auth'], function($req, $res) {
    // Protected route
});

// Multiple middleware
$middleware->handle(['auth', 'csrf', 'admin'], function($req, $res) {
    // Admin-only route
});

// Middleware groups
$middleware->handle('api', function($req, $res) {
    // Group: throttle, json, cors, log
});
```

**Built-in Middleware:**
- `auth` - Require authentication
- `guest` - Redirect authenticated users
- `csrf` - CSRF token validation
- `cors` - CORS headers
- `throttle` - Rate limiting
- `admin` - Require admin role
- `json` - Force JSON content-type
- `log` - Request logging

---

### 3. Template Engine

Blade-like template system with inheritance and caching.

```php
use Core\Model\Template;

$template = new Template('views');
echo $template->render('welcome', ['name' => 'John', 'items' => $items]);
```

**Template Syntax** (`views/welcome.php`):
```blade
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

**Features:**
- Blade-like directives (`@if`, `@foreach`, `@extends`, `@section`)
- Template inheritance
- Automatic XSS protection
- Template caching
- Custom directives

---

### 4. Queue/Job System

Background job processing with multiple drivers.

```php
use Core\Model\Queue;

$queue = new Queue('database');

// Push job immediately
$queue->push(SendEmailJob::class, ['to' => 'user@example.com', 'subject' => 'Hello']);

// Delayed execution (60 seconds)
$queue->later(60, ProcessVideoJob::class, ['video_id' => 123]);

// Start worker
$queue->work();
```

**Job Class:**
```php
class SendEmailJob
{
    public function handle(array $data)
    {
        $mail = new Mail();
        $mail->send($data['to'], $data['subject'], $data['body']);
    }
}
```

**Features:**
- Database, Redis, File drivers
- Delayed execution
- Automatic retries
- Worker management
- Failed job tracking

---

### 5. Event System

Event-driven architecture with listeners and subscribers.

```php
use Core\Model\Event;

// Register listener
Event::listen('user.registered', function($user) {
    Mail::send($user->email, 'Welcome!', 'emails.welcome');
    Log::info("User registered: {$user->email}");
});

// Fire event
Event::fire('user.registered', $user);

// Wildcard listeners
Event::listen('user.*', function($payload, $event) {
    Log::info("User event: $event");
});

// Queue event for background processing
Event::queue('send.newsletter', $subscribers);
```

---

### 6. File Storage

Unified file storage for Local, S3, and FTP.

```php
use Core\Model\Storage;

$storage = new Storage('local', ['root' => 'storage/app']);

// Store file
$storage->put('documents/readme.txt', 'File contents');

// Upload from form
$path = $storage->putFile('avatar', 'users/avatars');

// Get file
$contents = $storage->get('documents/readme.txt');

// File operations
$storage->copy('old.txt', 'new.txt');
$storage->move('temp.txt', 'archived/temp.txt');
$storage->delete('old-file.txt');

// Generate URLs
$url = $storage->url('images/logo.png');
$tempUrl = $storage->temporaryUrl('private/document.pdf', 3600);
```

**Configuration:**
```env
STORAGE_DRIVER=local
STORAGE_ROOT=storage/app
STORAGE_MAX_FILE_SIZE=10485760
STORAGE_ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,pdf,doc,docx
```

---

### 7. Mail System

SMTP email sending with templates and queue integration.

```php
use Core\Model\Mail;

$mail = new Mail([
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your@gmail.com',
    'password' => 'app-password',
    'encryption' => 'tls'
]);

// Send email
$mail->send('user@example.com', 'Welcome!', '<h1>Welcome to OmnioPHP</h1>');

// Using template
$mail->sendTemplate('user@example.com', 'Welcome', 'emails.welcome', [
    'name' => 'John',
    'link' => 'https://example.com/verify'
]);

// Queue email for background sending
$mail->queue('user@example.com', 'Newsletter', $htmlBody);
```

---

### 8. HTTP Client

Modern HTTP client for API requests.

```php
use Core\Model\Http;

// GET request
$response = Http::get('https://api.example.com/users');
$users = $response->json();

// POST request
$response = Http::post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// With headers
$response = Http::get('https://api.example.com/protected', [], [
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json'
]);

// Check response
if ($response->successful()) {
    $data = $response->json();
} else {
    $error = $response->body();
}
```

---

### 9. Collections

Fluent array manipulation with 25+ chainable methods.

```php
use Core\Model\Collection;

$collection = collect([1, 2, 3, 4, 5]);

// Method chaining
$result = $collection
    ->filter(fn($n) => $n > 2)
    ->map(fn($n) => $n * 2)
    ->sum();  // 24

// Working with objects
$activeUsers = collect($users)
    ->where('active', true)
    ->pluck('email')
    ->unique()
    ->all();

// Aggregations
$total = collect($orders)->sum('amount');
$average = collect($scores)->avg();
$highest = collect($prices)->max();
```

**Available Methods:**
`map`, `filter`, `where`, `pluck`, `unique`, `sort`, `reverse`, `chunk`, `take`, `skip`, `sum`, `avg`, `min`, `max`, `groupBy`, `first`, `last`, `isEmpty`, `count`, `toJson`, and more!

---

### 10. Pagination

Database pagination with Bootstrap 5 styling.

```php
use Core\Model\Pagination;

// Create paginator
$paginator = new Pagination($items, $totalCount, 15, $currentPage);

// Display items
foreach ($paginator->items() as $item) {
    echo $item->name;
}

// Render pagination links
echo $paginator->links();

// JSON output for APIs
$json = $paginator->toJson();
```

**Features:**
- Bootstrap 5 styled links
- Custom page sizes
- URL generation
- JSON output
- Metadata (total, per_page, current_page, last_page)

---

### 11. CLI Console

Command-line interface with custom commands.

```bash
# Built-in commands
php console help
php console cache:clear
php console queue:work
php console queue:work emails --timeout=60
php console make:controller UserController
php console make:model User
php console serve --port=8000
```

**Custom Commands:**
```php
use Core\Model\Console;

$console = new Console();

$console->register('greet', function($args, $options) use ($console) {
    $name = $args[0] ?? 'World';
    $console->info("Hello, $name!");
    $console->success("Command completed!");
});

// Usage: php console greet John
```

---

### 12. Rate Limiting

Advanced rate limiting with multiple strategies.

```php
use Core\Model\RateLimit;

$limiter = RateLimit::for('api');  // 60 attempts per minute

$key = 'api:' . $request->ip();

if ($limiter->tooManyAttempts($key)) {
    $seconds = $limiter->availableIn($key);
    $response->json([
        'error' => "Too many requests. Try again in $seconds seconds."
    ], 429);
    return;
}

$limiter->hit($key);
$remaining = $limiter->retriesLeft($key);

// Add to response headers
$response->setHeader('X-RateLimit-Remaining', $remaining);
```

**Predefined Limits:**
- `api` - 60 requests per minute
- `login` - 5 attempts per minute
- `global` - 1000 requests per minute

---

### 13. Localization (i18n)

Multi-language support with placeholders.

```php
use Core\Model\Lang;

// Set locale
Lang::setLocale('es');

// Get translation
echo Lang::get('welcome');  // "Bienvenido"
echo __('auth.failed');     // Helper function

// With replacements
echo __('messages.greeting', ['name' => 'John']);
// Output: "Hola, John!" (if Spanish is active)
```

**Language Files** (`resources/lang/en.php`):
```php
return [
    'welcome' => 'Welcome',
    'auth' => [
        'failed' => 'These credentials do not match our records.',
        'throttle' => 'Too many login attempts.'
    ],
    'messages' => [
        'greeting' => 'Hello, :name!'
    ]
];
```

---

## 🧩 Core Components

### Session Management

```php
use Core\Model\Session;

$session = new Session();

// Set/Get values
$session->set('user_id', 123);
$userId = $session->get('user_id');

// Flash messages (one-time display)
$session->setFlash('success', 'User created successfully!');
$message = $session->getFlash('success');

// CSRF Protection
$token = $session->generateCsrfToken();
if ($session->validateCsrfToken($_POST['csrf_token'])) {
    // Valid request
}
```

**Security Features:**
- HTTPOnly cookies
- Secure cookies (HTTPS)
- SameSite=Strict
- Session regeneration on login

---

### Authentication

```php
use Core\Model\Auth;

$auth = new Auth($db, $session);

// Login
if ($auth->login('user@example.com', 'password', $rememberMe = true)) {
    // Success
}

// Register
$userId = $auth->register([
    'email' => 'new@example.com',
    'password' => 'secure123',
    'name' => 'John Doe'
]);

// Check authentication
if ($auth->check()) {
    $user = $auth->user();
    $userId = $auth->id();
}

// Logout
$auth->logout();
```

---

### Routing

```php
use Core\Model\Router;

$router = new Router($request, $response);

// Static routes
$router->get('/', fn($req, $res) => $res->html('Home'));
$router->post('/users', fn($req, $res) => $res->json(['created' => true]));

// Dynamic routes with parameters
$router->get('/users/{id}', function($req, $res, $id) {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    $res->json($user);
});

// Multiple parameters
$router->get('/posts/{postId}/comments/{commentId}', 
    fn($req, $res, $postId, $commentId) => /* ... */
);

$router->resolve();
```

---

### Database

```php
use Core\Model\Database\Database;

$db = new Database($logger);

// Fetch data
$users = $db->fetchAll("SELECT * FROM users WHERE active = ?", [1]);
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [123]);

// Execute queries
$db->executeQuery("UPDATE users SET status = ? WHERE id = ?", ['active', 123]);

// Transactions
$db->beginTransaction();
try {
    $db->executeQuery("INSERT INTO ...");
    $db->executeQuery("UPDATE ...");
    $db->commitTransaction();
} catch (Exception $e) {
    $db->rollbackTransaction();
}

// Query Builder
$users = $db->queryBuilder()
    ->select('*')
    ->from('users')
    ->where('active = ?', [1])
    ->orderBy('created_at DESC')
    ->limit(10)
    ->fetchAll();
```

---

### Validation

```php
use Core\Model\Validation;

$validation = new Validation($db);

$rules = [
    'name' => 'required|min:2|max:100|alpha',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8',
    'age' => 'numeric'
];

if ($validation->make($_POST, $rules)) {
    // Valid
} else {
    $errors = $validation->errors();
    $firstError = $validation->firstError('email');
}
```

**Available Rules:**
`required`, `email`, `min`, `max`, `numeric`, `alpha`, `alphanumeric`, `match`, `unique`, `url`

---

## ⚙️ Configuration

### Configuration Files

All configuration is centralized in `.env` files:

**`Configuration/config.env`** - Application settings
```env
# Database
DB_TYPE=mysql
DB_HOST=localhost
DB_NAME=omniophp
DB_USERNAME=root
DB_PASSWORD=secret

# Cache
CACHE_DRIVER=redis
CACHE_REDIS_HOST=127.0.0.1
CACHE_DEFAULT_TTL=3600

# Application
APP_NAME=OmnioPHP
APP_ENV=production
APP_DEBUG=false
```

**`Configuration/error.env`** - Error messages
```env
DATABASE_CONNECTION_FAILED='Database connection failed.'
AUTH_LOGIN_FAILED='Login failed. Invalid credentials.'
CACHE_CONNECTION_FAILED='Failed to connect to cache server.'
```

### Accessing Configuration

```php
use Core\Model\App;

// Get config value with default
$dbHost = App::config('DB_HOST', 'localhost');

// Check if exists
if (App::has('API_KEY')) {
    $apiKey = App::config('API_KEY');
}

// Get all config
$config = App::all();
```

---

## 🔐 Security

### 1. CSRF Protection

```php
// In form
<form method="POST">
    <input type="hidden" name="csrf_token" 
           value="<?php echo $session->generateCsrfToken(); ?>">
</form>

// Validate
if (!$session->validateCsrfToken($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

### 2. SQL Injection Prevention

```php
// ✅ GOOD - Use prepared statements
$user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

// ❌ BAD - Never concatenate
// $user = $db->fetchOne("SELECT * FROM users WHERE email = '$email'");
```

### 3. XSS Prevention

```php
// Escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Templates auto-escape
{{ $userInput }}  // Auto-escaped
{!! $trustedHtml !!}  // Raw output
```

### 4. Password Security

```php
// Automatic bcrypt hashing
$auth->register(['email' => $email, 'password' => $password]);

// Secure verification
$auth->login($email, $password);  // Uses password_verify()
```

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php resources/vendor/bin/phpunit

# Run specific test
php resources/vendor/bin/phpunit Tests/Unit/CacheTest.php

# With readable output
php resources/vendor/bin/phpunit --testdox

# Generate coverage
php resources/vendor/bin/phpunit --coverage-html coverage/
```

### Test Statistics

```
Total Tests: 188+
Core Tests: 58/58 (100%) ✅
Enterprise Tests: 130+ ✅
Overall Coverage: 95%

Components:
├─ Cache: 14 tests ✅
├─ Template: 12 tests ✅
├─ Collection: 35 tests ✅
├─ Session: 11/11 ✅
├─ Validation: 16/16 ✅
└─ Database: 13/13 ✅
```

### Writing Tests

```php
use PHPUnit\Framework\TestCase;
use Core\Model\Cache;

class CacheTest extends TestCase
{
    public function testCacheStoresAndRetrievesData(): void
    {
        $cache = new Cache('file');
        $cache->put('test', 'value', 60);
        
        $this->assertEquals('value', $cache->get('test'));
    }
}
```

---

## 🖥️ CLI Tools

### Built-in Commands

```bash
# Help
php console help

# Cache
php console cache:clear

# Queue
php console queue:work
php console queue:work emails --timeout=60

# Generators
php console make:controller UserController
php console make:model User
php console make:middleware AdminMiddleware

# Development Server
php console serve
php console serve --port=8000
```

### Custom Commands

```php
use Core\Model\Console;

$console = new Console();

$console->register('db:seed', function($args, $options) use ($console, $db) {
    $console->info('Seeding database...');
    
    // Seed logic here
    
    $console->success('Database seeded successfully!');
});
```

---

## 📖 Documentation

### API Documentation

Generate with PhpDocumentor:

```bash
./generate-docs.sh
```

View at: `Documentation/index.html`

### Additional Resources

- **Test Documentation**: `Tests/README.md`
- **Error Reference**: `Configuration/error.env`
- **Example Apps**: `examples/` directory

---

## 📁 Project Structure

```
omniophp/
├── Configuration/          # Config files
│   ├── config.env         # App configuration
│   └── error.env          # Error messages
├── Core/Model/            # Framework core
│   ├── Cache.php          # ⭐ Cache system
│   ├── Middleware.php     # ⭐ Middleware
│   ├── Template.php       # ⭐ Template engine
│   ├── Queue.php          # ⭐ Queue/Jobs
│   ├── Event.php          # ⭐ Events
│   ├── Storage.php        # ⭐ File storage
│   ├── Mail.php           # ⭐ Mail
│   ├── Http.php           # ⭐ HTTP client
│   ├── Collection.php     # ⭐ Collections
│   ├── Pagination.php     # ⭐ Pagination
│   ├── Console.php        # ⭐ CLI
│   ├── RateLimit.php      # ⭐ Rate limiting
│   ├── Session.php        # Sessions
│   ├── Auth.php           # Authentication
│   ├── Router.php         # Routing
│   ├── Validation.php     # Validation
│   └── Database/          # Database layer
├── Tests/                 # Test suite
│   ├── Unit/             # Unit tests
│   ├── Integration/      # Integration tests
│   └── Feature/          # Feature tests
├── resources/
│   ├── lang/             # Language files
│   └── vendor/           # Composer packages
├── storage/              # File storage
├── views/                # Templates
├── composer.json         # Dependencies
├── phpunit.xml           # Test config
└── console              # CLI entry point
```

⭐ = Enterprise Module

---

## 🤝 Contributing

We welcome contributions! Here's how:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing`)
3. **Commit** your changes (`git commit -m 'Add feature'`)
4. **Push** to the branch (`git push origin feature/amazing`)
5. **Open** a Pull Request

### Guidelines

- Follow **PSR-12** coding standards
- Add **PHPDoc** comments
- Write **tests** for new features
- Update **documentation**

---

## 📄 License

OmnioPHP is open-source software licensed under the **GPL-3.0-or-later** license.

```
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
```

See [LICENSE](LICENSE) for full details.

---

## 🏢 About

### Company

**[AnteOmnio](https://www.anteomnio.com)**  
Building enterprise-grade software solutions

### Creator

**[Suman Banerjee](https://www.isumanbanerjee.com)**  
Full-stack developer and framework architect

- 🌐 Website: [isumanbanerjee.com](https://www.isumanbanerjee.com)
- 📧 Email: [contact@isumanbanerjee.com](mailto:contact@isumanbanerjee.com)
- 💼 GitHub: [@isumanbanerjee](https://github.com/isumanbanerjee)

---

## 📊 Project Stats

- **Lines of Code**: 19,000+
- **Components**: 23 (10 core + 13 enterprise)
- **Test Cases**: 188+
- **Test Coverage**: 95%
- **PHP Version**: 8.4+
- **Documentation**: 2,000+ lines

---

## 🙏 Acknowledgments

- PHP Community for excellent documentation
- PHPUnit team for robust testing framework
- All contributors and users of OmnioPHP
- Open source community for inspiration

---

## 📞 Support

Need help? We're here for you:

1. 📚 Check the [Documentation](Documentation/index.html)
2. 🧪 Review the [Tests](Tests/) for examples
3. 🐛 Open an [Issue](https://github.com/isumanbanerjee/omniophp/issues)
4. 💬 Contact: [contact@isumanbanerjee.com](mailto:contact@isumanbanerjee.com)
5. 🌐 Visit: [AnteOmnio](https://www.anteomnio.com)

---

## 🎉 Ready to Build?

```bash
# Install OmnioPHP
git clone https://github.com/isumanbanerjee/omniophp.git
cd omniophp
composer install

# Start building
php console serve
```

**Build amazing applications with OmnioPHP!**

---

*Built with ❤️ by [AnteOmnio](https://www.anteomnio.com) • Created by [Suman Banerjee](https://www.isumanbanerjee.com)*

*Last Updated: December 24, 2025*
