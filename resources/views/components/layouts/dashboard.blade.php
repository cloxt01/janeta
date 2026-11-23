<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · JANETA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#08090C] text-slate-100">

@php
    $user = auth()->user();

    $canOlt = $user->hasPermission('olt.view');
    $canOltActivity = $user->hasPermission('olt.activity.view');
    $canTelegram = $user->hasPermission('telegram.view');
    $canUsers = $user->hasPermission('users.view');
    $canAudit = $user->hasPermission('audit.view');
    $canAccess = $user->hasPermission('access.view');
    $canLocation = $user->hasPermission('location.view');
    $canTrack = $user->hasPermission('location.track');
@endphp

<header class="jn-navbar border-b border-slate-800 bg-[#0f172a]/50 backdrop-blur">

    <a href="{{ route('dashboard') }}"
       class="jn-logo-link"
       aria-label="JANETA Dashboard">

        <img
            src="{{ asset('images/jangkaunet-logo.png') }}"
            alt="JANETA Logo"
            style="width: 140px; max-width: 100%; height: auto; display: block; margin-left: auto; margin-right: auto;"
        >
    </a>

    <nav class="jn-nav">

        {{-- Dashboard --}}
        @if($user->hasPermission('dashboard.view'))
            <a href="{{ route('dashboard') }}"
               class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard"></i>
                Dashboard
            </a>
        @endif

        {{-- Cari ONT --}}
        @if($user->hasPermission('ont.lookup'))
            <a href="{{ route('dashboard.ont') }}"
               class="{{ request()->routeIs('dashboard.ont') ? 'active' : '' }}">
                <i data-lucide="search"></i>
                Cari ONT
            </a>
        @endif


        {{-- PERANGKAT --}}
        @if($canOlt)
            <details class="jn-dropdown"
                {{ request()->routeIs('olts.*') ? 'open' : '' }}>

                <summary class="{{ request()->routeIs('olts.*') ? 'active' : '' }}">
                    <i data-lucide="server"></i>
                    Perangkat
                    <i data-lucide="chevron-down" class="jn-chevron"></i>
                </summary>

                <div class="jn-dropdown-menu">

                    <a href="{{ route('olts.index') }}"
                       class="{{ request()->routeIs('olts.index') ? 'active' : '' }}">
                        <i data-lucide="server"></i>
                        OLT
                    </a>

                </div>
            </details>
        @endif


        {{-- TOOLS --}}
        @if($canOltActivity || $canOlt)
            <details class="jn-dropdown"
                {{ request()->routeIs('olt-activity', 'olts.ports') ? 'open' : '' }}>

                <summary class="{{
                    request()->routeIs('olt-activity', 'olts.ports')
                        ? 'active'
                        : ''
                }}">
                    <i data-lucide="wrench"></i>
                    Tools
                    <i data-lucide="chevron-down" class="jn-chevron"></i>
                </summary>

                <div class="jn-dropdown-menu">

                    @if($canOltActivity)
                        <a href="{{ route('olt-activity') }}"
                           class="{{ request()->routeIs('olt-activity') ? 'active' : '' }}">
                            <i data-lucide="network"></i>
                            OLT Activity
                        </a>
                    @endif

                    @if($canOlt)
                        <a href="{{ route('olts.ports') }}"
                           class="{{ request()->routeIs('olts.ports') ? 'active' : '' }}">
                            <i data-lucide="activity"></i>
                            Port Monitor
                        </a>
                    @endif

                </div>
            </details>
        @endif


        {{-- MASTER DATA --}}
        {{-- Tetap dinonaktifkan sesuai layout sebelumnya --}}
        <a href="#"
           class="jn-nav-disabled opacity-60 cursor-not-allowed"
           aria-disabled="true"
           title="Master Data dikelola langsung di database">

            <i data-lucide="database"></i>
            Master Data
        </a>


        {{-- TELEGRAM --}}
        @if($canTelegram)
            <a href="{{ route('whitelist.index') }}"
               class="{{ request()->routeIs('whitelist.*') ? 'active' : '' }}">

                <i data-lucide="send"></i>
                Telegram
            </a>
        @endif


        {{-- PENGGUNA --}}
        @if($canUsers)
            <a href="{{ route('users.index') }}"
               class="{{ request()->routeIs('users.*') ? 'active' : '' }}">

                <i data-lucide="users"></i>
                Pengguna
            </a>
        @endif


        {{-- LIVE LOCATION --}}
        @if($canLocation)
            <a href="{{ route('location.live') }}"
               class="{{ request()->routeIs('location.live', 'location.history') ? 'active' : '' }}">

                <i data-lucide="map-pin"></i>
                Live Location
            </a>
        @endif


        {{-- AUDIT LOG --}}
        @if($canAudit)
            <a href="{{ route('audit-logs') }}"
               class="{{ request()->routeIs('audit-logs') ? 'active' : '' }}">

                <i data-lucide="scroll-text"></i>
                Audit Log
            </a>
        @endif


        {{-- ACCESS CONTROL --}}
        @if($canAccess)
            <a href="{{ route('access.index') }}"
               class="{{ request()->routeIs('access.*') ? 'active' : '' }}">

                <i data-lucide="shield-check"></i>
                Access Control
            </a>
        @endif

    </nav>


    {{-- ACCOUNT --}}
    <div class="jn-account text-slate-300">

        <span class="font-medium text-slate-200">
            {{ $user->name }}
        </span>

        <form method="post" action="{{ route('logout') }}"
              onsubmit="window.__locationTracker && window.__locationTracker.stop();">
            @csrf

            <button
                title="Keluar"
                aria-label="Keluar"
                class="text-slate-400 hover:text-rose-400 transition">

                <i data-lucide="log-out"></i>
            </button>
        </form>

    </div>

</header>


{{-- BREADCRUMB --}}
<div class="jn-breadcrumb border-b border-slate-800/60 bg-[#08090C] px-6 py-2.5 text-xs text-slate-400">

    <span>Navigation</span>
    <b class="text-slate-600">›</b>

    <strong class="text-slate-200">
        {{ $title ?? 'Dashboard' }}
    </strong>

</div>


{{-- MAIN CONTENT --}}
<main class="jn-main p-4 sm:p-6 lg:p-8">

    @if(session('success'))
        <div class="mb-5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-rose-300">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ $slot }}

</main>


{{-- CLOSE DROPDOWN WHEN CLICKING OUTSIDE --}}
<script>
    document.addEventListener('click', (event) => {

        const dropdowns = document.querySelectorAll('details.jn-dropdown');

        dropdowns.forEach((dropdown) => {

            if (!dropdown.contains(event.target)) {
                dropdown.removeAttribute('open');
            }

        });

    });
</script>

{{-- GPS TRACKER (hanya untuk user dengan permission location.track) --}}
@if($canTrack)
    @include('location._tracker')
@endif

@stack('scripts')

</body>
</html>
