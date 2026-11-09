<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Janeta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-page { background: #08090C; color: #f8fafc; }
        .login-card { width: min(100%, 320px); }
        .login-input { border: 1px solid #334155; background: #0f172a; color: #f8fafc; }
        .login-input:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgb(56 189 248 / 25%); outline: 0; }
        .login-button { background: #f8fafc; color: #0f172a; }
        .login-button:hover { background: #e2e8f0; }
    </style>
</head>
<body class="login-page min-h-screen">
    <main class="mx-auto flex min-h-screen w-full max-w-[400px] items-center px-6 py-8">
        <div class="login-card mx-auto">
            <div style="margin-bottom: 20px;">
                <img
                    src="{{ asset('images/jangkaunet-logo.png') }}"
                    alt="JANETA Logo"
                    style="width: 140px; max-width: 100%; height: auto; display: block; margin-left: auto; margin-right: auto;"
                >
            </div>

            @if($errors->any())
                <div class="mb-4 rounded border border-rose-900/50 bg-rose-950/40 px-3 py-2.5 text-xs text-rose-300">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('login.authenticate') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="sr-only">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Email" required autofocus autocomplete="username" class="login-input h-[46px] w-full rounded-lg px-4 text-sm placeholder:text-slate-400">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" placeholder="Password" required autocomplete="current-password" class="login-input h-[46px] w-full rounded-lg px-4 text-sm placeholder:text-slate-400">
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-300">
                    <input type="checkbox" id="show-password" class="h-3.5 w-3.5 accent-slate-100 rounded">
                    Tampilkan Password
                </label>
                <button type="submit" class="cursor-pointer login-button h-[46px] w-full rounded-lg text-sm font-semibold transition">MASUK</button>
            </form>
        </div>
    </main>
    <script>
        const password = document.getElementById('password');
        document.getElementById('show-password').addEventListener('change', (event) => {
            password.type = event.target.checked ? 'text' : 'password';
        });
    </script>
</body>
</html>