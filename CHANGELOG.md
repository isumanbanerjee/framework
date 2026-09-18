# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Docker test environment (PHP 8.1 + Apache with mod_rewrite), front
  controller (`index.php`), and `.htaccess`.
- PHPUnit test suite covering Collection, Pagination, Validation, and the
  QueryBuilder (SQL compilation + SQLite integration).
- `Response::securityHeaders()` / `sendSecurityHeaders()` for hardened
  default HTTP security headers.
- `strongPassword` validation rule.
- Global helper functions (`config()`, `env()`, `e()`) autoloaded via Composer.
- `/health` endpoint and `Configuration/config.env.example` template.
- PSR-11 dependency injection `Container` with constructor autowiring, plus a
  `ServiceProvider` base class.
- PHPStan static analysis (level 5) with a baseline.
- Router enhancements: `put`/`patch`/`delete`/`options`/`any`/`match`, route
  groups with shared prefix and middleware, per-route middleware via a fluent
  `Route` handle, named routes, and `resource()` RESTful registration.
- Database migration system: `Blueprint`/`ColumnDefinition` schema builder,
  `Schema`, `Migration`, `Seeder`, `Migrator`, and `Connection` factory.
- Console generators (`make:controller`/`model`/`middleware`/`migration`) and
  `migrate`, `migrate:rollback`, `db:seed`, `config:cache` commands.
- `ResourceController` base class and `UploadedFile` wrapper.
- Multi-channel notification system (`Notification`, `Notifier`,
  `DatabaseChannel`, `ArrayChannel`).
- Branded error views (404/403/500/503) and a base layout.

### Fixed
- `Error::terminateWithError()` now returns `never`, resolving latent
  unreachable-return warnings across Auth/Database/Session/Template.
- `QueryBuilder::where()` PHPDoc corrected so the documented two-argument
  shorthand (`where('id', 1)`) is accepted.
- `Collection` `offsetGet()`/`current()`/`key()` return types satisfy
  `ArrayAccess`/`Iterator` (removed PHP deprecation notices).
- `Middleware::normalizeMiddleware()` passes closure middleware through
  instead of using it as an array key.

### Removed
- Stray `Configuration/App.php` that declared a duplicate Database class.
