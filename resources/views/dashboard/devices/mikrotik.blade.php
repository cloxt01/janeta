<x-layouts.dashboard title="MikroTik Devices">

    @php
        $svcClass = fn (bool $enabled, ?string $status) => ! $enabled ? 'muted'
            : ($status === 'up' ? 'online' : ($status === 'down' ? 'offline' : 'unknown'));

        $svcLabel = fn (bool $enabled, ?string $status) => ! $enabled ? 'OFF' : strtoupper($status ?? 'UNKNOWN');

        $totalDevices = $devices->total();
        $pageDevices  = $devices->getCollection();
        $onlineCount  = $pageDevices->where('status', 'online')->count();
        $offlineCount = $pageDevices->where('status', 'offline')->count();
        $unknownCount = $pageDevices->count() - $onlineCount - $offlineCount;
    @endphp

    <style>
        /* =========================================================
           DEVICES - CENTIPID DARK (dipakai MikroTik & OLT)
        ========================================================= */
        .dv-page { --bg:#1c1c1c; --bg2:#202020; --bg3:#242424; --line:#2e2e2e; --line2:#3a3a3a;
            --tx:#f2f2f2; --tx2:#c4c4c4; --mu:#8a8a8a; --fa:#6d6d6d;
            --gr:#0b8a3e; --gr-hi:#0da048; }

        /* card */
        .dv-card { overflow:hidden; border:1px solid var(--line); border-radius:16px; background:var(--bg); transition:border-color .15s; }
        .dv-card:hover { border-color:var(--line2); }

        .dv-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid var(--line); }
        .dv-identity { display:flex; gap:14px; min-width:0; }
        .dv-icon { display:grid; flex:0 0 40px; width:40px; height:40px; place-items:center; border-radius:999px; background:#2c2c2c; color:#bdbdbd; }
        .dv-icon svg { width:18px; height:18px; }
        .dv-name { color:var(--tx); font-size:15px; font-weight:600; letter-spacing:-.01em; }
        .dv-ip { margin-top:4px; color:var(--mu); font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:11px; }
        .dv-chips { margin-top:8px; display:flex; flex-wrap:wrap; gap:6px; }
        .dv-chip { border:1px solid var(--line2); border-radius:999px; background:var(--bg3); padding:2px 9px; color:#b0b0b0; font-size:10px; font-weight:500; }

        /* status pill */
        .dv-pill { display:inline-flex; align-items:center; gap:6px; border:1px solid var(--line2); border-radius:999px; background:var(--bg3); padding:4px 10px; color:var(--mu); font-size:10px; font-weight:600; letter-spacing:.04em; white-space:nowrap; }
        .dv-pill small { font-size:10px; font-weight:400; letter-spacing:0; opacity:.75; }
        .dv-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
        .dv-pill.online  { background:#14281d; border-color:#1f5a38; color:#4cd286; }
        .dv-pill.offline { background:#2a1717; border-color:#5e2b2b; color:#f08a8a; }
        .dv-pill.unknown { background:#2a2314; border-color:#5a4a1f; color:#e0b04c; }
        .dv-pill.muted   { background:var(--bg3); border-color:var(--line2); color:var(--mu); }
        .dv-pill.online .dv-dot { box-shadow:0 0 0 3px rgba(76,210,134,.18); }

        /* body */
        .dv-body { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr); }
        .dv-services { padding:18px 20px; }
        .dv-config { padding:18px 20px; border-left:1px solid var(--line); background:var(--bg2); }
        .dv-title { margin-bottom:12px; color:var(--mu); font-size:10px; font-weight:600; letter-spacing:.12em; text-transform:uppercase; }

        /* service rows */
        .dv-svc-list { display:grid; gap:8px; }
        .dv-svc { overflow:hidden; border:1px solid var(--line); border-radius:14px; background:var(--bg2); transition:border-color .15s, background .15s; }
        .dv-svc:hover { border-color:var(--line2); }
        .dv-svc[open] { border-color:var(--line2); background:#232323; }
        .dv-svc summary { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; cursor:pointer; list-style:none; }
        .dv-svc summary::-webkit-details-marker { display:none; }
        .dv-svc-info { display:flex; align-items:center; gap:10px; color:var(--tx); font-size:12px; font-weight:600; }
        .dv-svc-info svg { width:16px; height:16px; color:var(--mu); }
        .dv-svc-info small { color:var(--fa); font-size:11px; font-weight:400; }
        .dv-svc-right { display:flex; align-items:center; gap:10px; }
        .dv-chev { width:14px; height:14px; color:var(--fa); transition:transform .15s; }
        .dv-svc[open] .dv-chev { transform:rotate(180deg); }
        .dv-svc-config { display:grid; grid-template-columns:1fr 1fr; gap:12px; padding:14px; border-top:1px solid var(--line); }
        .dv-wide { grid-column:1 / -1; }

        /* form controls */
        .dv-label { display:block; margin-bottom:6px; color:var(--mu); font-size:11px; }
        .dv-input { display:block; width:100%; height:36px; border:1px solid var(--line); border-radius:999px; background:var(--bg2); padding:0 14px; color:#e5e5e5; font-size:12px; outline:none; transition:border-color .15s, box-shadow .15s; }
        .dv-svc .dv-input, .dv-config .dv-input { background:var(--bg); }
        .dv-input::placeholder { color:#777; }
        .dv-input:focus { border-color:#5a5a5a; box-shadow:0 0 0 3px rgba(255,255,255,.04); }
        textarea.dv-input { height:auto; padding:10px 14px; border-radius:16px; resize:vertical; line-height:1.5; }
        .dv-input.mono { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }

        /* toggle switch */
        .dv-toggle { display:flex; align-items:center; gap:10px; color:var(--tx2); font-size:12px; cursor:pointer; }
        .dv-switch { appearance:none; -webkit-appearance:none; position:relative; flex:0 0 auto; width:30px; height:18px; margin:0; border-radius:999px; background:#3a3a3a; cursor:pointer; transition:background .15s; }
        .dv-switch::after { content:''; position:absolute; top:2px; left:2px; width:14px; height:14px; border-radius:50%; background:#cfcfcf; transition:transform .15s, background .15s; }
        .dv-switch:checked { background:var(--gr); }
        .dv-switch:checked::after { transform:translateX(12px); background:#fff; }
        .dv-switch:focus-visible { outline:2px solid #5a5a5a; outline-offset:2px; }

        /* notes */
        .dv-note { color:var(--fa); font-size:11px; line-height:1.6; }
        .dv-note code { border-radius:6px; background:var(--bg3); padding:1px 5px; color:var(--tx2); font-size:10px; }
        .dv-err { border:1px solid #5e2b2b; border-radius:12px; background:#2a1717; padding:9px 12px; color:#f08a8a; font-size:11px; line-height:1.5; }

        /* snapshot */
        .dv-snap { margin-top:12px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; border:1px dashed var(--line2); border-radius:14px; padding:10px 14px; color:var(--mu); font-size:11px; }
        .dv-snap-actions { display:flex; align-items:center; gap:12px; }
        .dv-link { color:#4cd286; font-size:11px; font-weight:600; transition:opacity .15s; }
        .dv-link:hover { opacity:.8; }

        /* footer */
        .dv-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding:12px 20px; border-top:1px solid var(--line); background:var(--bg2); }
        .dv-foot-actions { display:flex; flex-wrap:wrap; align-items:center; gap:8px; }

        /* buttons */
        .dv-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; height:32px; border:1px solid transparent; border-radius:999px; padding:0 16px; font-size:11px; font-weight:600; white-space:nowrap; cursor:pointer; transition:background .15s, border-color .15s, opacity .15s; }
        .dv-btn svg { width:14px; height:14px; }
        .dv-btn:disabled { opacity:.45; cursor:not-allowed; }
        .dv-btn-primary { background:var(--gr); color:#fff; }
        .dv-btn-primary:hover:not(:disabled) { background:var(--gr-hi); }
        .dv-btn-ghost { border-color:var(--line2); background:transparent; color:#d0d0d0; }
        .dv-btn-ghost:hover:not(:disabled) { background:#2a2a2a; }
        .dv-btn-danger { border-color:#5e2b2b; background:#2a1717; color:#f08a8a; }
        .dv-btn-danger:hover:not(:disabled) { background:#361d1d; }

        @media (max-width:860px) {
            .dv-body { grid-template-columns:1fr; }
            .dv-config { border-left:0; border-top:1px solid var(--line); }
        }
        @media (max-width:560px) {
            .dv-head { flex-direction:column; }
            .dv-svc-config { grid-template-columns:1fr; }
        }
    </style>

    <div class="dv-page">

        {{-- PAGE HEADER --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
                    Infrastructure · MikroTik
                </div>

                <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
                    MikroTik <span class="text-[#8a8a8a]">devices.</span>
                </h1>

                <p class="mt-2.5 max-w-xl text-xs text-[#a0a0a0]">
                    {{ $totalDevices }} perangkat · satu perangkat untuk setiap alamat IP. Koneksi lewat RouterOS API, dengan SSH opsional untuk export konfigurasi.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <form method="post" action="{{ route('router.mikrotik.test-all') }}">
                    @csrf
                    <button type="submit" class="h-9 rounded-full border border-[#3a3a3a] px-5 text-xs font-semibold text-[#d0d0d0] transition-colors hover:bg-[#2a2a2a]">
                        Test semua koneksi
                    </button>
                </form>

                <button type="button" id="toggleAddDevice"
                        class="h-9 rounded-full bg-[#0b8a3e] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#0da048]">
                    Tambah perangkat
                </button>
            </div>
        </div>

        {{-- Flash message & error ditampilkan oleh layout (x-layouts.dashboard) --}}

        {{-- STAT STRIP --}}
        <div class="mb-5 grid grid-cols-2 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] lg:grid-cols-4">
            <div class="border-b border-r border-[#2e2e2e] px-5 py-4 lg:border-b-0">
                <div class="text-[11px] text-[#8a8a8a]">Total perangkat</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $totalDevices }}</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">terdaftar</div>
            </div>

            <div class="border-b border-[#2e2e2e] px-5 py-4 lg:border-b-0 lg:border-r">
                <div class="text-[11px] text-[#8a8a8a]">Online</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#4cd286]">{{ $onlineCount }}</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">pada halaman ini</div>
            </div>

            <div class="border-r border-[#2e2e2e] px-5 py-4">
                <div class="text-[11px] text-[#8a8a8a]">Offline</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight {{ $offlineCount ? 'text-[#f08a8a]' : 'text-[#f2f2f2]' }}">{{ $offlineCount }}</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">pada halaman ini</div>
            </div>

            <div class="px-5 py-4">
                <div class="text-[11px] text-[#8a8a8a]">Belum dicek</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $unknownCount }}</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">status unknown</div>
            </div>
        </div>

        {{-- ADD DEVICE PANEL --}}
        <div id="addDevicePanel"
             class="{{ $errors->any() ? '' : 'hidden' }} mb-5 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

            <div class="border-b border-[#2e2e2e] px-5 py-4">
                <h2 class="text-sm font-semibold text-[#f2f2f2]">Tambah perangkat MikroTik</h2>
                <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Gunakan user khusus dengan akses read-only untuk monitoring</p>
            </div>

            <form method="post" action="{{ route('router.mikrotik.store') }}"
                  class="grid grid-cols-1 gap-4 p-5 md:grid-cols-12">
                @csrf

                <div class="md:col-span-3">
                    <label class="dv-label">Nama perangkat</label>
                    <input name="name" value="{{ old('name') }}" placeholder="RB-POP-01" required class="dv-input">
                </div>

                <div class="md:col-span-3">
                    <label class="dv-label">IP / Hostname</label>
                    <input name="host" value="{{ old('host') }}" placeholder="192.168.88.1" required class="dv-input">
                </div>

                <div class="md:col-span-3">
                    <label class="dv-label">API port</label>
                    <input name="api_port" type="number" value="{{ old('api_port', 8728) }}" min="1" max="65535" class="dv-input">
                </div>

                <div class="md:col-span-3">
                    <label class="dv-label">SSH port (export)</label>
                    <input name="ssh_port" type="number" value="{{ old('ssh_port', 22) }}" min="1" max="65535" class="dv-input">
                </div>

                <div class="md:col-span-6">
                    <label class="dv-label">Username</label>
                    <input name="username" value="{{ old('username') }}" placeholder="janeta-monitor" required class="dv-input">
                </div>

                <div class="md:col-span-6">
                    <label class="dv-label">Password</label>
                    <input name="password" type="password" autocomplete="new-password" placeholder="Password API" required class="dv-input">
                </div>

                <input type="hidden" name="api_ssl" value="0">

                <div class="md:col-span-4">
                    <label class="dv-toggle">
                        <input type="checkbox" name="api_ssl" value="1" class="dv-switch">
                        Pakai API-SSL <span class="text-[#6d6d6d]">(port 8729)</span>
                    </label>
                </div>

                <div class="md:col-span-5">
                    <label class="dv-toggle">
                        <input type="checkbox" name="ssh_enabled" value="1" class="dv-switch">
                        Aktifkan SSH untuk export konfigurasi
                    </label>
                </div>

                <div class="flex justify-end md:col-span-3">
                    <button type="submit" class="dv-btn dv-btn-primary h-9 w-full md:w-auto">
                        <i data-lucide="save"></i>
                        Simpan perangkat
                    </button>
                </div>
            </form>
        </div>

        {{-- DEVICE LIST --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-[#f2f2f2]">Registered devices</h2>
                <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Buka tiap service untuk mengubah koneksi, lalu klik Simpan</p>
            </div>

            <span class="rounded-full border border-[#3a3a3a] bg-[#242424] px-3 py-1 text-[11px] font-medium text-[#c4c4c4]">
                {{ $totalDevices }} perangkat
            </span>
        </div>

        <div class="grid gap-4">

            @forelse($devices as $device)

                @php
                    $apiOn = (bool) $device->api_enabled;
                    $sshOn = (bool) $device->ssh_enabled;
                    $state = in_array($device->status, ['online', 'offline'], true) ? $device->status : 'unknown';
                @endphp

                <article class="dv-card">

                    {{-- HEAD --}}
                    <div class="dv-head">
                        <div class="dv-identity">
                            <span class="dv-icon"><i data-lucide="router"></i></span>

                            <div class="min-w-0">
                                <div class="dv-name">{{ $device->name }}</div>
                                <div class="dv-ip">{{ $device->host }}</div>

                                @if($device->identity || $device->routeros_version || $device->board_name)
                                    <div class="dv-chips">
                                        @if($device->identity)
                                            <span class="dv-chip">{{ $device->identity }}</span>
                                        @endif
                                        @if($device->board_name)
                                            <span class="dv-chip">{{ $device->board_name }}</span>
                                        @endif
                                        @if($device->routeros_version)
                                            <span class="dv-chip">RouterOS {{ $device->routeros_version }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <span class="dv-pill {{ $state }}">
                            <i class="dv-dot"></i>
                            {{ strtoupper($device->status ?? 'unknown') }}
                            @if($device->last_checked_at)
                                <small>· {{ $device->last_checked_at->diffForHumans() }}</small>
                            @endif
                        </span>
                    </div>

                    <div class="dv-body">

                        {{-- SERVICES --}}
                        <div class="dv-services">
                            <div class="dv-title">Services</div>

                            <div class="dv-svc-list">

                                {{-- API --}}
                                <details class="dv-svc">
                                    <summary>
                                        <span class="dv-svc-info">
                                            <i data-lucide="plug"></i>
                                            API{{ $device->api_ssl ? '-SSL' : '' }}
                                            <small>:{{ $device->api_port ?: ($device->api_ssl ? 8729 : 8728) }}</small>
                                        </span>

                                        <span class="dv-svc-right">
                                            <span class="dv-pill {{ $svcClass($apiOn, $device->api_status) }}">
                                                <i class="dv-dot"></i>
                                                {{ $svcLabel($apiOn, $device->api_status) }}
                                                @if($device->api_last_checked_at)
                                                    <small>· {{ $device->api_last_checked_at->diffForHumans() }}</small>
                                                @endif
                                            </span>
                                            <i data-lucide="chevron-down" class="dv-chev"></i>
                                        </span>
                                    </summary>

                                    <div class="dv-svc-config">

                                        <div>
                                            <label class="dv-label">API port</label>
                                            <input form="update-mt-{{ $device->id }}" name="api_port" type="number"
                                                   value="{{ $device->api_port ?: ($device->api_ssl ? 8729 : 8728) }}"
                                                   min="1" max="65535" required class="dv-input">
                                        </div>

                                        <div>
                                            <label class="dv-label">API username</label>
                                            <input form="update-mt-{{ $device->id }}" name="api_username"
                                                   value="{{ $device->api_username ?: $device->username }}"
                                                   placeholder="API username" required autocomplete="off" class="dv-input">
                                        </div>

                                        <div class="dv-wide">
                                            <label class="dv-label">API password</label>
                                            <input form="update-mt-{{ $device->id }}" name="api_password" type="password"
                                                   value="" autocomplete="new-password"
                                                   placeholder="Kosongkan jika tidak ingin mengubah password" class="dv-input">
                                        </div>

                                        <input type="hidden" form="update-mt-{{ $device->id }}" name="api_ssl" value="0">

                                        <label class="dv-toggle">
                                            <input form="update-mt-{{ $device->id }}" type="checkbox" name="api_ssl" value="1"
                                                   @checked((bool) $device->api_ssl) class="dv-switch">
                                            Pakai API-SSL
                                        </label>

                                        <label class="dv-toggle">
                                            <input form="update-mt-{{ $device->id }}" type="checkbox" name="api_enabled" value="1"
                                                   @checked($apiOn) class="dv-switch">
                                            Aktifkan API
                                        </label>

                                        <p class="dv-note dv-wide">
                                            Gunakan user khusus dengan group read-only (policy: read, api).
                                            Batasi IP sumber di <code>/ip service</code> dan <code>/user</code>.
                                        </p>

                                        @if($device->api_last_error)
                                            <div class="dv-err dv-wide">
                                                <strong>Koneksi gagal:</strong> {{ $device->api_last_error }}
                                            </div>
                                        @endif
                                    </div>
                                </details>

                                {{-- SSH --}}
                                <details class="dv-svc">
                                    <summary>
                                        <span class="dv-svc-info">
                                            <i data-lucide="terminal"></i>
                                            SSH
                                            <small>:{{ $device->ssh_port ?: 22 }} · export</small>
                                        </span>

                                        <span class="dv-svc-right">
                                            <span class="dv-pill {{ $svcClass($sshOn, $device->ssh_status) }}">
                                                <i class="dv-dot"></i>
                                                {{ $svcLabel($sshOn, $device->ssh_status) }}
                                                @if($device->ssh_last_checked_at)
                                                    <small>· {{ $device->ssh_last_checked_at->diffForHumans() }}</small>
                                                @endif
                                            </span>
                                            <i data-lucide="chevron-down" class="dv-chev"></i>
                                        </span>
                                    </summary>

                                    <div class="dv-svc-config">

                                        <div class="dv-wide">
                                            <label class="dv-label">SSH port</label>
                                            <input form="update-mt-{{ $device->id }}" name="ssh_port" type="number"
                                                   value="{{ $device->ssh_port ?: 22 }}" min="1" max="65535" required class="dv-input">
                                        </div>

                                        <label class="dv-toggle dv-wide">
                                            <input form="update-mt-{{ $device->id }}" type="checkbox" name="ssh_enabled" value="1"
                                                   @checked($sshOn) class="dv-switch">
                                            Aktifkan SSH
                                        </label>

                                        @if($device->ssh_last_error)
                                            <div class="dv-err dv-wide">
                                                <strong>Koneksi gagal:</strong> {{ $device->ssh_last_error }}
                                            </div>
                                        @endif
                                    </div>
                                </details>

                            </div>

                            {{-- SNAPSHOT --}}
                            <div class="dv-snap">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="file-diff" class="h-3.5 w-3.5"></i>
                                    Snapshot terakhir:
                                    <span class="text-[#c4c4c4]">
                                        {{ $device->last_snapshot_at ? $device->last_snapshot_at->diffForHumans() : 'belum ada' }}
                                    </span>
                                </span>

                                <span class="dv-snap-actions">
                                    <button form="capture-mt-{{ $device->id }}" type="submit"
                                            class="dv-btn dv-btn-ghost" @disabled(! $sshOn)>
                                        Ambil snapshot
                                    </button>

                                    @if(Route::has('router.mikrotik.snapshots.index'))
                                        <a class="dv-link"
                                           href="{{ route('router.mikrotik.snapshots.index', ['router' => $device->name]) }}">
                                            Riwayat →
                                        </a>
                                    @endif
                                </span>
                            </div>

                            @if($device->last_error)
                                <div class="dv-err mt-3">{{ $device->last_error }}</div>
                            @endif
                        </div>

                        {{-- CONFIGURATION --}}
                        <form id="update-mt-{{ $device->id }}" method="post"
                              action="{{ route('router.mikrotik.update', $device) }}" class="dv-config">
                            @csrf
                            @method('PUT')

                            <input type="hidden" name="username" value="{{ $device->username }}">
                            <input type="hidden" name="is_active" value="0">

                            <div class="dv-title">Configuration</div>

                            <div class="grid gap-3">
                                <div>
                                    <label class="dv-label">Nama</label>
                                    <input name="name" value="{{ $device->name }}" required placeholder="Nama" class="dv-input">
                                </div>

                                <div>
                                    <label class="dv-label">IP / Hostname</label>
                                    <input name="host" value="{{ $device->host }}" required placeholder="IP / Hostname" class="dv-input mono">
                                </div>

                                <label class="dv-toggle mt-1">
                                    <input type="checkbox" name="is_active" value="1" @checked($device->is_active) class="dv-switch">
                                    Aktifkan perangkat
                                </label>
                            </div>
                        </form>

                    </div>

                    {{-- FOOTER ACTIONS --}}
                    <div class="dv-foot">
                        <span class="dv-note">Simpan menerapkan konfigurasi perangkat dan semua service sekaligus.</span>

                        <div class="dv-foot-actions">
                            <button form="test-mt-{{ $device->id }}" type="submit" class="dv-btn dv-btn-ghost">
                                <i data-lucide="activity"></i>
                                Test
                            </button>

                            <button form="update-mt-{{ $device->id }}" type="submit" class="dv-btn dv-btn-primary">
                                <i data-lucide="save"></i>
                                Simpan
                            </button>

                            <button form="delete-mt-{{ $device->id }}" type="submit" class="dv-btn dv-btn-danger"
                                    onclick="return confirm('Hapus perangkat ini?')">
                                <i data-lucide="trash-2"></i>
                                Hapus
                            </button>
                        </div>
                    </div>

                    {{-- SEPARATE ACTION FORMS --}}
                    <form id="test-mt-{{ $device->id }}" method="post" action="{{ route('router.mikrotik.test', $device) }}">
                        @csrf
                    </form>

                    <form id="capture-mt-{{ $device->id }}" method="post" action="{{ route('router.mikrotik.capture', $device) }}">
                        @csrf
                    </form>

                    <form id="delete-mt-{{ $device->id }}" method="post" action="{{ route('router.mikrotik.destroy', $device) }}">
                        @csrf
                        @method('DELETE')
                    </form>

                </article>

            @empty

                <div class="rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] px-5 py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="mb-3 grid h-11 w-11 place-items-center rounded-full border border-[#3a3a3a] bg-[#242424] text-[#777]">
                            <i data-lucide="router" class="h-5 w-5"></i>
                        </div>
                        <p class="text-sm font-medium text-[#d0d0d0]">Belum ada perangkat MikroTik</p>
                        <p class="mt-1 text-xs text-[#8a8a8a]">Tambahkan perangkat pertama untuk mulai memantau jaringan.</p>
                    </div>
                </div>

            @endforelse

        </div>

        {{-- FOOTER --}}
        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <div class="text-xs text-[#8a8a8a]">
                Menampilkan
                <span class="font-semibold text-[#f2f2f2]">{{ $devices->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-semibold text-[#f2f2f2]">{{ $devices->lastItem() ?? 0 }}</span>
                dari
                <span class="font-semibold text-[#f2f2f2]">{{ $totalDevices }}</span>
                perangkat
            </div>

            @if($devices->hasPages())
                <div class="jn-pagination">
                    {{ $devices->onEachSide(1)->links('pagination::tailwind') }}
                </div>
            @endif
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Panel tambah perangkat
            document.getElementById('toggleAddDevice')?.addEventListener('click', () => {
                document.getElementById('addDevicePanel').classList.toggle('hidden');
            });

            // API-SSL otomatis mengganti port 8728 <-> 8729
            document.querySelectorAll('input[name="api_ssl"][type="checkbox"]').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const formId = checkbox.getAttribute('form');

                    const portInput = formId
                        ? document.querySelector(`input[form="${formId}"][name="api_port"]`)
                        : checkbox.closest('form')?.querySelector('input[name="api_port"]');

                    if (!portInput) return;

                    if (['8728', '8729', ''].includes(String(portInput.value))) {
                        portInput.value = checkbox.checked ? '8729' : '8728';
                    }
                });
            });

        });
    </script>

</x-layouts.dashboard>
