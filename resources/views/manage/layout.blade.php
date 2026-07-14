<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Catalog') — FutureBD Manage</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; background:#0b1020; color:#e5e7eb; }
        a { color:#60a5fa; text-decoration:none; }
        header { padding: 16px 24px; background:#111827; border-bottom:1px solid #1f2937; display:flex; justify-content:space-between; align-items:center; }
        header .brand { font-size:16px; font-weight:700; }
        header nav a { margin-left:16px; font-size:13px; color:#9ca3af; }
        header nav a:hover { color:#e5e7eb; }
        main { max-width: 1080px; margin: 0 auto; padding: 24px; }
        h1 { font-size:20px; margin:0 0 18px; }
        .card { background:#111827; border:1px solid #1f2937; border-radius:12px; padding:20px; margin-bottom:20px; }
        label { display:block; font-size:12px; color:#9ca3af; margin-bottom:5px; }
        input, select, textarea { width:100%; padding:8px 10px; border-radius:8px; border:1px solid #374151; background:#0b1020; color:#e5e7eb; font-size:13px; font-family:inherit; }
        textarea { min-height:70px; resize:vertical; }
        .grid { display:grid; gap:14px; }
        .g2 { grid-template-columns: 1fr 1fr; }
        .g3 { grid-template-columns: 1fr 1fr 1fr; }
        .field { margin-bottom:14px; }
        button, .btn { cursor:pointer; border:0; border-radius:8px; padding:9px 16px; font-size:13px; font-weight:600; display:inline-block; }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-ghost { background:#1f2937; color:#e5e7eb; }
        .btn-danger { background:#7f1d1d; color:#fee2e2; }
        .btn-sm { padding:5px 11px; font-size:12px; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { text-align:left; padding:9px 10px; border-bottom:1px solid #1f2937; vertical-align:middle; }
        th { color:#9ca3af; font-weight:600; font-size:12px; }
        .pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:11px; font-weight:600; }
        .ok { background:#064e3b; color:#6ee7b7; } .low { background:#78350f; color:#fcd34d; } .out { background:#7f1d1d; color:#fca5a5; }
        .muted { color:#6b7280; font-size:12px; }
        .swatch { display:inline-block; width:12px; height:12px; border-radius:3px; vertical-align:middle; margin-right:5px; border:1px solid #00000055; }
        .flash { background:#064e3b; color:#a7f3d0; padding:11px 15px; border-radius:8px; margin-bottom:18px; font-size:13px; }
        .errbox { background:#7f1d1d; color:#fecaca; padding:11px 15px; border-radius:8px; margin-bottom:18px; font-size:13px; }
        .vrow { display:grid; grid-template-columns: .9fr .9fr .7fr 1.5fr .8fr .8fr .7fr .9fr auto; gap:7px; align-items:end; margin-bottom:8px; }
        .vrow label { font-size:10px; }
        .toolbar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:8px; }
    </style>
</head>
<body>
<header>
    <div class="brand">📦 FutureBD · Catalog Manager</div>
    <nav>
        <a href="{{ route('manage.products.index') }}">Products</a>
        <a href="{{ route('manage.taxonomy.index') }}">Categories &amp; Brands</a>
        <a href="{{ route('dashboard') }}">← Dashboard</a>
    </nav>
</header>
<main>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="errbox">
            <strong>Please fix:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
