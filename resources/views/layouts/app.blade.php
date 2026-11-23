<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name', 'TradeSim') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root { --bs-font-sans-serif: 'Be Vietnam Pro', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; --bs-primary: #1d5bc4; --bs-primary-rgb: 29, 91, 196; }
        body { background: #eef1f5; }
        .num { font-family: 'JetBrains Mono', ui-monospace, Consolas, monospace; font-variant-numeric: tabular-nums; }
        /* Quy ước màu bảng giá: trần tím, tăng xanh, tham chiếu vàng, giảm đỏ, sàn xanh lam */
        .px-ceil { color: #8c2aa6; } .px-up { color: #0b8a4c; } .px-ref { color: #a07400; }
        .px-down { color: #cf3328; } .px-floor { color: #00829a; }
        .disclaimer { background: #fdf1d2; color: #6b4d00; font-size: .85rem; }
        .table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #5a6779; white-space: nowrap; }
        .table td { vertical-align: middle; }
        .sort-link { color: inherit; text-decoration: none; }
        .sort-link:hover { text-decoration: underline; }
        .navbar-brand .logo { background: #1d5bc4; color: #fff; border-radius: 6px; padding: 2px 6px; font-size: .8rem; margin-right: 6px; }
    </style>
    @stack('styles')
</head>
<body>
    @include('partials.nav')

    <div class="disclaimer py-2">
        <div class="container">{{ config('tradesim.disclaimer') }}</div>
    </div>

    <main class="container py-4">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="container pb-4 text-secondary small">
        TradeSim · Đồ án học phần Phần mềm nguồn mở nâng cao · Mã nguồn phát hành theo giấy phép MIT
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
