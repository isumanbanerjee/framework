<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 2rem; }
        a { color: #38bdf8; text-decoration: none; }
    </style>
</head>
<body>
    @include('components.header')

    <main>
        @yield('content')
    </main>

    @include('components.footer')
</body>
</html>
