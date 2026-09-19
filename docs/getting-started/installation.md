# Installation

## Requirements

- PHP 8.1 or higher
- Composer
- A web server (Apache with `mod_rewrite`, or Nginx) or the PHP built-in server
- Required extensions: `curl`, `fileinfo`, `openssl`, `pdo`, `mbstring`, `json`, `session`

## 1. Clone and install dependencies

```bash
git clone <your-repository-url>
cd framework
composer install
```

Composer installs into `resources/vendor` (see `config.vendor-dir` in `composer.json`), not the usual `vendor/` directory.

## 2. Configure the environment

```bash
cp Configuration/config.env.example Configuration/config.env
```

Edit `Configuration/config.env` with your database credentials and application settings. For running the test suite, `Configuration/config.env.testing.example` provides a ready-to-use SQLite configuration.

## 3. Serve the application

### Option A — Docker (recommended for a consistent environment)

```bash
docker compose up -d --build
```

This builds a PHP 8.1 + Apache container with `mod_rewrite` and `AllowOverride All` already configured, and mounts the project at `/var/www/html`. The app is served at `http://localhost:8080`.

### Option B — PHP's built-in server

```bash
php -S localhost:8000 -t . index.php
```

### Option C — Apache/Nginx

Point the document root at the project root (the `index.php` front controller and `.htaccess` live there) and ensure `mod_rewrite` is enabled with `AllowOverride All`.

## 4. Verify

```bash
curl http://localhost:8080/health
# {"status":"healthy","timestamp":...}
```

## Next steps

- [Configuration](configuration.md)
- [Your First Application](first-application.md)
