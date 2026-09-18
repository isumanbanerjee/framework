# Contributing

Thanks for your interest in improving OmnioPHP. This guide covers how to set
up the project, the standards we follow, and how to submit changes.

## Development Setup

The supported environment is the bundled Docker container (PHP 8.1 + Apache):

```bash
docker compose up -d --build
docker compose exec php-dev81 composer install
```

The application is then served at http://localhost:8080.

## Running the Test Suite

```bash
docker compose exec php-dev81 php resources/vendor/bin/phpunit
docker compose exec php-dev81 php resources/vendor/bin/phpunit --testdox
```

Static analysis:

```bash
docker compose exec php-dev81 php resources/vendor/bin/phpstan analyse --memory-limit=512M
```

Both must pass before a change is merged.

## Coding Standards

- Target PHP 8.1 and declare `strict_types=1` in new files.
- Follow PSR-12 for new code.
- Add complete type hints (parameters, return types, properties).
- Every new class and public method carries a PHPDoc block.
- New behaviour must ship with tests. New code must not introduce PHPStan
  errors above the committed baseline.

## Submitting Changes

1. Create a topic branch off `main`.
2. Make focused commits with clear messages describing the *why*.
3. Ensure the test suite and static analysis pass.
4. Open a pull request describing the change and its motivation.

## Reporting Bugs

Open an issue including: the expected behaviour, the actual behaviour, steps to
reproduce, and your PHP version. A failing test case is the most helpful
reproduction.
