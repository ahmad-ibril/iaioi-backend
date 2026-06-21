<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') - {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #f6f7f9;
            --panel: #ffffff;
            --line: #d9dee7;
            --text: #1f2937;
            --muted: #667085;
            --brand: #0f766e;
            --brand-dark: #115e59;
            --danger: #b42318;
            --danger-bg: #fef3f2;
            --success: #027a48;
            --success-bg: #ecfdf3;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Tahoma, Arial, sans-serif;
            font-size: 14px;
        }
        a { color: inherit; text-decoration: none; }
        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px 1fr;
        }
        .sidebar {
            background: #111827;
            color: #f9fafb;
            padding: 20px 18px;
        }
        .brand {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .nav a,
        .logout-button {
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 6px;
            color: #d1d5db;
            background: transparent;
            border: 0;
            cursor: pointer;
            font: inherit;
            text-align: right;
        }
        .nav a:hover,
        .logout-button:hover,
        .nav a.active {
            background: #1f2937;
            color: #ffffff;
        }
        .main {
            min-width: 0;
            padding: 24px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 0;
        }
        .muted { color: var(--muted); }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 18px;
        }
        .grid {
            display: grid;
            gap: 14px;
        }
        .grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stat {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 16px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 12px;
            margin-bottom: 14px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .field.full { grid-column: 1 / -1; }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
        }
        input,
        textarea,
        select {
            width: 100%;
            min-height: 40px;
            border: 1px solid #cfd6df;
            border-radius: 6px;
            padding: 9px 10px;
            font: inherit;
            background: #ffffff;
        }
        textarea { min-height: 96px; resize: vertical; }
        .check-row {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
        }
        .check-row input { width: auto; min-height: auto; }
        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 13px;
            border-radius: 6px;
            border: 1px solid transparent;
            background: var(--brand);
            color: #ffffff;
            cursor: pointer;
            font: inherit;
        }
        .button:hover { background: var(--brand-dark); }
        .button.secondary {
            background: #ffffff;
            color: var(--text);
            border-color: var(--line);
        }
        .button.danger {
            background: var(--danger);
            color: #ffffff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th,
        td {
            padding: 11px 10px;
            border-bottom: 1px solid var(--line);
            text-align: right;
            vertical-align: top;
        }
        th {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            background: #f8fafc;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 3px 8px;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
            font-size: 12px;
            font-weight: 700;
        }
        .badge.green { background: var(--success-bg); color: var(--success); }
        .badge.gray { background: #f2f4f7; color: #344054; }
        .alert {
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 14px;
            border: 1px solid var(--line);
        }
        .alert.success {
            background: var(--success-bg);
            color: var(--success);
            border-color: #abefc6;
        }
        .alert.error {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: #fecdca;
        }
        .pagination { margin-top: 14px; }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .grid.cols-3,
            .grid.cols-2,
            .form-grid { grid-template-columns: 1fr; }
            .toolbar,
            .topbar { align-items: stretch; flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">{{ config('app.name') }}</div>
            <nav class="nav">
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>لوحة التحكم</a>
                <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>الأقسام والفلاتر</a>
                <a href="{{ route('admin.listings.index') }}" @class(['active' => request()->routeIs('admin.listings.*')])>الخدمات والمنشآت</a>
                <a href="{{ route('admin.booking-requests.index') }}" @class(['active' => request()->routeIs('admin.booking-requests.*')])>طلبات الحجز</a>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="logout-button" type="submit">تسجيل الخروج</button>
                </form>
            </nav>
        </aside>

        <main class="main">
            <div class="topbar">
                <div>
                    <h1>@yield('title', 'لوحة التحكم')</h1>
                    @hasSection('subtitle')
                        <div class="muted">@yield('subtitle')</div>
                    @endif
                </div>
                @yield('actions')
            </div>

            @include('admin.partials.flash')
            @yield('content')
        </main>
    </div>
</body>
</html>
