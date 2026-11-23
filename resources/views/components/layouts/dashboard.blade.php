
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} · JANETA</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            color-scheme: dark;
            --jn-bg: #080808;
            --jn-panel: #0a0a0a;
            --jn-border: #262626;
            --jn-muted: #888888;
            --jn-text: #ededed;
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: #333 transparent;
        }

        body {
            background: var(--jn-bg);
            color: var(--jn-text);
            font-family: Geist, Inter, ui-sans-serif, system-ui,
            -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .jn-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(8, 8, 8, .88);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--jn-border);
        }

        .jn-topbar {
            min-height: 64px;
            display: flex;
            align-items: center;
            gap: 28px;
            padding: 0 28px;
            max-width: 1800px;
            margin: auto;
        }

        .jn-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            flex-shrink: 0;
            text-decoration: none;
        }

        .jn-brand img {
            width: 112px;
            height: auto;
            object-fit: contain;
        }

        .jn-brand-divider {
            height: 22px;
            width: 1px;
            background: #333;
        }

        .jn-brand-label {
            font-size: 12px;
            color: #888;
            letter-spacing: .02em;
        }

        .jn-navigation {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            min-width: 0;
        }

        .jn-link,
        .jn-dropdown > summary {
            height: 36px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0 11px;
            border: 1px solid transparent;
            border-radius: 7px;
            color: #a1a1aa;
            font-size: 13px;
            font-weight: 450;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            transition: .18s ease;
            list-style: none;
        }

        .jn-link:hover,
        .jn-dropdown > summary:hover {
            color: #fff;
            background: #171717;
        }

        .jn-link.active,
        .jn-dropdown > summary.active {
            color: #fff;
            background: #1c1c1c;
            border-color: #303030;
        }

        .jn-link svg,
        .jn-dropdown summary svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .jn-dropdown {
            position: relative;
        }

        .jn-dropdown summary::-webkit-details-marker {
            display: none;
        }

        .jn-chevron {
            width: 12px !important;
            height: 12px !important;
            color: #666;
            transition: transform .2s ease;
        }

        .jn-dropdown[open] .jn-chevron {
            transform: rotate(180deg);
        }

        .jn-dropdown-menu {
            position: absolute;
            top: calc(100% + 9px);
            left: 0;
            min-width: 210px;
            padding: 5px;
            background: #111;
            border: 1px solid #303030;
            border-radius: 10px;
            box-shadow: 0 12px 40px rgba(0,0,0,.5);
            animation: jn-dropdown-in .15s ease;
        }

        @keyframes jn-dropdown-in {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .jn-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 11px;
            color: #a1a1aa;
            font-size: 13px;
            text-decoration: none;
            border-radius: 6px;
            transition: .15s;
        }

        .jn-dropdown-menu a svg {
            width: 15px;
            height: 15px;
            color: #737373;
        }

        .jn-dropdown-menu a:hover,
        .jn-dropdown-menu a.active {
            background: #222;
            color: white;
        }

        .jn-account {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .jn-account-name {
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 12px;
            color: #d4d4d8;
        }

        .jn-avatar {
            width: 30px;
            height: 30px;
            border: 1px solid #383838;
            background: #202020;
            color: #ddd;
            display: grid;
            place-items: center;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .jn-logout {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border: 1px solid transparent;
            border-radius: 7px;
            color: #888;
            background: transparent;
            cursor: pointer;
            transition: .2s;
        }

        .jn-logout:hover {
            background: #251515;
            border-color: #492525;
            color: #fb7777;
        }

        .jn-logout svg {
            width: 16px;
            height: 16px;
        }

        .jn-mobile-toggle {
            display: none;
            width: 36px;
            height: 36px;
            place-items: center;
            border: 1px solid #303030;
            background: #111;
            border-radius: 7px;
            color: #ddd;
            cursor: pointer;
        }

        .jn-mobile-toggle svg {
            width: 18px;
            height: 18px;
        }

        .jn-mobile-nav {
            display: none;
            padding: 10px 16px 16px;
            border-top: 1px solid #262626;
            background: #090909;
        }

        .jn-mobile-nav.show {
            display: block;
        }

        .jn-mobile-nav .jn-navigation {
            display: flex;
            align-items: stretch;
            flex-direction: column;
            gap: 4px;
        }

        .jn-mobile-nav .jn-link,
        .jn-mobile-nav .jn-dropdown > summary {
            width: 100%;
            height: 42px;
        }

        .jn-mobile-nav .jn-dropdown-menu {
            position: static;
            margin: 4px 0 7px;
            box-shadow: none;
            animation: none;
        }

        .jn-mobile-nav .jn-dropdown > summary {
            justify-content: flex-start;
        }

        .jn-mobile-nav .jn-chevron {
            margin-left: auto;
        }

        .jn-mobile-account {
            display: none;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #262626;
            margin-top: 12px;
            padding-top: 14px;
        }

        .jn-breadcrumb {
            max-width: 1800px;
            margin: auto;
            padding: 15px 28px;
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 12px;
            color: #737373;
        }

        .jn-main {
            max-width: 1800px;
            margin: auto;
            min-height: calc(100vh - 120px);
            padding: 28px;
        }

        .jn-alert {
            border: 1px solid;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .jn-alert-success {
            background: rgba(16,185,129,.07);
            border-color: rgba(16,185,129,.25);
            color: #6ee7b7;
        }

        .jn-alert-error {
            background: rgba(244,63,94,.07);
            border-color: rgba(244,63,94,.25);
            color: #fda4af;
        }

        @media(max-width: 1100px) {
            .jn-topbar {
                gap: 16px;
                padding: 0 20px;
            }

            .jn-navigation {
                gap: 1px;
            }

            .jn-link,
            .jn-dropdown > summary {
                padding: 0 8px;
                font-size: 12px;
            }
        }

        @media(max-width: 850px) {
            .jn-topbar {
                min-height: 60px;
                justify-content: space-between;
                padding: 0 16px;
            }

            .jn-desktop-nav,
            .jn-desktop-account {
                display: none !important;
            }

            .jn-mobile-toggle {
                display: grid;
            }

            .jn-mobile-account {
                display: flex;
            }

            .jn-breadcrumb {
                padding: 13px 17px;
            }

            .jn-main {
                padding: 20px 16px;
            }
        }
    </style>
</head>

<body class="min-h-screen antialiased">

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

    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<header class="jn-header">

    <div class="jn-topbar">

        {{-- BRAND --}}
        <a href="{{ route('dashboard') }}" class="jn-brand"
           aria-label="JANETA Dashboard">

            <img
                src="{{ asset('images/jangkaunet-logo.png') }}"
                alt="JANETA"
            >

            <span class="jn-brand-divider"></span>

            <span class="jn-brand-label">Network Management</span>
        </a>

        {{-- DESKTOP NAVIGATION --}}
        <nav class="jn-navigation jn-desktop-nav">

            @if($user->hasPermission('dashboard.view'))
                <a href="{{ route('dashboard') }}"
                   class="jn-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard"></i>
                    Dashboard
                </a>
            @endif

            @if($user->hasPermission('ont.lookup'))
                <a href="{{ route('dashboard.ont') }}"
                   class="jn-link {{ request()->routeIs('dashboard.ont') ? 'active' : '' }}">
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
                            OLT Management
                        </a>
                    </div>
                </details>
            @endif

            {{-- TOOLS --}}
            @if($canOltActivity || $canOlt)
                <details class="jn-dropdown"
                    {{ request()->routeIs('olt-activity', 'olts.ports') ? 'open' : '' }}>

                    <summary class="{{ request()->routeIs('olt-activity', 'olts.ports') ? 'active' : '' }}">
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

            {{-- MANAGEMENT --}}
            @if($canTelegram || $canUsers || $canLocation || $canAudit || $canAccess)
                <details class="jn-dropdown"
                    {{ request()->routeIs('whitelist.*', 'users.*', 'location.*', 'audit-logs', 'access.*') ? 'open' : '' }}>

                    <summary class="{{ request()->routeIs('whitelist.*', 'users.*', 'location.*', 'audit-logs', 'access.*') ? 'active' : '' }}">
                        <i data-lucide="layers"></i>
                        Management
                        <i data-lucide="chevron-down" class="jn-chevron"></i>
                    </summary>

                    <div class="jn-dropdown-menu">

                        @if($canTelegram)
                            <a href="{{ route('whitelist.index') }}"
                               class="{{ request()->routeIs('whitelist.*') ? 'active' : '' }}">
                                <i data-lucide="send"></i>
                                Telegram
                            </a>
                        @endif

                        @if($canUsers)
                            <a href="{{ route('users.index') }}"
                               class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <i data-lucide="users"></i>
                                Pengguna
                            </a>
                        @endif

                        @if($canLocation)
                            <a href="{{ route('location.live') }}"
                               class="{{ request()->routeIs('location.live', 'location.history') ? 'active' : '' }}">
                                <i data-lucide="map-pin"></i>
                                Live Location
                            </a>
                        @endif

                        @if($canAudit)
                            <a href="{{ route('audit-logs') }}"
                               class="{{ request()->routeIs('audit-logs') ? 'active' : '' }}">
                                <i data-lucide="scroll-text"></i>
                                Audit Log
                            </a>
                        @endif

                        @if($canAccess)
                            <a href="{{ route('access.index') }}"
                               class="{{ request()->routeIs('access.*') ? 'active' : '' }}">
                                <i data-lucide="shield-check"></i>
                                Access Control
                            </a>
                        @endif

                    </div>
                </details>
            @endif

        </nav>

        {{-- DESKTOP ACCOUNT --}}
        <div class="jn-account jn-desktop-account">

            <div class="jn-avatar">
                {{ $initials }}
            </div>

            <span class="jn-account-name" title="{{ $user->name }}">
                {{ $user->name }}
            </span>

            <form method="POST" action="{{ route('logout') }}"
                  onsubmit="window.__locationTracker?.stop();">
                @csrf

                <button type="submit"
                        class="jn-logout"
                        title="Keluar"
                        aria-label="Keluar">
                    <i data-lucide="log-out"></i>
                </button>
            </form>

        </div>

        {{-- MOBILE TOGGLE --}}
        <button type="button"
                class="jn-mobile-toggle"
                id="jnMobileToggle"
                aria-label="Buka navigasi"
                aria-expanded="false">
            <i data-lucide="menu"></i>
        </button>

    </div>

    {{-- MOBILE NAVIGATION --}}
    <div class="jn-mobile-nav" id="jnMobileNav">

        <nav class="jn-navigation">

            @if($user->hasPermission('dashboard.view'))
                <a href="{{ route('dashboard') }}"
                   class="jn-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard"></i>
                    Dashboard
                </a>
            @endif

            @if($user->hasPermission('ont.lookup'))
                <a href="{{ route('dashboard.ont') }}"
                   class="jn-link {{ request()->routeIs('dashboard.ont') ? 'active' : '' }}">
                    <i data-lucide="search"></i>
                    Cari ONT
                </a>
            @endif

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
                            OLT Management
                        </a>
                    </div>
                </details>
            @endif

            @if($canOltActivity || $canOlt)
                <details class="jn-dropdown"
                    {{ request()->routeIs('olt-activity', 'olts.ports') ? 'open' : '' }}>
                    <summary class="{{ request()->routeIs('olt-activity', 'olts.ports') ? 'active' : '' }}">
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

            @if($canTelegram || $canUsers || $canLocation || $canAudit || $canAccess)
                <details class="jn-dropdown"
                    {{ request()->routeIs('whitelist.*', 'users.*', 'location.*', 'audit-logs', 'access.*') ? 'open' : '' }}>
                    <summary class="{{ request()->routeIs('whitelist.*', 'users.*', 'location.*', 'audit-logs', 'access.*') ? 'active' : '' }}">
                        <i data-lucide="layers"></i>
                        Management
                        <i data-lucide="chevron-down" class="jn-chevron"></i>
                    </summary>
                    <div class="jn-dropdown-menu">
                        @if($canTelegram)
                            <a href="{{ route('whitelist.index') }}">
                                <i data-lucide="send"></i> Telegram
                            </a>
                        @endif
                        @if($canUsers)
                            <a href="{{ route('users.index') }}">
                                <i data-lucide="users"></i> Pengguna
                            </a>
                        @endif
                        @if($canLocation)
                            <a href="{{ route('location.live') }}">
                                <i data-lucide="map-pin"></i> Live Location
                            </a>
                        @endif
                        @if($canAudit)
                            <a href="{{ route('audit-logs') }}">
                                <i data-lucide="scroll-text"></i> Audit Log
                            </a>
                        @endif
                        @if($canAccess)
                            <a href="{{ route('access.index') }}">
                                <i data-lucide="shield-check"></i> Access Control
                            </a>
                        @endif
                    </div>
                </details>
            @endif

        </nav>

        <div class="jn-mobile-account">
            <div class="flex items-center gap-3">
                <div class="jn-avatar">{{ $initials }}</div>
                <div>
                    <div class="text-sm text-white">{{ $user->name }}</div>
                    <div class="text-xs text-neutral-500">JANETA Account</div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}"
                  onsubmit="window.__locationTracker?.stop();">
                @csrf
                <button type="submit" class="jn-logout">
                    <i data-lucide="log-out"></i>
                </button>
            </form>
        </div>

    </div>

</header>

{{-- BREADCRUMB --}}
<div class="jn-breadcrumb">
    <i data-lucide="house" style="width:13px;height:13px"></i>

    <span>JANETA</span>

    <span class="text-neutral-700">/</span>

    <strong class="font-medium text-neutral-200">
        {{ $title ?? 'Dashboard' }}
    </strong>
</div>

{{-- MAIN CONTENT --}}
<main class="jn-main">

    @if(session('success'))
        <div class="jn-alert jn-alert-success">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" style="width:16px;height:16px"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="jn-alert jn-alert-error">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" style="width:16px;height:16px"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="jn-alert jn-alert-error">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ $slot }}

</main>

{{-- NAVIGATION SCRIPT --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {

        const toggle = document.getElementById('jnMobileToggle');
        const mobileNav = document.getElementById('jnMobileNav');

        toggle?.addEventListener('click', () => {
            const isOpen = mobileNav.classList.toggle('show');

            toggle.setAttribute('aria-expanded', String(isOpen));
            toggle.innerHTML = isOpen
                ? '<i data-lucide="x"></i>'
                : '<i data-lucide="menu"></i>';

            if (window.lucide) {
                window.lucide.createIcons();
            }
        });

        document.addEventListener('click', (event) => {
            document.querySelectorAll('details.jn-dropdown[open]')
                .forEach(dropdown => {
                    if (!dropdown.contains(event.target)) {
                        dropdown.removeAttribute('open');
                    }
                });
        });

        document.querySelectorAll('.jn-dropdown').forEach(dropdown => {
            dropdown.addEventListener('toggle', () => {
                if (dropdown.open) {
                    document.querySelectorAll('.jn-dropdown[open]')
                        .forEach(other => {
                            if (other !== dropdown) {
                                other.removeAttribute('open');
                            }
                        });
                }
            });
        });

    });
</script>

{{-- GPS TRACKER --}}
@if($canTrack)
    @include('location._tracker')
@endif

@stack('scripts')

</body>
</html>
