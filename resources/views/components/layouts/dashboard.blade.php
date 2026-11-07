<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} · JANETA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<header class="jn-navbar">
    <a href="{{ route('dashboard') }}" class="jn-logo-link" aria-label="JANETA Dashboard">
        <span class="jn-logo-mark">J</span>
        <span class="jn-logo-word">JANETA</span>
    </a>
    <nav class="jn-nav">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i data-lucide="layout-dashboard"></i>Dashboard</a>
        <a href="{{ route('ont.lookup') }}" class="{{ request()->routeIs('ont.*') ? 'active' : '' }}"><i data-lucide="search"></i>Cari ONT</a>
        @if(auth()->user()->is_admin)
            <details class="jn-dropdown" {{ request()->routeIs('olts') ? 'open' : '' }}>
                <summary class="{{ request()->routeIs('olts') ? 'active' : '' }}"><i data-lucide="server"></i>Devices<i data-lucide="chevron-down" class="jn-chevron"></i></summary>
                <div class="jn-dropdown-menu">
                    <a href="{{ route('olts') }}" class="{{ request()->routeIs('olts') ? 'active' : '' }}"><i data-lucide="server"></i>OLT</a>
                </div>
            </details>
            <details class="jn-dropdown" {{ request()->routeIs('olts.ports') ? 'open' : '' }}>
                <summary class="{{ request()->routeIs('olts.ports') ? 'active' : '' }}"><i data-lucide="wrench"></i>Tools<i data-lucide="chevron-down" class="jn-chevron"></i></summary>
                <div class="jn-dropdown-menu">
                    <a href="{{ route('olts.ports') }}" class="{{ request()->routeIs('olts.ports') ? 'active' : '' }}"><i data-lucide="activity"></i>Port Monitor</a>
                </div>
            </details>
            <a href="#" class="jn-nav-disabled" aria-disabled="true" title="Master Data dikelola langsung di database"><i data-lucide="database"></i>Master Data</a>
            <a href="{{ route('whitelist') }}" class="{{ request()->routeIs('whitelist*') ? 'active' : '' }}"><i data-lucide="send"></i>Telegram</a>
            <a href="{{ route('users') }}" class="{{ request()->routeIs('users*') ? 'active' : '' }}"><i data-lucide="users"></i>Pengguna</a>
            <a href="{{ route('audit-logs') }}" class="{{ request()->routeIs('audit-logs') ? 'active' : '' }}"><i data-lucide="scroll-text"></i>Audit Log</a>
        @endif
    </nav>
    <div class="jn-account">
        <span>{{ auth()->user()->name }}</span>
        <form method="post" action="{{ route('logout') }}">@csrf<button title="Keluar" aria-label="Keluar"><i data-lucide="log-out"></i></button></form>
    </div>
</header>
<div class="jn-breadcrumb"><span>Navigation</span><b>›</b><strong>{{ $title ?? 'Dashboard' }}</strong></div>
<main class="jn-main p-4 sm:p-6 lg:p-8">
        @if(session('success'))
            <div class="mb-5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-300">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-rose-300">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-rose-300">
                <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        {{ $slot }}
</main>
</body>
</html>
