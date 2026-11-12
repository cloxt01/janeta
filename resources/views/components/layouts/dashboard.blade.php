<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} · JANETA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#08090C] text-slate-100">
<header class="jn-navbar border-b border-slate-800 bg-[#0f172a]/50 backdrop-blur">
    <a href="{{ route('dashboard') }}" class="jn-logo-link" aria-label="JANETA Dashboard">
        <img
            src="{{ asset('images/jangkaunet-logo.png') }}"
            alt="JANETA Logo"
            style="width: 140px; max-width: 100%; height: auto; display: block; margin-left: auto; margin-right: auto;"
        >
    </a>
    <nav class="jn-nav">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i data-lucide="layout-dashboard"></i>Dashboard</a>
        <a href="{{ route('dashboard.ont') }}" class="{{ request()->routeIs('dashboard.ont') ? 'active' : '' }}"><i data-lucide="search"></i>Cari ONT</a>
        @if(auth()->user()->is_admin)
            <!-- Ubah logika 'open' agar hanya aktif jika tidak diklik menutup, atau biarkan dikontrol JS -->
            <details class="jn-dropdown">
                <summary class="{{ request()->routeIs('olts') ? 'active' : '' }}"><i data-lucide="server"></i>Perangkat<i data-lucide="chevron-down" class="jn-chevron"></i></summary>
                <div class="jn-dropdown-menu">
                    <a href="{{ route('olts') }}" class="{{ request()->routeIs('olts') ? 'active' : '' }}"><i data-lucide="server"></i>OLT</a>
                </div>
            </details>
            <details class="jn-dropdown">
                <summary class="{{ request()->routeIs('olts.ports') ? 'active' : '' }}"><i data-lucide="wrench"></i>Tools<i data-lucide="chevron-down" class="jn-chevron"></i></summary>
                <div class="jn-dropdown-menu">
                    <a href="{{ route('olt-activity') }}"
                    class="{{ request()->routeIs('olt-activity*') ? 'active' : '' }}">
                        <i data-lucide="network"></i>
                        OLT Activity
                    </a>
                    <a href="{{ route('olts.ports') }}" class="{{ request()->routeIs('olts.ports') ? 'active' : '' }}"><i data-lucide="activity"></i>Port Monitor</a>
                </div>
            </details>
            <a href="#" class="jn-nav-disabled opacity-60 cursor-not-allowed" aria-disabled="true" title="Master Data dikelola langsung di database"><i data-lucide="database"></i>Master Data</a>
            <a href="{{ route('whitelist') }}" class="{{ request()->routeIs('whitelist*') ? 'active' : '' }}"><i data-lucide="send"></i>Telegram</a>
            <a href="{{ route('users') }}" class="{{ request()->routeIs('users*') ? 'active' : '' }}"><i data-lucide="users"></i>Pengguna</a>
            <a href="{{ route('audit-logs') }}" class="{{ request()->routeIs('audit-logs') ? 'active' : '' }}"><i data-lucide="scroll-text"></i>Audit Log</a>
        @endif
    </nav>
    <div class="jn-account text-slate-300">
        <span class="font-medium text-slate-200">{{ auth()->user()->name }}</span>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button title="Keluar" aria-label="Keluar" class="text-slate-400 hover:text-rose-400 transition"><i data-lucide="log-out"></i></button>
        </form>
    </div>
</header>
<div class="jn-breadcrumb border-b border-slate-800/60 bg-[#08090C] px-6 py-2.5 text-xs text-slate-400">
    <span>Navigation</span> <b class="text-slate-600">›</b> <strong class="text-slate-200">{{ $title ?? 'Dashboard' }}</strong>
</div>
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

<!-- Skrip untuk menutup dropdown saat klik di luar -->
<script>
    document.addEventListener('click', (event) => {
        const dropdowns = document.querySelectorAll('details.jn-dropdown');
        dropdowns.forEach((dropdown) => {
            // Jika klik terjadi di luar elemen <details> yang sedang terbuka, tutup dropdown
            if (!dropdown.contains(event.target)) {
                dropdown.removeAttribute('open');
            }
        });
    });
</script>
@stack('scripts')
</body>
</html>

   