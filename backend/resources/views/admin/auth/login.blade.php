<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول - {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f6f7f9;
            color: #1f2937;
            font-family: Tahoma, Arial, sans-serif;
        }
        .login {
            width: min(420px, calc(100vw - 32px));
            background: #ffffff;
            border: 1px solid #d9dee7;
            border-radius: 8px;
            padding: 24px;
        }
        h1 { margin: 0 0 6px; font-size: 24px; letter-spacing: 0; }
        p { margin: 0 0 20px; color: #667085; }
        label { display: block; margin-bottom: 6px; font-weight: 700; }
        input {
            width: 100%;
            min-height: 42px;
            border: 1px solid #cfd6df;
            border-radius: 6px;
            padding: 9px 10px;
            font: inherit;
        }
        .field { margin-bottom: 14px; }
        .check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
        }
        .check input { width: auto; min-height: auto; }
        button {
            width: 100%;
            min-height: 42px;
            border: 0;
            border-radius: 6px;
            background: #0f766e;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
        }
        .error {
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 6px;
            background: #fef3f2;
            color: #b42318;
        }
    </style>
</head>
<body>
    <form class="login" method="post" action="{{ route('admin.login.store') }}">
        @csrf
        <h1>لوحة التحكم</h1>
        <p>{{ config('app.name') }}</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <div class="field">
            <label for="email">البريد الإلكتروني</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="field">
            <label for="password">كلمة المرور</label>
            <input id="password" name="password" type="password" required>
        </div>

        <label class="check">
            <input name="remember" type="checkbox" value="1">
            <span>تذكرني</span>
        </label>

        <button type="submit">دخول</button>
    </form>
</body>
</html>
