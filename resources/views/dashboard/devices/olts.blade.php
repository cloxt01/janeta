<x-layouts.dashboard title="OLT Devices">

    @php
        $svcClass = fn (bool $enabled, ?string $status) => ! $enabled ? 'muted'
            : ($status === 'up' ? 'online' : ($status === 'down' ? 'offline' : 'unknown'));

        $svcLabel = fn (bool $enabled, ?string $status) => ! $enabled ? 'OFF' : strtoupper($status ?? 'UNKNOWN');

        $totalDevices = $olts->total();
        $pageDevices  = $olts->getCollection();
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
        .dv-err { border:1px solid #5e2b2b; border-radius:12px; background:#2a1717; padding:9px 12px; color:#f08a8a; font-size:11px; line-height:1.5; }

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
                    Infrastructure · OLT
                </div>

                <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
                    OLT <span class="text-[#8a8a8a]">devices.</span>
                </h1>

                <p class="mt-2.5 max-w-xl text-xs text-[#a0a0a0]">
                    {{ $totalDevices }} perangkat · satu perangkat untuk setiap alamat IP. Kelola service dan status koneksi dari blok perangkat.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <form method="post" action="{{ route('olts.test-all') }}">
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
                <h2 class="text-sm font-semibold text-[#f2f2f2]">Tambah perangkat OLT</h2>
                <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Aktifkan SSH, SNMP, atau keduanya sesuai kemampuan perangkat</p>
            </div>

            <form method="post" action="{{ route('olts.store') }}"
                  class="grid grid-cols-1 gap-4 p-5 md:grid-cols-12">
                @csrf

                <div class="md:col-span-3">
                    <label class="dv-label">Nama perangkat</label>
                    <input name="name" value="{{ old('name') }}" placeholder="DUTA" required class="dv-input">
                </div>

                <div class="md:col-span-3">
                    <label class="dv-label">IP / Hostname</label>
                    <input name="host" value="{{ old('host') }}" placeholder="192.168.1.1" required class="dv-input">
                </div>

                <div class="md:col-span-3">
                    <label class="dv-label">SSH port</label>
                    <input name="ssh_port" type="number" value="{{ old('ssh_port', 22) }}" min="1" max="65535" class="dv-input">
                </div>

                <div class="md:col-span-3">
                    <label class="dv-label">SNMP port</label>
                    <input name="snmp_port" type="number" value="{{ old('snmp_port', 161) }}" min="1" max="65535" class="dv-input">
                </div>

                <div class="md:col-span-6">
                    <div class="mb-2 flex items-center justify-between">
                        <label class="dv-label !mb-0">SSH username</label>
                        <label class="dv-toggle !text-[11px]">
                            <input type="checkbox" name="ssh_enabled" value="1" checked class="dv-switch">
                            SSH aktif
                        </label>
                    </div>
                    <input name="ssh_username" value="{{ old('ssh_username') }}" placeholder="SSH username" required class="dv-input">
                </div>

                <div class="md:col-span-6">
                    <div class="mb-2 flex items-center justify-between">
                        <label class="dv-label !mb-0">SNMP community</label>
                        <label class="dv-toggle !text-[11px]">
                            <input type="checkbox" name="snmp_enabled" value="1" class="dv-switch">
                            SNMP aktif
                        </label>
                    </div>
                    <input name="snmp_community" value="{{ old('snmp_community') }}" placeholder="SNMP community" class="dv-input">
                </div>

                <div class="md:col-span-6">
                    <label class="dv-label">SSH password</label>
                    <input name="ssh_password" type="password" autocomplete="new-password" placeholder="Password SSH" class="dv-input">
                </div>

                <div class="md:col-span-6">
                    <label class="dv-label">SSH private key (opsional)</label>
                    <textarea name="ssh_private_key" rows="2" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"
                              class="dv-input mono">{{ old('ssh_private_key') }}</textarea>
                </div>

                <div class="flex justify-end md:col-span-12">
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

            @forelse($olts as $olt)

                @php
                    $sshOn  = (bool) ($olt->ssh_enabled ?? ($olt->connection_type === 'ssh'));
                    $snmpOn = (bool) ($olt->snmp_enabled ?? ($olt->connection_type === 'snmp'));
                    $state  = in_array($olt->status, ['online', 'offline'], true) ? $olt->status : 'unknown';
                @endphp

                <article class="dv-card">

                    {{-- HEAD --}}
                    <div class="dv-head">
                        <div class="dv-identity">
                            <span class="dv-icon"><i data-lucide="server"></i></span>

                            <div class="min-w-0">
                                <div class="dv-name">{{ $olt->name }}</div>
                                <div class="dv-ip">{{ $olt->host }}</div>
                            </div>
                        </div>

                        <span class="dv-pill {{ $state }}">
                            <i class="dv-dot"></i>
                            {{ strtoupper($olt->status ?? 'unknown') }}
                            @if($olt->last_checked_at)
                                <small>· {{ $olt->last_checked_at->diffForHumans() }}</small>
                            @endif
                        </span>
                    </div>

                    <div class="dv-body">

                        {{-- SERVICES --}}
                        <div class="dv-services">
                            <div class="dv-title">Services</div>

                            <div class="dv-svc-list">

                                {{-- SSH --}}
                                <details class="dv-svc">
                                    <summary>
                                        <span class="dv-svc-info">
                                            <i data-lucide="terminal"></i>
                                            SSH
                                            <small>:{{ $olt->ssh_port ?: 22 }}</small>
                                        </span>

                                        <span class="dv-svc-right">
                                            <span class="dv-pill {{ $svcClass($sshOn, $olt->ssh_status) }}">
                                                <i class="dv-dot"></i>
                                                {{ $svcLabel($sshOn, $olt->ssh_status) }}
                                                @if($olt->ssh_last_checked_at)
                                                    <small>· {{ $olt->ssh_last_checked_at->diffForHumans() }}</small>
                                                @endif
                                            </span>
                                            <i data-lucide="chevron-down" class="dv-chev"></i>
                                        </span>
                                    </summary>

                                    <div class="dv-svc-config">

                                        <div>
                                            <label class="dv-label">SSH port</label>
                                            <input form="update-olt-{{ $olt->id }}" name="ssh_port" type="number"
                                                   value="{{ $olt->ssh_port ?: 22 }}" min="1" max="65535" required class="dv-input">
                                        </div>

                                        <div>
                                            <label class="dv-label">Username</label>
                                            <input form="update-olt-{{ $olt->id }}" name="ssh_username"
                                                   value="{{ $olt->ssh_username ?: ($olt->connection_type === 'ssh' ? $olt->username : '') }}"
                                                   placeholder="SSH username" class="dv-input">
                                        </div>

                                        <div class="dv-wide">
                                            <label class="dv-label">Password</label>
                                            <input form="update-olt-{{ $olt->id }}" name="ssh_password" type="password"
                                                   autocomplete="new-password"
                                                   placeholder="Kosongkan untuk mempertahankan password" class="dv-input">
                                        </div>

                                        <div class="dv-wide">
                                            <label class="dv-label">Private key (opsional)</label>
                                            <textarea form="update-olt-{{ $olt->id }}" name="ssh_private_key" rows="2"
                                                      placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"
                                                      class="dv-input mono"></textarea>
                                        </div>

                                        <label class="dv-toggle dv-wide">
                                            <input form="update-olt-{{ $olt->id }}" type="checkbox" name="ssh_enabled" value="1"
                                                   @checked($sshOn) class="dv-switch">
                                            Aktifkan SSH
                                        </label>

                                        @if($olt->ssh_last_error)
                                            <div class="dv-err dv-wide">
                                                <strong>Koneksi gagal:</strong> {{ $olt->ssh_last_error }}
                                            </div>
                                        @endif
                                    </div>
                                </details>

                                {{-- SNMP --}}
                                <details class="dv-svc">
                                    <summary>
                                        <span class="dv-svc-info">
                                            <i data-lucide="activity"></i>
                                            SNMP
                                            <small>:{{ $olt->snmp_port ?: 161 }}</small>
                                        </span>

                                        <span class="dv-svc-right">
                                            <span class="dv-pill {{ $svcClass($snmpOn, $olt->snmp_status) }}">
                                                <i class="dv-dot"></i>
                                                {{ $svcLabel($snmpOn, $olt->snmp_status) }}
                                                @if($olt->snmp_last_checked_at)
                                                    <small>· {{ $olt->snmp_last_checked_at->diffForHumans() }}</small>
                                                @endif
                                            </span>
                                            <i data-lucide="chevron-down" class="dv-chev"></i>
                                        </span>
                                    </summary>

                                    <div class="dv-svc-config">

                                        <div>
                                            <label class="dv-label">SNMP port</label>
                                            <input form="update-olt-{{ $olt->id }}" name="snmp_port" type="number"
                                                   value="{{ $olt->snmp_port ?: 161 }}" min="1" max="65535" required class="dv-input">
                                        </div>

                                        <div>
                                            <label class="dv-label">Community</label>
                                            <input form="update-olt-{{ $olt->id }}" name="snmp_community"
                                                   value="{{ $olt->snmp_community ?: ($olt->connection_type === 'snmp' ? $olt->username : '') }}"
                                                   placeholder="SNMP community" class="dv-input">
                                        </div>

                                        <label class="dv-toggle dv-wide">
                                            <input form="update-olt-{{ $olt->id }}" type="checkbox" name="snmp_enabled" value="1"
                                                   @checked($snmpOn) class="dv-switch">
                                            Aktifkan SNMP
                                        </label>
                                    </div>
                                </details>

                            </div>

                            @if($olt->last_error)
                                <div class="dv-err mt-3">{{ $olt->last_error }}</div>
                            @endif
                        </div>

                        {{-- CONFIGURATION --}}
                        <form id="update-olt-{{ $olt->id }}" method="post"
                              action="{{ route('olts.update', $olt) }}" class="dv-config">
                            @csrf
                            @method('PUT')

                            <div class="dv-title">Configuration</div>

                            <div class="grid gap-3">
                                <div>
                                    <label class="dv-label">Nama</label>
                                    <input name="name" value="{{ $olt->name }}" required placeholder="Nama" class="dv-input">
                                </div>

                                <div>
                                    <label class="dv-label">IP / Hostname</label>
                                    <input name="host" value="{{ $olt->host }}" required placeholder="IP / Hostname" class="dv-input mono">
                                </div>

                                <label class="dv-toggle mt-1">
                                    <input type="checkbox" name="is_active" value="1" @checked($olt->is_active) class="dv-switch">
                                    Aktifkan perangkat
                                </label>
                            </div>
                        </form>

                    </div>

                    {{-- FOOTER ACTIONS --}}
                    <div class="dv-foot">
                        <span class="dv-note">Simpan menerapkan konfigurasi perangkat dan semua service sekaligus.</span>

                        <div class="dv-foot-actions">
                            <button form="test-olt-{{ $olt->id }}" type="submit" class="dv-btn dv-btn-ghost">
                                <i data-lucide="activity"></i>
                                Test
                            </button>

                            <button form="update-olt-{{ $olt->id }}" type="submit" class="dv-btn dv-btn-primary">
                                <i data-lucide="save"></i>
                                Simpan
                            </button>

                            <button form="delete-olt-{{ $olt->id }}" type="submit" class="dv-btn dv-btn-danger"
                                    onclick="return confirm('Hapus OLT ini?')">
                                <i data-lucide="trash-2"></i>
                                Hapus
                            </button>
                        </div>
                    </div>

                    {{-- SEPARATE ACTION FORMS --}}
                    <form id="test-olt-{{ $olt->id }}" method="post" action="{{ route('olts.test', $olt) }}">
                        @csrf
                    </form>

                    <form id="delete-olt-{{ $olt->id }}" method="post" action="{{ route('olts.destroy', $olt) }}">
                        @csrf
                        @method('DELETE')
                    </form>

                </article>

            @empty

                <div class="rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] px-5 py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="mb-3 grid h-11 w-11 place-items-center rounded-full border border-[#3a3a3a] bg-[#242424] text-[#777]">
                            <i data-lucide="server" class="h-5 w-5"></i>
                        </div>
                        <p class="text-sm font-medium text-[#d0d0d0]">Belum ada perangkat OLT</p>
                        <p class="mt-1 text-xs text-[#8a8a8a]">Tambahkan OLT pertama untuk mulai memantau jaringan fiber.</p>
                    </div>
                </div>

            @endforelse

        </div>

        {{-- FOOTER --}}
        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <div class="text-xs text-[#8a8a8a]">
                Menampilkan
                <span class="font-semibold text-[#f2f2f2]">{{ $olts->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-semibold text-[#f2f2f2]">{{ $olts->lastItem() ?? 0 }}</span>
                dari
                <span class="font-semibold text-[#f2f2f2]">{{ $totalDevices }}</span>
                perangkat
            </div>

            @if($olts->hasPages())
                <div class="jn-pagination">
                    {{ $olts->onEachSide(1)->links('pagination::tailwind') }}
                </div>
            @endif
        </div>

    </div>

    <script>
        // Panel tambah perangkat
        document.getElementById('toggleAddDevice')?.addEventListener('click', () => {
            document.getElementById('addDevicePanel').classList.toggle('hidden');
        });
    </script>

</x-layouts.dashboard>
