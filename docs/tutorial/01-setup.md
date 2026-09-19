# 1. Setup

Follow [Installation](../getting-started/installation.md) if you haven't already:

```bash
git clone <your-repository-url>
cd framework
composer install
cp Configuration/config.env.example Configuration/config.env
```

Edit `Configuration/config.env` with a real database connection — the tutorial needs one for the migration in [part 3](03-database-and-models.md). SQLite is the fastest way to follow along locally:

```env
DB_TYPE=sqlite
DB_NAME=storage/database.sqlite
```

Then start the app. With Docker (recommended — matches the environment this tutorial's commands were verified against):

```bash
docker compose up -d --build
curl http://localhost:8080/health
# {"status":"healthy","timestamp":...}
```

Or with PHP's built-in server:

```bash
php -S localhost:8000 -t . index.php
```

## What you're building

A `/tasks` resource: list, create, complete, and delete tasks, gated behind login. Every part below adds one layer — routes, then storage, then validation, then HTML, then auth, then a test — so the app is runnable (if incomplete) after each step.

Next: [Routes & Controllers](02-routes-and-controllers.md).
