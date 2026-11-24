
<x-layouts.dashboard title="MikroTik Devices">
    <style>
        .devices-page .eyebrow{color:#64748b;font-size:10px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}
        .device-toolbar,.device-card,.device-add{border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgb(15 23 42 / 4%)}
        .device-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px 18px}
        .device-toolbar-title{color:#0f172a;font-size:13px;font-weight:650}
        .device-toolbar-meta{margin-top:3px;color:#94a3b8;font-size:11px}
        .device-add{padding:18px}
        .device-add summary{display:flex;align-items:center;justify-content:space-between;cursor:pointer;color:#1e293b;font-size:13px;font-weight:650;list-style:none}
        .device-add summary::-webkit-details-marker{display:none}
        .device-add summary svg{width:16px;height:16px;color:#64748b}
        .device-add-form{display:grid;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0}
        .device-add-form input,.device-edit input{width:100%;border:1px solid #cbd5e1;border-radius:7px;background:#fff;padding:9px 10px;color:#1e293b;font-size:12px;outline:0}
        .device-add-form input:focus,.device-edit input:focus{border-color:#64748b;box-shadow:0 0 0 3px rgb(100 116 139 / 11%)}
        .device-add-form input[type=checkbox]{width:auto}
        .device-field label{display:block;margin-bottom:6px;color:#64748b;font-size:10px;font-weight:650}
        .device-card{overflow:hidden}
        .device-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid #e2e8f0}
        .device-identity{display:flex;gap:12px;min-width:0}
        .device-icon{display:grid;flex:0 0 36px;width:36px;height:36px;place-items:center;border:1px solid #cbd5e1;border-radius:9px;background:#f8fafc;color:#334155}
        .device-icon svg{width:18px;height:18px}
        .device-name{color:#0f172a;font-size:15px;font-weight:650}
        .device-ip{margin-top:4px;color:#64748b;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px}
        .device-meta{margin-top:6px;display:flex;flex-wrap:wrap;gap:6px}
        .device-chip{border-radius:999px;background:#f1f5f9;padding:2px 8px;color:#475569;font-size:10px;font-weight:600}
        .device-state{display:flex;align-items:center;gap:7px;white-space:nowrap;color:#475569;font-size:11px}
        .device-state-dot{width:7px;height:7px;border-radius:50%;background:#c28a2c}
        .device-state-dot.online{background:#16805b}.device-state-dot.offline{background:#b45353}
        .device-body{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(260px,.8fr);gap:20px;padding:18px 20px}
        .service-title{margin-bottom:10px;color:#64748b;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
        .service-list{display:grid;gap:7px}
        .service-row{border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;overflow:hidden}
        .service-row[open]{border-color:#cbd5e1;background:#fff}
        .service-row summary{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 12px;cursor:pointer;list-style:none}
        .service-row summary::-webkit-details-marker{display:none}
        .service-info{display:flex;align-items:center;gap:9px;color:#1e293b;font-size:12px;font-weight:600}
        .service-info svg{width:16px;height:16px;color:#64748b}
        .service-status{display:flex;align-items:center;gap:6px;color:#64748b;font-size:10px}
        .service-status strong{font-weight:650}
        .service-status.ok{color:#16805b}.service-status.muted{color:#94a3b8}
        .service-status.down{color:#b45353}.service-status.unknown{color:#a16207}
        .service-check{width:15px;height:15px;accent-color:#334155}
        .service-chevron{width:14px;height:14px;color:#94a3b8;transition:transform .15s ease}
        .service-row[open] .service-chevron{transform:rotate(180deg)}
        .service-config{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:0 12px 12px;border-top:1px solid #e2e8f0}
        .service-config label{display:block;margin-top:10px;color:#64748b;font-size:10px;font-weight:650}
        .service-config input{margin-top:5px;width:100%;border:1px solid #cbd5e1;border-radius:7px;background:#fff;padding:8px 9px;color:#1e293b;font-size:12px;outline:0}
        .service-config .wide{grid-column:1/-1}
        .service-config label.service-toggle{display:flex;align-items:center;gap:8px;margin-top:12px;color:#475569;font-size:11px;font-weight:600}
        .service-config label.service-toggle input{flex:0 0 auto;width:15px;height:15px;margin:0}
        .service-hint{grid-column:1/-1;margin-top:8px;color:#94a3b8;font-size:10px}
        .device-edit{padding-left:20px;border-left:1px solid #e2e8f0}
        .device-edit-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
        .device-edit-grid .wide{grid-column:1/-1}
        .device-edit-grid label.device-toggle{display:flex;align-items:center;gap:8px;margin:0;color:#64748b;font-size:11px;font-weight:600}
        .device-edit-grid label.device-toggle input{flex:0 0 auto;width:15px;height:15px;margin:0}
        .device-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:7px;margin-top:12px}
        .device-snapshot{margin-top:12px;display:flex;align-items:center;justify-content:space-between;gap:10px;border:1px dashed #cbd5e1;border-radius:8px;padding:9px 12px;color:#64748b;font-size:11px}
        @media(max-width:800px){.device-body{grid-template-columns:1fr}.device-edit{padding-top:18px;padding-left:0;border-top:1px solid #e2e8f0;border-left:0}}
        @media(max-width:560px){.device-card-head{flex-direction:column}.device-toolbar{align-items:flex-start;flex-direction:column}.device-edit-grid{grid-template-columns:1fr}}
    </style>

    @php
        $svcClass = fn (bool $enabled, ?string $status) => ! $enabled ? 'muted'
            : ($status === 'up' ? 'ok' : ($status === 'down' ? 'down' : 'unknown'));

        $svcLabel = fn (bool $enabled, ?string $status) => ! $enabled ? 'OFF' : strtoupper($status ?? 'UNKNOWN');
    @endphp

    <div class="devices-page">

        {{-- HEADER --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow mb-2">Infrastructure</p>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">MikroTik Devices</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Satu perangkat untuk setiap alamat IP. Koneksi lewat RouterOS API, dengan SSH opsional untuk export konfigurasi.
                </p>
            </div>

            <form method="post" action="{{ route('router.mikrotik.test-all') }}">
                @csrf
                <button class="jn-button jn-button-secondary">Test semua koneksi</button>
            </form>
        </div>

        {{-- ADD DEVICE --}}
        <details class="device-add mb-5">
            <summary>
                Tambah perangkat MikroTik
                <i data-lucide="plus"></i>
            </summary>

            <form method="post" action="{{ route('router.mikrotik.store') }}" class="device-add-form md:grid-cols-4">
                @csrf

                <div class="device-field">
                    <label>Nama perangkat</label>
                    <input name="name" placeholder="RB-POP-01" required>
                </div>

                <div class="device-field">
                    <label>IP / Hostname</label>
                    <input name="host" placeholder="192.168.88.1" required>
                </div>

                <div class="device-field">
                    <label>API port</label>
                    <input name="api_port" type="number" value="8728" min="1" max="65535">
                </div>

                <div class="device-field">
                    <label>SSH port (export)</label>
                    <input name="ssh_port" type="number" value="22" min="1" max="65535">
                </div>

                <div class="device-field md:col-span-2">
                    <label>Username</label>
                    <input name="username" placeholder="janeta-monitor" required>
                </div>

                <div class="device-field md:col-span-2">
                    <label>Password</label>
                    <input name="password" type="password" autocomplete="new-password" placeholder="Password API" required>
                </div>

                <input type="hidden" name="api_ssl" value="0">

                <div class="device-field md:col-span-2">
                    <label>
                        <input type="checkbox" name="api_ssl" value="1" class="mr-2 accent-slate-700">
                        Pakai API-SSL (biasanya port 8729)
                    </label>
                </div>

                <div class="device-field md:col-span-2">
                    <label>
                        <input type="checkbox" name="ssh_enabled" value="1" class="mr-2 accent-slate-700">
                        Aktifkan SSH untuk export konfigurasi
                    </label>
                </div>

                <button class="jn-button md:col-span-4">Simpan perangkat</button>
            </form>
        </details>

        {{-- DEVICE TOOLBAR --}}
        <div class="device-toolbar mb-4">
            <div>
                <div class="device-toolbar-title">Registered devices</div>
                <div class="device-toolbar-meta">
                    {{ $devices->total() }} perangkat terdaftar · status terakhir
                </div>
            </div>

            <i data-lucide="router" class="h-5 w-5 text-slate-400"></i>
        </div>

        {{-- DEVICE LIST --}}
        <div class="grid gap-4">

            @forelse($devices as $device)

                @php
                    $apiOn = (bool) $device->api_enabled;
                    $sshOn = (bool) $device->ssh_enabled;
                @endphp

                <article class="device-card">

                    {{-- DEVICE HEADER --}}
                    <div class="device-card-head">
                        <div class="device-identity">
                            <span class="device-icon">
                                <i data-lucide="router"></i>
                            </span>

                            <div>
                                <div class="device-name">{{ $device->name }}</div>

                                <div class="device-ip">
                                    IP address · {{ $device->host }}
                                </div>

                                @if($device->identity || $device->routeros_version || $device->board_name)
                                    <div class="device-meta">
                                        @if($device->identity)
                                            <span class="device-chip">{{ $device->identity }}</span>
                                        @endif

                                        @if($device->board_name)
                                            <span class="device-chip">{{ $device->board_name }}</span>
                                        @endif

                                        @if($device->routeros_version)
                                            <span class="device-chip">RouterOS {{ $device->routeros_version }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="device-state">
                            <i class="device-state-dot {{ $device->status }}"></i>

                            {{ strtoupper($device->status ?? 'unknown') }}

                            @if($device->last_checked_at)
                                <span class="text-slate-400">
                                    · {{ $device->last_checked_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="device-body">

                        {{-- SERVICES --}}
                        <div>
                            <div class="service-title">Services</div>

                            <div class="service-list">

                                {{-- ROUTEROS API --}}
                                <details class="service-row">
                                    <summary>
                                        <span class="service-info">
                                            <i data-lucide="plug"></i>
                                            API{{ $device->api_ssl ? '-SSL' : '' }}

                                            <small class="font-normal text-slate-400">
                                                :{{ $device->api_port ?: ($device->api_ssl ? 8729 : 8728) }}
                                            </small>
                                        </span>

                                        <span class="service-status {{ $svcClass($apiOn, $device->api_status) }}">
                                            <strong>{{ $svcLabel($apiOn, $device->api_status) }}</strong>

                                            @if($device->api_last_checked_at)
                                                <small>{{ $device->api_last_checked_at->diffForHumans() }}</small>
                                            @endif

                                            <i data-lucide="chevron-down" class="service-chevron"></i>
                                        </span>
                                    </summary>

                                    <div class="service-config">

                                        <label>
                                            API port
                                            <input
                                                form="update-mt-{{ $device->id }}"
                                                name="api_port"
                                                type="number"
                                                value="{{ $device->api_port ?: ($device->api_ssl ? 8729 : 8728) }}"
                                                min="1"
                                                max="65535"
                                                required
                                            >
                                        </label>

                                        <label>
                                            API username
                                            <input
                                                form="update-mt-{{ $device->id }}"
                                                name="api_username"
                                                value="{{ $device->api_username ?: $device->username }}"
                                                placeholder="API username"
                                                required
                                                autocomplete="off"
                                            >
                                        </label>

                                        <label class="wide">
                                            API password
                                            <input
                                                form="update-mt-{{ $device->id }}"
                                                name="api_password"
                                                type="password"
                                                value=""
                                                autocomplete="new-password"
                                                placeholder="Kosongkan jika tidak ingin mengubah password"
                                            >
                                        </label>

                                        <input
                                            type="hidden"
                                            form="update-mt-{{ $device->id }}"
                                            name="api_ssl"
                                            value="0"
                                        >

                                        <label class="wide service-toggle">
                                            <input
                                                form="update-mt-{{ $device->id }}"
                                                type="checkbox"
                                                name="api_ssl"
                                                value="1"
                                                @checked((bool) $device->api_ssl)
                                                class="service-check"
                                            >
                                            <span>Pakai API-SSL</span>
                                        </label>

                                        <label class="wide service-toggle">
                                            <input
                                                form="update-mt-{{ $device->id }}"
                                                type="checkbox"
                                                name="api_enabled"
                                                value="1"
                                                @checked($apiOn)
                                                class="service-check"
                                            >
                                            <span>Aktifkan API</span>
                                        </label>

                                        <p class="service-hint">
                                            Gunakan user khusus dengan group read-only (policy: read, api).
                                            Batasi IP sumber di <code>/ip service</code> dan <code>/user</code>.
                                        </p>

                                        @if($device->api_last_error)
                                            <div class="wide rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                                                <strong>Koneksi gagal:</strong>
                                                {{ $device->api_last_error }}
                                            </div>
                                        @endif

                                    </div>
                                </details>

                                {{-- SSH --}}
                                <details class="service-row">
                                    <summary>
                                        <span class="service-info">
                                            <i data-lucide="terminal"></i>
                                            SSH

                                            <small class="font-normal text-slate-400">
                                                :{{ $device->ssh_port ?: 22 }} · export
                                            </small>
                                        </span>

                                        <span class="service-status {{ $svcClass($sshOn, $device->ssh_status) }}">
                                            <strong>{{ $svcLabel($sshOn, $device->ssh_status) }}</strong>

                                            @if($device->ssh_last_checked_at)
                                                <small>{{ $device->ssh_last_checked_at->diffForHumans() }}</small>
                                            @endif

                                            <i data-lucide="chevron-down" class="service-chevron"></i>
                                        </span>
                                    </summary>

                                    <div class="service-config">

                                        <label class="wide">
                                            SSH port
                                            <input
                                                form="update-mt-{{ $device->id }}"
                                                name="ssh_port"
                                                type="number"
                                                value="{{ $device->ssh_port ?: 22 }}"
                                                min="1"
                                                max="65535"
                                                required
                                            >
                                        </label>

                                        <label class="wide service-toggle">
                                            <input
                                                form="update-mt-{{ $device->id }}"
                                                type="checkbox"
                                                name="ssh_enabled"
                                                value="1"
                                                @checked($sshOn)
                                                class="service-check"
                                            >
                                            <span>Aktifkan SSH</span>
                                        </label>

                                        @if($device->ssh_last_error)
                                            <div class="wide rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                                                <strong>Koneksi gagal:</strong>
                                                {{ $device->ssh_last_error }}
                                            </div>
                                        @endif

                                    </div>
                                </details>

                            </div>

                            {{-- SNAPSHOT --}}
                            <div class="device-snapshot">
                                <span>
                                    <i data-lucide="file-diff" class="mr-1 inline h-3.5 w-3.5"></i>

                                    Snapshot terakhir:

                                    {{ $device->last_snapshot_at ? $device->last_snapshot_at->diffForHumans() : 'belum ada' }}
                                </span>

                                <span class="flex gap-2">
                                    <button
                                        form="capture-mt-{{ $device->id }}"
                                        class="jn-button jn-button-secondary"
                                        type="submit"
                                        @disabled(! $sshOn)
                                    >
                                        Ambil snapshot
                                    </button>

                                    @if(Route::has('router.mikrotik.snapshots.index'))
                                        <a
                                            class="jn-link"
                                            href="{{ route('router.mikrotik.snapshots.index', ['router' => $device->name]) }}"
                                        >
                                            Riwayat
                                        </a>
                                    @endif
                                </span>
                            </div>

                            @if($device->last_error)
                                <div class="mt-3 text-xs text-rose-600">
                                    {{ $device->last_error }}
                                </div>
                            @endif
                        </div>


                        {{-- GENERAL CONFIGURATION --}}
                        <form
                            id="update-mt-{{ $device->id }}"
                            method="post"
                            action="{{ route('router.mikrotik.update', $device) }}"
                            class="device-edit"
                        >
                            @csrf
                            @method('PUT')

                            {{-- General username tetap dipertahankan --}}
                            <input
                                type="hidden"
                                name="username"
                                value="{{ $device->username }}"
                            >

                            {{-- Pastikan checkbox bisa mengirim nilai 0 --}}
                            <input
                                type="hidden"
                                name="is_active"
                                value="0"
                            >

                            <div class="service-title">Configuration</div>

                            <div class="device-edit-grid">

                                <input
                                    name="name"
                                    value="{{ $device->name }}"
                                    required
                                    placeholder="Nama"
                                >

                                <input
                                    name="host"
                                    value="{{ $device->host }}"
                                    required
                                    placeholder="IP / Hostname"
                                >

                                <label class="wide device-toggle">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        @checked($device->is_active)
                                        class="accent-slate-700"
                                    >
                                    <span>Aktifkan perangkat</span>
                                </label>

                            </div>

                            <div class="device-actions">

                                <button
                                    form="test-mt-{{ $device->id }}"
                                    class="jn-button jn-button-secondary"
                                    type="submit"
                                >
                                    Test
                                </button>

                                <button class="jn-button" type="submit">
                                    Simpan
                                </button>

                                <button
                                    form="delete-mt-{{ $device->id }}"
                                    class="jn-button jn-button-danger"
                                    type="submit"
                                    onclick="return confirm('Hapus perangkat ini?')"
                                >
                                    Hapus
                                </button>

                            </div>
                        </form>


                    </div>

                    {{-- SEPARATE ACTION FORMS --}}
                    <form
                        id="test-mt-{{ $device->id }}"
                        method="post"
                        action="{{ route('router.mikrotik.test', $device) }}"
                    >
                        @csrf
                    </form>

                    <form
                        id="capture-mt-{{ $device->id }}"
                        method="post"
                        action="{{ route('router.mikrotik.capture', $device) }}"
                    >
                        @csrf
                    </form>

                    <form
                        id="delete-mt-{{ $device->id }}"
                        method="post"
                        action="{{ route('router.mikrotik.destroy', $device) }}"
                    >
                        @csrf
                        @method('DELETE')
                    </form>

                </article>

            @empty

                <div class="device-card p-10 text-center text-sm text-slate-500">
                    Belum ada perangkat MikroTik.
                </div>

            @endforelse

        </div>

        <div class="mt-5">
            {{ $devices->links() }}
        </div>

    </div>

    {{-- API SSL PORT AUTOMATION --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            document.querySelectorAll('input[name="api_ssl"][type="checkbox"]').forEach((checkbox) => {

                checkbox.addEventListener('change', () => {

                    const formId = checkbox.getAttribute('form');

                    let portInput;

                    if (formId) {
                        portInput = document.querySelector(
                            `input[form="${formId}"][name="api_port"]`
                        );
                    } else {
                        const form = checkbox.closest('form');

                        portInput = form?.querySelector(
                            'input[name="api_port"]'
                        );
                    }

                    if (!portInput) return;

                    const currentPort = String(portInput.value);

                    if (['8728', '8729', ''].includes(currentPort)) {
                        portInput.value = checkbox.checked ? '8729' : '8728';
                    }

                });

            });

        });
    </script>

</x-layouts.dashboard>
