<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? '' }} · JangkauNet NetOps Assistant</title>

    <meta name="description" content="@yield('description', 'JANETA (JangkauNet NetOps Assistant): aplikasi internal untuk mendukung operasional manajemen jaringan dan perangkat.')">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="JANETA">
    <meta property="og:title" content="@yield('title', 'JANETA - JangkauNet NetOps Assistant')">
    <meta property="og:description" content="@yield('description', 'Aplikasi internal JangkauNet untuk operasional manajemen jaringan dan perangkat.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('og-image.png') }}?v=1">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="id_ID">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'JANETA - JangkauNet NetOps Assistant')">
    <meta name="twitter:description" content="@yield('description', 'Aplikasi internal JangkauNet untuk operasional manajemen jaringan dan perangkat.')">
    <meta name="twitter:image" content="{{ asset('favicon.png') }}?v=1">

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ==========================================================
           TOKENS
        ========================================================== */
        :root {
            color-scheme: dark;

            --jn-bg: #000;
            --jn-panel: #0a0a0a;
            --jn-text: #ededed;
            --jn-muted: #a1a1a1;
            --jn-subtle: #6b6b6b;

            --jn-line: rgba(255, 255, 255, .08);
            --jn-line-strong: rgba(255, 255, 255, .14);
            --jn-hover: rgba(255, 255, 255, .06);
            --jn-selected: rgba(255, 255, 255, .09);

            --jn-radius: 6px;
            --jn-ease: cubic-bezier(.16, 1, .3, 1);
            --jn-focus: 0 0 0 2px rgba(255, 255, 255, .22);
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: #333 transparent;
        }

        body {
            background: var(--jn-bg);
            color: var(--jn-text);
            font-family: Geist, Inter, ui-sans-serif, system-ui, -apple-system,
            BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: -.006em;
            -webkit-font-smoothing: antialiased;
        }

        /* ==========================================================
           HEADER
        ========================================================== */
        .jn-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(0, 0, 0, .72);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 1px solid var(--jn-line);
        }

        .jn-topbar {
            max-width: 1800px;
            min-height: 58px;
            margin: auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* Brand */
        .jn-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            text-decoration: none;
        }

        .jn-brand img {
            width: 104px;
            height: auto;
            object-fit: contain;
        }

        .jn-brand-divider {
            width: 1px;
            height: 20px;
            background: var(--jn-line-strong);
            transform: rotate(22deg);
        }

        .jn-brand-label {
            font-size: 12.5px;
            font-weight: 450;
            color: var(--jn-subtle);
        }

        /* ==========================================================
           NAVIGATION (top-level)
        ========================================================== */
        .jn-navigation {
            flex: 1;
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .jn-link,
        .jn-navigation > .jn-dropdown > summary {
            height: 32px;
            padding: 0 11px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: var(--jn-radius);
            color: var(--jn-muted);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            list-style: none;
            cursor: pointer;
            transition: color .15s ease, background-color .15s ease;
        }

        .jn-link:hover,
        .jn-navigation > .jn-dropdown > summary:hover,
        .jn-navigation > .jn-dropdown[open] > summary {
            color: #fff;
            background: var(--jn-hover);
        }

        .jn-link.active,
        .jn-navigation > .jn-dropdown > summary.active {
            color: #fff;
            background: var(--jn-selected);
        }

        .jn-link:focus-visible,
        .jn-dropdown summary:focus-visible,
        .jn-dropdown-menu a:focus-visible,
        .jn-logout:focus-visible,
        .jn-mobile-toggle:focus-visible {
            outline: none;
            box-shadow: var(--jn-focus);
        }

        .jn-link svg,
        .jn-dropdown summary svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            stroke-width: 1.75;
        }

        /* ==========================================================
           DROPDOWN
        ========================================================== */
        .jn-dropdown {
            position: relative;
        }

        .jn-dropdown summary {
            list-style: none;
            user-select: none;
        }

        .jn-dropdown summary::-webkit-details-marker {
            display: none;
        }

        .jn-chevron {
            width: 12px !important;
            height: 12px !important;
            margin-left: -1px;
            color: var(--jn-subtle);
            transition: transform .2s var(--jn-ease), color .15s ease;
        }

        .jn-dropdown[open] > summary .jn-chevron {
            transform: rotate(180deg);
            color: var(--jn-muted);
        }

        .jn-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            min-width: 224px;
            padding: 6px;
            background: var(--jn-panel);
            border-radius: 10px;
            box-shadow:
                0 0 0 1px var(--jn-line),
                0 2px 4px rgba(0, 0, 0, .4),
                0 16px 40px rgba(0, 0, 0, .65);
            transform-origin: top left;
            animation: jn-menu-in .16s var(--jn-ease);
        }

        @keyframes jn-menu-in {
            from { opacity: 0; transform: translateY(-4px) scale(.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .jn-dropdown-menu a {
            position: relative;
            min-height: 36px;
            padding: 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: var(--jn-radius);
            color: var(--jn-muted);
            font-size: 13px;
            font-weight: 450;
            text-decoration: none;
            transition: color .15s ease, background-color .15s ease;
        }

        .jn-dropdown-menu a svg {
            width: 15px;
            height: 15px;
            color: var(--jn-subtle);
            stroke-width: 1.75;
            transition: color .15s ease;
        }

        .jn-dropdown-menu a:hover {
            background: var(--jn-hover);
            color: #fff;
        }

        .jn-dropdown-menu a:hover svg,
        .jn-dropdown-menu a.active svg {
            color: var(--jn-text);
        }

        .jn-dropdown-menu a.active {
            background: var(--jn-selected);
            color: #fff;
        }

        /* Titik kecil penanda halaman aktif */
        .jn-dropdown-menu a.active::after {
            content: "";
            text-decoration: none;
            width: 5px;
            height: 5px;
            margin-left: auto;
            border-radius: 50%;
            background: #fff;
        }

        /* ==========================================================
           MOBILE NAVIGATION
           (sebelum submenu, supaya aturan submenu yang menang)
        ========================================================== */
        .jn-mobile-toggle {
            display: none;
            width: 34px;
            height: 34px;
            place-items: center;
            border: 0;
            border-radius: var(--jn-radius);
            background: transparent;
            box-shadow: 0 0 0 1px var(--jn-line);
            color: var(--jn-text);
            cursor: pointer;
            transition: background-color .15s ease;
        }

        .jn-mobile-toggle:hover {
            background: var(--jn-hover);
        }

        .jn-mobile-toggle svg {
            width: 17px;
            height: 17px;
        }

        .jn-mobile-nav {
            display: none;
            padding: 10px 16px 16px;
            border-top: 1px solid var(--jn-line);
            background: var(--jn-bg);
        }

        .jn-mobile-nav.show {
            display: block;
        }

        .jn-mobile-nav .jn-navigation {
            flex-direction: column;
            align-items: stretch;
            gap: 2px;
        }

        .jn-mobile-nav .jn-link,
        .jn-mobile-nav .jn-navigation > .jn-dropdown > summary {
            width: 100%;
            height: 42px;
        }

        .jn-mobile-nav .jn-dropdown-menu {
            position: static;
            min-width: 0;
            margin: 2px 0 6px;
            padding: 2px 0 2px 8px;
            background: transparent;
            box-shadow: none;
            animation: none;
        }

        .jn-mobile-nav .jn-chevron {
            margin-left: auto;
        }

        .jn-mobile-account {
            display: none;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 14px;
            border-top: 1px solid var(--jn-line);
        }

        /* ==========================================================
           SUBMENU (nested, mis. Router)
        ========================================================== */
        .jn-subdropdown > summary {
            min-height: 36px;
            padding: 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: var(--jn-radius);
            background: transparent;
            color: var(--jn-muted);
            font-size: 13px;
            font-weight: 450;
            cursor: pointer;
            transition: color .15s ease, background-color .15s ease;
        }

        .jn-subdropdown > summary:hover {
            background: var(--jn-hover);
            color: #fff;
        }

        .jn-subdropdown[open] > summary,
        .jn-subdropdown > summary.active {
            background: transparent;
            color: #fff;
        }

        .jn-subdropdown > summary svg {
            color: var(--jn-subtle);
        }

        .jn-subdropdown[open] > summary svg {
            color: var(--jn-text);
        }

        .jn-subdropdown > summary .jn-chevron {
            margin-left: auto;
        }

        /* Garis pemandu sejajar tengah ikon induk */
        .jn-subdropdown > .jn-dropdown-menu {
            position: static;
            min-width: 0;
            margin: 2px 0 4px 17px;
            padding: 2px 0 2px 8px;
            background: transparent;
            border-radius: 0;
            box-shadow: none;
            border-left: 1px solid var(--jn-line-strong);
            animation: none;
        }

        /* ==========================================================
           ACCOUNT
        ========================================================== */
        .jn-account {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .jn-account-name {
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 12.5px;
            font-weight: 450;
            color: var(--jn-muted);
        }

        .jn-avatar {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #2e2e2e 0%, #0f0f0f 100%);
            box-shadow: 0 0 0 1px var(--jn-line-strong), inset 0 1px 0 rgba(255, 255, 255, .08);
            color: #e5e5e5;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .jn-logout {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: var(--jn-radius);
            background: transparent;
            color: var(--jn-subtle);
            cursor: pointer;
            transition: color .15s ease, background-color .15s ease;
        }

        .jn-logout:hover {
            background: rgba(255, 70, 70, .1);
            color: #ff6b6b;
        }

        .jn-logout svg {
            width: 15px;
            height: 15px;
            stroke-width: 1.75;
        }

        /* ==========================================================
           BREADCRUMB, MAIN, ALERT
        ========================================================== */
        .jn-breadcrumb {
            max-width: 1800px;
            margin: auto;
            padding: 16px 24px 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: var(--jn-subtle);
        }

        .jn-main {
            max-width: 1800px;
            min-height: calc(100vh - 120px);
            margin: auto;
            padding: 20px 24px 32px;
        }

        .jn-alert {
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid;
            border-radius: 8px;
            font-size: 13px;
        }

        .jn-alert-success {
            background: rgba(16, 185, 129, .06);
            border-color: rgba(16, 185, 129, .22);
            color: #6ee7b7;
        }

        .jn-alert-error {
            background: rgba(244, 63, 94, .06);
            border-color: rgba(244, 63, 94, .22);
            color: #fda4af;
        }

        @media (prefers-reduced-motion: reduce) {
            .jn-dropdown-menu { animation: none; }
            * { transition-duration: .01ms !important; }
        }

        /* ==========================================================
           RESPONSIVE
        ========================================================== */
        @media (max-width: 1100px) {
            .jn-topbar {
                gap: 14px;
                padding: 0 18px;
            }

            .jn-link,
            .jn-navigation > .jn-dropdown > summary {
                padding: 0 8px;
                font-size: 12.5px;
            }
        }

        @media (max-width: 850px) {
            .jn-topbar {
                min-height: 56px;
                padding: 0 16px;
                justify-content: space-between;
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
                padding: 14px 16px 2px;
            }

            .jn-main {
                padding: 16px 16px 28px;
            }
        }
    </style>
</head>

<body class="min-h-screen antialiased">

@php
    $user = auth()->user();
    $can  = fn (string $permission) => $user->hasPermission($permission);

    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');

    /*
    |--------------------------------------------------------------------------
    | DEFINISI MENU (satu sumber untuk desktop & mobile)
    |--------------------------------------------------------------------------
    | - 'can'      : permission item (default true)
    | - 'match'    : pola route untuk state active
    | - 'children' : item/grup di dalamnya (grup kosong otomatis disembunyikan)
    |--------------------------------------------------------------------------
    */
    $menu = [
        [
            'label' => 'Dashboard',
            'icon'  => 'layout-dashboard',
            'route' => 'dashboard',
            'match' => ['dashboard'],
            'can'   => $can('dashboard.view'),
        ],
        [
            'label' => 'Cari ONT',
            'icon'  => 'search',
            'route' => 'dashboard.ont',
            'match' => ['dashboard.ont'],
            'can'   => $can('ont.lookup'),
        ],
        [
            'label' => 'Perangkat',
            'icon'  => 'server',
            'match' => ['olts.*', 'router.*'],
            'children' => [
                [
                    'label' => 'OLT Management',
                    'icon'  => 'server',
                    'route' => 'olts.index',
                    'match' => ['olts.index'],
                    'can'   => $can('olt.view'),
                ],
                [
                    'label' => 'Router',
                    'icon'  => 'router',
                    'match' => ['router.*'],
                    'children' => [
                        [
                            'label' => 'MikroTik',
                            'icon'  => 'cpu',
                            'route' => 'router.mikrotik.index',
                            'match' => ['router.mikrotik.*'],
                            'can'   => $can('router-mikrotik.view'),
                        ],
                    ],
                ],
            ],
        ],
        [
            'label' => 'Tools',
            'icon'  => 'wrench',
            'match' => ['olts.ports'],
            'children' => [

                [
                    'label' => 'Port Monitor',
                    'icon'  => 'activity',
                    'route' => 'olts.ports',
                    'match' => ['olts.ports'],
                    'can'   => $can('olt.view'),
                ],
            ],
        ],
        [
            'label' => 'Activity',
            'icon'  => 'activity',
            'match' => [
                'olt-activity',
                'router-activity',
                'audit-logs',
            ],
            'children' => [
                [
                    'label' => 'OLT Activity',
                    'icon'  => 'server',
                    'route' => 'olt-activity',
                    'match' => ['olt-activity'],
                    'can'   => $can('olt.activity.view'),
                ],
                [
                    'label' => 'MikroTik Activity',
                    'icon'  => 'router',
                    'route' => 'router-activity',
                    'match' => ['router-activity'],
                    'can'   => $can('mikrotik.activity.view'),
                ],
                [
                    'label' => 'Audit Log',
                    'icon'  => 'shield-check',
                    'route' => 'audit-logs',
                    'match' => ['audit-logs'],
                    'can'   => $can('audit.view'),
                ],
            ],
        ],
        [
            'label' => 'Management',
            'icon'  => 'layers',
            'match' => ['whitelist.*', 'users.*', 'location.*', 'audit-logs', 'access.*'],
            'children' => [
                [
                    'label' => 'Telegram',
                    'icon'  => 'send',
                    'route' => 'whitelist.index',
                    'match' => ['whitelist.*'],
                    'can'   => $can('telegram.view'),
                ],
                [
                    'label' => 'Pengguna',
                    'icon'  => 'users',
                    'route' => 'users.index',
                    'match' => ['users.*'],
                    'can'   => $can('users.view'),
                ],
                [
                    'label' => 'Live Location',
                    'icon'  => 'map-pin',
                    'route' => 'location.live',
                    'match' => ['location.live', 'location.history'],
                    'can'   => $can('location.view'),
                ],
                [
                    'label' => 'Access Control',
                    'icon'  => 'shield-check',
                    'route' => 'access.index',
                    'match' => ['access.*'],
                    'can'   => $can('access.view'),
                ],
            ],
        ],
    ];

    // Buang item tanpa izin & grup yang tidak punya isi (anti submenu kosong)
    $filterMenu = function (array $items) use (&$filterMenu) {
        return collect($items)
            ->filter(fn ($item) => $item['can'] ?? true)
            ->map(function ($item) use (&$filterMenu) {
                if (isset($item['children'])) {
                    $item['children'] = $filterMenu($item['children'])->all();
                }
                return $item;
            })
            ->filter(fn ($item) => ! isset($item['children']) || count($item['children']) > 0)
            ->values();
    };

    $isActive = fn (array $item) => request()->routeIs(...$item['match']);


    $menu     = $filterMenu($menu);
    $canTrack = $can('location.track');
@endphp

<header class="jn-header">

    <div class="jn-topbar">

        {{-- BRAND --}}
        <a href="{{ route('dashboard') }}" class="jn-brand" aria-label="JANETA Dashboard">
            <img src="{{ asset('images/jangkaunet-logo.png') }}" alt="JANETA">
            <span class="jn-brand-divider"></span>
            <span class="jn-brand-label">Network Management</span>
        </a>

        {{-- DESKTOP NAVIGATION --}}
        <nav class="jn-navigation jn-desktop-nav" aria-label="Navigasi utama">
            @include('layouts.partials.nav-items', ['items' => $menu, 'depth' => 0])
        </nav>

        {{-- DESKTOP ACCOUNT --}}
        <div class="jn-account jn-desktop-account">
            <div class="jn-avatar">{{ $initials }}</div>

            <span class="jn-account-name" title="{{ $user->name }}">{{ $user->name }}</span>

            <form method="POST" action="{{ route('logout') }}" onsubmit="window.__locationTracker?.stop();">
                @csrf
                <button type="submit" class="jn-logout" title="Keluar" aria-label="Keluar">
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

        <nav class="jn-navigation" aria-label="Navigasi mobile">
            @include('layouts.partials.nav-items', ['items' => $menu, 'depth' => 0])
        </nav>

        <div class="jn-mobile-account">
            <div class="flex items-center gap-3">
                <div class="jn-avatar">{{ $initials }}</div>

                <div>
                    <div class="text-sm text-white">{{ $user->name }}</div>
                    <div class="text-xs text-neutral-500">JANETA Account</div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" onsubmit="window.__locationTracker?.stop();">
                @csrf
                <button type="submit" class="jn-logout" title="Keluar" aria-label="Keluar">
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
    <strong class="font-medium text-neutral-200">{{ $title ?? 'Dashboard' }}</strong>
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
        const toggle    = document.getElementById('jnMobileToggle');
        const mobileNav = document.getElementById('jnMobileNav');

        // Mobile menu toggle
        toggle?.addEventListener('click', () => {
            const isOpen = mobileNav.classList.toggle('show');

            toggle.setAttribute('aria-expanded', String(isOpen));
            toggle.innerHTML = `<i data-lucide="${isOpen ? 'x' : 'menu'}"></i>`;

            window.lucide?.createIcons();
        });

        // Tutup dropdown saat klik di luar (desktop saja; di mobile menu inline)
        document.addEventListener('click', (event) => {
            document.querySelectorAll('.jn-desktop-nav details.jn-dropdown[open]').forEach((dropdown) => {
                if (!dropdown.contains(event.target)) {
                    dropdown.removeAttribute('open');
                }
            });
        });

        // Tutup dengan tombol Escape
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            document.querySelectorAll('.jn-desktop-nav > details.jn-dropdown[open]').forEach((dropdown) => {
                dropdown.removeAttribute('open');
                dropdown.querySelector(':scope > summary')?.focus();
            });
        });

        // Saat satu dropdown dibuka, tutup saudara selevel saja
        // (membuka Router tidak menutup Perangkat)
        document.querySelectorAll('details.jn-dropdown').forEach((dropdown) => {
            dropdown.addEventListener('toggle', () => {
                if (!dropdown.open) return;

                dropdown.parentElement
                    .querySelectorAll(':scope > details.jn-dropdown[open]')
                    .forEach((other) => {
                        if (other !== dropdown) other.removeAttribute('open');
                    });
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
