# Asset Pipeline (Vite)

OmnioPHP integrates [Vite](https://vitejs.dev) for bundling frontend
JavaScript and CSS, with a small `Core\Model\Vite` helper (no
`laravel-vite-plugin` dependency) that renders the right `<script>`/`<link>`
tags for both the dev server and a production build.

## Setup

```bash
npm install
```

- `resources/assets/js/app.js` / `resources/assets/css/app.css` — default
  entry points, referenced from `vite.config.js`.
- `vite.config.js` — builds to `assets/build/` with a manifest, and writes
  `assets/build/hot` while the dev server is running (removed on exit).

## Development

```bash
npm run dev
```

Starts the Vite dev server with hot module replacement. While it's running,
`Core\Model\Vite::tags()` detects `assets/build/hot` and points script tags
at the dev server instead of the manifest.

## Production build

```bash
npm run build
```

Writes hashed, versioned assets and `assets/build/manifest.json`.
`Core\Model\Vite::tags()` reads the manifest and resolves entry points to
their built filenames automatically — no manual cache-busting needed.

## Usage in views

```php
<?php $vite = new \Core\Model\Vite(); ?>
<!DOCTYPE html>
<html>
<head>
    <?= $vite->tags('resources/assets/js/app.js', 'resources/assets/css/app.css') ?>
</head>
<body>
</body>
</html>
```

`tags()` accepts any number of entry points declared in `vite.config.js`'s
`build.rollupOptions.input`. Add more entries there (and reference them the
same way) for additional pages or admin-only bundles.

## Serving `assets/build/`

The built files under `assets/build/` need to be reachable at the
`publicBase` path passed to `Vite` (`/assets/build` by default — pass a
second constructor argument to change it). Point your web server's document
root at the project root (as `index.php` already assumes) so `assets/build/`
is served directly; no separate `public/` directory is required.

## Checking dev-server status from PHP

```php
if ((new \Core\Model\Vite())->isRunningHot()) {
    // dev server is up
}
```
