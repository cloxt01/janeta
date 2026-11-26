
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} · JANETA</title>

    <meta name="description" content="JANETA - JangkauNet NetOps Assistant">
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            color-scheme: dark;

            --jn-bg: #1f1f1f;
            --jn-sidebar: #1c1c1c;
            --jn-panel: #222222;
            --jn-panel-hover: #292929;

            --jn-text: #e5e5e5;
            --jn-muted: #929292;
            --jn-subtle: #666666;

            --jn-border: #363636;
            --jn-border-soft: rgba(255,255,255,.07);

            --jn-green: #00a34a;
            --jn-green-soft: rgba(0,163,74,.14);

            --jn-sidebar-width: 260px;
            --jn-topbar-height: 58px;
            --jn-radius: 11px;
        }

        * {
            box-sizing: border-box;
            scrollbar-width: thin;
            scrollbar-color: #444 transparent;
        }

        html {
            min-height: 100%;
            background: var(--jn-bg);
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--jn-bg);
            color: var(--jn-text);
            font-family: Geist, Inter, ui-sans-serif, system-ui,
            -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* APP SHELL */

        .jn-app {
            min-height: 100vh;
        }

        /* SIDEBAR */

        .jn-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 100;
            width: var(--jn-sidebar-width);
            display: flex;
            flex-direction: column;
            background: var(--jn-sidebar);
            border-right: 1px solid var(--jn-border);
            transition: transform .25s ease;
        }

        .jn-sidebar-brand {
            height: var(--jn-topbar-height);
            min-height: var(--jn-topbar-height);
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 20px;
            border-bottom: 1px solid var(--jn-border);
        }

        .jn-brand-mark {
            width: 26px;
            height: 26px;
            display: grid;
            place-items: center;
            overflow: hidden;
            flex-shrink: 0;
            border-radius: 7px;
            background: var(--jn-green);
        }

        .jn-brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .jn-brand-name {
            font-size: 14px;
            font-weight: 650;
            letter-spacing: -.3px;
            color: #ededed;
        }

        .jn-sidebar-scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 13px 12px 24px;
        }

        .jn-sidebar-section {
            margin-top: 22px;
        }

        .jn-sidebar-section:first-child {
            margin-top: 0;
        }

        .jn-section-label {
            padding: 0 9px;
            margin-bottom: 8px;
            color: #888;
            font-size: 12px;
            font-weight: 550;
        }

        .jn-nav-item,
        .jn-nav-group > summary {
            position: relative;
            min-height: 38px;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 10px;
            border-radius: 7px;
            color: #999;
            font-size: 13.5px;
            font-weight: 450;
            cursor: pointer;
            list-style: none;
            transition: background .15s, color .15s;
        }

        .jn-nav-group > summary::-webkit-details-marker {
            display: none;
        }

        .jn-nav-item:hover,
        .jn-nav-group > summary:hover {
            background: #292929;
            color: #f2f2f2;
        }

        .jn-nav-item.active,
        .jn-nav-group.active > summary {
            background: #303030;
            color: #fff;
        }

        .jn-nav-item.active::before {
            position: absolute;
            left: 0;
            top: 9px;
            bottom: 9px;
            width: 3px;
            content: "";
            border-radius: 0 4px 4px 0;
            background: var(--jn-green);
        }

        .jn-nav-item svg,
        .jn-nav-group > summary svg {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            stroke-width: 1.7;
        }

        .jn-nav-chevron {
            width: 13px !important;
            height: 13px !important;
            margin-left: auto;
            color: #777;
            transition: transform .2s;
        }

        .jn-nav-group[open] > summary .jn-nav-chevron {
            transform: rotate(90deg);
        }

        .jn-nav-children {
            margin: 3px 0 5px 18px;
            padding-left: 9px;
            border-left: 1px solid #3a3a3a;
        }

        .jn-nav-children .jn-nav-item,
        .jn-nav-children .jn-nav-group > summary {
            min-height: 35px;
            font-size: 12.5px;
        }

        .jn-sidebar-footer {
            padding: 12px 15px;
            border-top: 1px solid var(--jn-border);
            color: #777;
            font-size: 11px;
        }

        /* MAIN AREA */

        .jn-workspace {
            min-height: 100vh;
            margin-left: var(--jn-sidebar-width);
        }

        /* TOPBAR */

        .jn-topbar {
            position: sticky;
            top: 0;
            z-index: 80;
            height: var(--jn-topbar-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 0 20px;
            background: rgba(31,31,31,.96);
            border-bottom: 1px solid var(--jn-border);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .jn-topbar-left {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
        }

        .jn-page-heading {
            font-size: 14px;
            font-weight: 600;
            color: #ddd;
            white-space: nowrap;
        }

        .jn-mobile-toggle {
            display: none;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--jn-border);
            border-radius: 7px;
            background: transparent;
            color: #ddd;
            cursor: pointer;
        }

        .jn-topbar-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .jn-global-search {
            width: min(430px, 40vw);
            height: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px;
            border: 1px solid var(--jn-border);
            border-radius: 8px;
            background: #202020;
            color: #888;
        }

        .jn-global-search svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .jn-global-search input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: none;
            background: transparent;
            color: #eee;
            font: inherit;
            font-size: 13px;
        }

        .jn-global-search input::placeholder {
            color: #858585;
        }

        .jn-account {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .jn-avatar {
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 50%;
            background: #303030;
            color: #bbb;
            font-size: 11px;
            font-weight: 600;
        }

        .jn-account-name {
            max-width: 150px;
            overflow: hidden;
            color: #aaa;
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .jn-logout {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #888;
            cursor: pointer;
        }

        .jn-logout:hover {
            background: rgba(255,70,70,.1);
            color: #ff7777;
        }

        /* CONTENT */

        .jn-main {
            width: 100%;
            max-width: 1800px;
            min-height: calc(100vh - var(--jn-topbar-height));
            margin: 0 auto;
            padding: 25px 22px 35px;
        }

        .jn-breadcrumb {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 17px;
            color: #888;
            font-size: 12px;
        }

        .jn-breadcrumb svg {
            width: 13px;
            height: 13px;
        }

        .jn-alert {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid;
            border-radius: 8px;
            font-size: 13px;
        }

        .jn-alert-success {
            background: rgba(16,185,129,.07);
            border-color: rgba(16,185,129,.2);
            color: #6ee7b7;
        }

        .jn-alert-error {
            background: rgba(244,63,94,.07);
            border-color: rgba(244,63,94,.2);
            color: #fda4af;
        }

        /* SHARED CARD STYLE */

        .jn-card {
            background: var(--jn-panel);
            border: 1px solid var(--jn-border);
            border-radius: var(--jn-radius);
        }

        .jn-card-header {
            padding: 17px 17px 0;
        }

        .jn-card-title {
            color: #e5e5e5;
            font-size: 14px;
            font-weight: 600;
        }

        .jn-card-description {
            margin-top: 4px;
            color: #888;
            font-size: 12px;
        }

        /* MOBILE */

        .jn-sidebar-backdrop {
            display: none;
        }

        /* JANETA DARK PAGINATION */

        .jn-pagination nav {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .jn-pagination nav > div:first-child {
            color: #888;
            font-size: 12px;
        }

        .jn-pagination nav > div:last-child > span,
        .jn-pagination nav > div:last-child > a {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .jn-pagination a,
        .jn-pagination span[aria-current="page"] span,
        .jn-pagination span[aria-disabled="true"] span {
            min-width: 32px;
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 9px;
            border: 1px solid #363636;
            border-radius: 7px;
            background: #252525;
            color: #aaa;
            font-size: 12px;
        }

        .jn-pagination a:hover {
            background: #303030;
            color: #fff;
        }

        .jn-pagination span[aria-current="page"] span {
            background: #17452d;
            border-color: #23663e;
            color: #65d995;
        }

        .jn-pagination span[aria-disabled="true"] span {
            opacity: .4;
        }


        @media(max-width: 900px) {
            .jn-sidebar {
                transform: translateX(-100%);
            }

            .jn-sidebar.open {
                transform: translateX(0);
            }

            .jn-sidebar-backdrop.show {
                position: fixed;
                inset: 0;
                z-index: 90;
                display: block;
                background: rgba(0,0,0,.6);
                backdrop-filter: blur(3px);
            }

            .jn-workspace {
                margin-left: 0;
            }

            .jn-mobile-toggle {
                display: flex;
            }

            .jn-main {
                padding: 20px 16px 30px;
            }
        }

        @media(max-width: 600px) {
            .jn-topbar {
                padding: 0 13px;
            }

            .jn-topbar-right {
                gap: 10px;
            }

            .jn-global-search {
                width: 36px;
                height: 36px;
                justify-content: center;
                padding: 0;
                border: 0;
                background: transparent;
            }

            .jn-global-search input {
                display: none;
            }

            .jn-account-name,
            .jn-logout {
                display: none;
            }

            .jn-main {
                padding: 17px 12px 25px;
            }
        }

        @media(prefers-reduced-motion:reduce) {
            *,
            *::before,
            *::after {
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>
@php
    $user = auth()->user();

    $can = fn (string $permission) => $user->hasPermission($permission);

    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');

    $sections = [
        [
            'label' => 'Workspace',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'layout-dashboard',
                    'route' => 'dashboard',
                    'match' => ['dashboard'],
                    'can' => $can('dashboard.view'),
                ],
                [
                    'label' => 'Cari ONT',
                    'icon' => 'search',
                    'route' => 'dashboard.ont',
                    'match' => ['dashboard.ont'],
                    'can' => $can('ont.lookup'),
                ],
            ],
        ],
        [
            'label' => 'Network',
            'items' => [
                [
                    'label' => 'Perangkat',
                    'icon' => 'server',
                    'match' => ['olts.*', 'router.*'],
                    'children' => [
                        [
                            'label' => 'OLT Management',
                            'icon' => 'server',
                            'route' => 'olts.index',
                            'match' => ['olts.index'],
                            'can' => $can('olt.view'),
                        ],
                        [
                            'label' => 'Router Management',
                            'icon' => 'cpu',
                            'match' => ['router.*'],
                            'children' => [
                                [
                                    'label' => 'MikroTik',
                                    'icon' => 'router',
                                    'route' => 'router.mikrotik.index',
                                    'match' => ['router.mikrotik.*'],
                                    'can' => $can('router-mikrotik.view'),
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'Live Location',
                    'icon' => 'map-pin',
                    'route' => 'location.live',
                    'match' => ['location.live', 'location.history'],
                    'can' => $can('location.view'),
                ],
            ],
        ],
        [
            'label' => 'Tools',
            'items' => [
                // Semua menu tools
                [
                    'label' => 'Port Monitor',
                    'icon' => 'activity',
                    'route' => 'tools.port-monitor',
                    'match' => ['tools.port-monitor'],
                    'can' => $can('tools.port-monitor'),
                ],
            ],
        ],
        [
            'label' => 'Tools & Activity',
            'items' => [
                [
                    'label' => 'OLT Activity',
                    'icon' => 'server',
                    'route' => 'olt-activity',
                    'match' => ['olt-activity'],
                    'can' => $can('activity.olt.view'),
                ],
                [
                    'label' => 'MikroTik Activity',
                    'icon' => 'router',
                    'route' => 'router-activity',
                    'match' => ['router-activity'],
                    'can' => $can('activity.mikrotik.view'),
                ],
                [
                    'label' => 'User Activity',
                    'icon' => 'shield-check',
                    'route' => 'user-activity',
                    'match' => ['user-activity'],
                    'can' => $can('activity.user.view'),
                ],
            ],
        ],
        [
            'label' => 'Management',
            'items' => [
                [
                    'label' => 'Telegram',
                    'icon' => 'send',
                    'route' => 'telegram.whitelist.index',
                    'match' => ['telegram.whitelist.*'],
                    'can' => $can('telegram.whitelist.view'),
                ],
                [
                    'label' => 'Pengguna',
                    'icon' => 'users',
                    'route' => 'users.index',
                    'match' => ['users.*'],
                    'can' => $can('users.view'),
                ],
                [
                    'label' => 'Access Control',
                    'icon' => 'shield-check',
                    'route' => 'access.index',
                    'match' => ['access.*'],
                    'can' => $can('access.view'),
                ],
            ],
        ],
    ];

    $filterItems = function (array $items) use (&$filterItems) {
        return collect($items)
            ->filter(fn ($item) => $item['can'] ?? true)
            ->map(function ($item) use (&$filterItems) {
                if (isset($item['children'])) {
                    $item['children'] = $filterItems($item['children'])->all();
                }

                return $item;
            })
            ->filter(fn ($item) =>
                !isset($item['children']) || count($item['children']) > 0
            )
            ->values();
    };

    foreach ($sections as &$section) {
        $section['items'] = $filterItems($section['items'])->all();
    }
    unset($section);

    $sections = array_values(array_filter(
        $sections,
        fn ($section) => count($section['items']) > 0
    ));
    $isActive = fn (array $item) =>
    !empty($item['match']) &&
    request()->routeIs(...$item['match']);

    $canTrack = $can('location.track');
@endphp

<div class="jn-app">

    {{-- SIDEBAR --}}
    <aside class="jn-sidebar" id="jnSidebar">

        <a href="{{ route('dashboard') }}" class="jn-sidebar-brand">
            <div class="jn-brand-mark">
                <img src="{{ asset('images/jangkaunet-logo.png') }}" alt="JANETA">
            </div>

            <span class="jn-brand-name">JANETA</span>
        </a>

        <div class="jn-sidebar-scroll">

            @foreach($sections as $section)
                <section class="jn-sidebar-section">

                    <div class="jn-section-label">
                        {{ $section['label'] }}
                    </div>

                    @include('layouts.partials.sidebar-items', [
                        'items' => $section['items'],
                        'depth' => 0,
                        'isActive' => $isActive,
                    ])

                </section>
            @endforeach

        </div>

        <div class="jn-sidebar-footer">
            JangkauNet NetOps Assistant
            <span class="float-right">v1.0</span>
        </div>

    </aside>

    <div class="jn-sidebar-backdrop" id="jnSidebarBackdrop"></div>

    {{-- WORKSPACE --}}
    <div class="jn-workspace">

        {{-- TOPBAR --}}
        <header class="jn-topbar">

            <div class="jn-topbar-left">
                <button type="button"
                        class="jn-mobile-toggle"
                        id="jnSidebarToggle"
                        aria-label="Toggle sidebar">
                    <i data-lucide="menu"></i>
                </button>

                <div class="jn-page-heading">
                    {{ $title ?? 'Dashboard' }}
                </div>
            </div>

            <div class="jn-topbar-right">

                <div class="jn-global-search">
                    <i data-lucide="search"></i>
                    <input type="search"
                           id="jnGlobalSearch"
                           placeholder="Cari perangkat, ONT, router...">
                </div>

                <div class="jn-account">

                    <span class="jn-account-name" title="{{ $user->name }}">
                        {{ $user->name }}
                    </span>

                    <div class="jn-avatar" title="{{ $user->name }}">
                        {{ $initials }}
                    </div>

                    <form method="POST"
                          action="{{ route('logout') }}"
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
            </div>

        </header>

        {{-- MAIN CONTENT --}}
        <main class="jn-main">

            @if(session('success'))
                <div class="jn-alert jn-alert-success">
                    <i data-lucide="check-circle" class="inline-block w-4 h-4 mr-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="jn-alert jn-alert-error">
                    <i data-lucide="alert-circle" class="inline-block w-4 h-4 mr-2"></i>
                    {{ session('error') }}
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

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('jnSidebar');
        const toggle = document.getElementById('jnSidebarToggle');
        const backdrop = document.getElementById('jnSidebarBackdrop');

        const closeSidebar = () => {
            sidebar?.classList.remove('open');
            backdrop?.classList.remove('show');
        };

        toggle?.addEventListener('click', () => {
            sidebar?.classList.toggle('open');
            backdrop?.classList.toggle('show');
        });

        backdrop?.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        // Close sibling dropdowns only at the same nesting level.
        document.querySelectorAll('.jn-nav-group').forEach(group => {
            group.addEventListener('toggle', () => {
                if (!group.open) return;

                [...group.parentElement.children]
                    .filter(el =>
                        el !== group &&
                        el.matches('.jn-nav-group') &&
                        el.open
                    )
                    .forEach(el => el.removeAttribute('open'));
            });
        });

        // Close mobile sidebar after navigation.
        sidebar?.querySelectorAll('a.jn-nav-item').forEach(link => {
            link.addEventListener('click', closeSidebar);
        });

        window.lucide?.createIcons();
    });
</script>

@if($canTrack)
    @include('location._tracker')
@endif

@stack('scripts')

</body>
</html>
