<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { background: #1e293b; border-radius: 0.5rem; padding: 2rem; width: 100%; max-width: 24rem; }
        .auth-card h1 { margin-top: 0; font-size: 1.5rem; }
        .auth-card label { display: block; margin-top: 1rem; font-size: 0.875rem; color: #94a3b8; }
        .auth-card input { display: block; width: 100%; box-sizing: border-box; margin-top: 0.25rem; padding: 0.5rem; border-radius: 0.25rem; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; }
        .auth-card button { margin-top: 1.5rem; width: 100%; padding: 0.6rem; border: none; border-radius: 0.25rem; background: #38bdf8; color: #0f172a; font-weight: 600; cursor: pointer; }
        .auth-card p { margin-bottom: 0; margin-top: 1rem; font-size: 0.875rem; }
        a { color: #38bdf8; text-decoration: none; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
