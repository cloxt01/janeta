<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Janeta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-page { background: #fff; color: #141414; }
        .login-card { width: min(100%, 360px); }
        .login-input { border: 1px solid #cbd5e1; background: #f8fafc; color: #0f172a; }
        .login-input:focus { border-color: #0f172a; box-shadow: 0 0 0 3px rgb(15 23 42 / 10%); outline: 0; }
        .login-button { background: #0f172a; }
        .login-button:hover { background: #1e293b; }
    </style>
</head>
<body class="login-page min-h-screen">
    <main class="mx-auto flex min-h-screen w-full max-w-[430px] items-center px-6 py-8">
        <div class="login-card">
            <center>
                <div class="login-logo mb-10" aria-label="JANETA">JANETA</div>
                <h1 class="mb-6 text-[19px] font-normal tracking-tight text-slate-900">Masuk untuk melanjutkan.</h1>
            
            </center>


            @if($errors->any())
                <div class="mb-5 rounded border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('login.authenticate') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="sr-only">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Email" required autofocus autocomplete="username" class="login-input h-[58px] w-full rounded-none px-5 text-[16px] placeholder:text-slate-700">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" placeholder="Password" required autocomplete="current-password" class="login-input h-[58px] w-full rounded-none px-5 text-[16px] placeholder:text-slate-700">
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-[15px] text-slate-900">
                    <input type="checkbox" id="show-password" class="h-4 w-4 accent-slate-900">
                    Tampilkan Password
                </label>
                <button type="submit" class="login-button h-[58px] w-full rounded-xl text-[16px] font-medium text-white transition">MASUK</button>
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
