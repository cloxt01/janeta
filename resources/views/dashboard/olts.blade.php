<x-layouts.dashboard title="OLT Devices">
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
    .device-add-form input,.device-add-form select,.device-edit input,.device-edit select{width:100%;border:1px solid #cbd5e1;border-radius:7px;background:#fff;padding:9px 10px;color:#1e293b;font-size:12px;outline:0}
    .device-add-form input:focus,.device-add-form select:focus,.device-edit input:focus,.device-edit select:focus{border-color:#64748b;box-shadow:0 0 0 3px rgb(100 116 139 / 11%)}
    .device-field label{display:block;margin-bottom:6px;color:#64748b;font-size:10px;font-weight:650}
    .device-card{overflow:hidden}
    .device-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid #e2e8f0}
    .device-identity{display:flex;gap:12px;min-width:0}
    .device-icon{display:grid;flex:0 0 36px;width:36px;height:36px;place-items:center;border:1px solid #cbd5e1;border-radius:9px;background:#f8fafc;color:#334155}
    .device-icon svg{width:18px;height:18px}
    .device-name{color:#0f172a;font-size:15px;font-weight:650}
    .device-ip{margin-top:4px;color:#64748b;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px}
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
    .device-edit{padding-left:20px;border-left:1px solid #e2e8f0}
    .device-edit-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .device-edit-grid .wide{grid-column:1/-1}
    .device-edit-grid label.device-toggle{display:flex;align-items:center;gap:8px;margin:0;color:#64748b;font-size:11px;font-weight:600}
    .device-edit-grid label.device-toggle input{flex:0 0 auto;width:15px;height:15px;margin:0}
    .device-actions{display:flex;justify-content:flex-end;gap:7px;margin-top:12px}
    .device-error{margin:0 20px 16px;border-radius:7px;background:#fdf8f8;padding:9px 11px;color:#9f4b4b;font-size:11px}
    @media(max-width:800px){.device-body{grid-template-columns:1fr}.device-edit{padding-top:18px;padding-left:0;border-top:1px solid #e2e8f0;border-left:0}}
    @media(max-width:560px){.device-card-head{flex-direction:column}.device-toolbar{align-items:flex-start;flex-direction:column}.device-edit-grid{grid-template-columns:1fr}}
</style>

<div class="devices-page">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div><p class="eyebrow mb-2">Infrastructure</p><h1 class="text-2xl font-semibold tracking-tight text-slate-900">OLT Devices</h1><p class="mt-1 text-sm text-slate-500">Satu perangkat untuk setiap alamat IP. Kelola service dan status koneksi dari blok perangkat.</p></div>
        <form method="post" action="{{ route('olts.test-all') }}">@csrf<button class="jn-button jn-button-secondary">Test semua koneksi</button></form>
    </div>

    <details class="device-add mb-5">
        <summary>Tambah perangkat OLT <i data-lucide="plus"></i></summary>
        <form method="post" action="{{ route('olts.store') }}" class="device-add-form md:grid-cols-4">@csrf
            <div class="device-field"><label>Nama perangkat</label><input name="name" placeholder="DUTA" required></div>
            <div class="device-field"><label>IP / Hostname</label><input name="host" placeholder="192.168.1.1" required></div>
            <div class="device-field"><label>SSH port</label><input name="ssh_port" type="number" value="22" min="1" max="65535"></div>
            <div class="device-field"><label>SNMP port</label><input name="snmp_port" type="number" value="161" min="1" max="65535"></div>
            <div class="device-field md:col-span-2"><label><input type="checkbox" name="ssh_enabled" value="1" checked class="mr-2 accent-slate-700">SSH aktif</label><input name="ssh_username" placeholder="SSH username" required></div>
            <div class="device-field md:col-span-2"><label><input type="checkbox" name="snmp_enabled" value="1" class="mr-2 accent-slate-700">SNMP aktif</label><input name="snmp_community" placeholder="SNMP community"></div>
            <div class="device-field md:col-span-2"><label>SSH password</label><input name="ssh_password" type="text" placeholder="Password SSH"></div>
            <div class="device-field md:col-span-2"><label>SSH private key (opsional)</label><textarea name="ssh_private_key" rows="2" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"></textarea></div>
            <button class="jn-button md:col-span-4">Simpan perangkat</button>
        </form>
    </details>

    <div class="device-toolbar mb-4"><div><div class="device-toolbar-title">Registered devices</div><div class="device-toolbar-meta">{{ $olts->total() }} perangkat terdaftar · status terakhir</div></div><i data-lucide="radio-tower" class="h-5 w-5 text-slate-400"></i></div>

    <div class="grid gap-4">
        @forelse($olts as $olt)
            <article class="device-card">
                <div class="device-card-head">
                    <div class="device-identity"><span class="device-icon"><i data-lucide="server"></i></span><div><div class="device-name">{{ $olt->name }}</div><div class="device-ip">IP address · {{ $olt->host }}</div></div></div>
                    <div class="device-state"><i class="device-state-dot {{ $olt->status }}"></i>{{ strtoupper($olt->status) }} @if($olt->last_checked_at)<span class="text-slate-400">· {{ $olt->last_checked_at->diffForHumans() }}</span>@endif</div>
                </div>
                <div class="device-body">
                    <div>
                        <div class="service-title">Services</div>
                        <div class="service-list">
                            <details class="service-row">
                                <summary><span class="service-info"><i data-lucide="terminal"></i>SSH <small class="font-normal text-slate-400">:{{ $olt->ssh_port ?: 22 }}</small></span><span class="service-status {{ !($olt->ssh_enabled ?? $olt->connection_type === 'ssh') ? 'muted' : ($olt->ssh_status === 'up' ? 'ok' : ($olt->ssh_status === 'down' ? 'down' : 'unknown')) }}"><strong>{{ !($olt->ssh_enabled ?? $olt->connection_type === 'ssh') ? 'OFF' : strtoupper($olt->ssh_status ?? 'UNKNOWN') }}</strong>@if($olt->ssh_last_checked_at)<small>{{ $olt->ssh_last_checked_at->diffForHumans() }}</small>@endif<i data-lucide="chevron-down" class="service-chevron"></i></span></summary>
                                <div class="service-config">
                                    <label>SSH port<input form="update-olt-{{ $olt->id }}" name="ssh_port" type="number" value="{{ $olt->ssh_port ?: 22 }}" min="1" max="65535" required></label>
                                    <label>Username<input form="update-olt-{{ $olt->id }}" name="ssh_username" value="{{ $olt->ssh_username ?: ($olt->connection_type === 'ssh' ? $olt->username : '') }}" placeholder="SSH username"></label>
                                    <label class="wide">Password<input form="update-olt-{{ $olt->id }}" name="ssh_password" type="text" placeholder="Kosongkan untuk mempertahankan password"></label>
                                    <label class="wide">Private key (opsional)<textarea form="update-olt-{{ $olt->id }}" name="ssh_private_key" rows="2" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"></textarea></label>
                                    <label class="wide service-toggle"><input form="update-olt-{{ $olt->id }}" type="checkbox" name="ssh_enabled" value="1" @checked($olt->ssh_enabled ?? $olt->connection_type === 'ssh') class="service-check"><span>Aktifkan SSH</span></label>
                                    @if($olt->ssh_last_error)<div class="wide rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"><strong>Koneksi gagal:</strong> {{ $olt->ssh_last_error }}</div>@endif
                                </div>
                            </details>
                            <details class="service-row">
                                <summary><span class="service-info"><i data-lucide="activity"></i>SNMP <small class="font-normal text-slate-400">:{{ $olt->snmp_port ?: 161 }}</small></span><span class="service-status {{ !($olt->snmp_enabled ?? $olt->connection_type === 'snmp') ? 'muted' : ($olt->snmp_status === 'up' ? 'ok' : ($olt->snmp_status === 'down' ? 'down' : 'unknown')) }}"><strong>{{ !($olt->snmp_enabled ?? $olt->connection_type === 'snmp') ? 'OFF' : strtoupper($olt->snmp_status ?? 'UNKNOWN') }}</strong>@if($olt->snmp_last_checked_at)<small>{{ $olt->snmp_last_checked_at->diffForHumans() }}</small>@endif<i data-lucide="chevron-down" class="service-chevron"></i></span></summary>
                                <div class="service-config">
                                    <label class="wide">SNMP port<input form="update-olt-{{ $olt->id }}" name="snmp_port" type="number" value="{{ $olt->snmp_port ?: 161 }}" min="1" max="65535" required></label>
                                    <label class="wide">Community<input form="update-olt-{{ $olt->id }}" name="snmp_community" value="{{ $olt->snmp_community ?: ($olt->connection_type === 'snmp' ? $olt->username : '') }}" placeholder="SNMP community"></label>
                                    <label class="wide service-toggle"><input form="update-olt-{{ $olt->id }}" type="checkbox" name="snmp_enabled" value="1" @checked($olt->snmp_enabled ?? $olt->connection_type === 'snmp') class="service-check"><span>Aktifkan SNMP</span></label>
                                </div>
                            </details>
                        </div>
                        @if($olt->last_error)<div class="mt-3 text-xs text-rose-600">{{ $olt->last_error }}</div>@endif
                    </div>
                    <form id="update-olt-{{ $olt->id }}" method="post" action="{{ route('olts.update', $olt) }}" class="device-edit">@csrf @method('PUT')
                        <div class="service-title">Configuration</div>
                        <div class="device-edit-grid">
                            <input name="name" value="{{ $olt->name }}" required placeholder="Nama">
                            <input name="host" value="{{ $olt->host }}" required placeholder="IP / Hostname">
                            <label class="wide device-toggle"><input type="checkbox" name="is_active" value="1" @checked($olt->is_active) class="accent-slate-700"><span>Aktifkan perangkat</span></label>
                        </div>
                        <div class="device-actions"><button form="test-olt-{{ $olt->id }}" class="jn-button jn-button-secondary" type="submit">Test</button><button class="jn-button" type="submit">Simpan</button><button form="delete-olt-{{ $olt->id }}" class="jn-button jn-button-danger" type="submit" onclick="return confirm('Hapus OLT ini?')">Hapus</button></div>
                    </form>
                </div>
                <form id="test-olt-{{ $olt->id }}" method="post" action="{{ route('olts.test', $olt) }}">@csrf</form>
                <form id="delete-olt-{{ $olt->id }}" method="post" action="{{ route('olts.destroy', $olt) }}">@csrf @method('DELETE')</form>
            </article>
        @empty
            <div class="device-card p-10 text-center text-sm text-slate-500">Belum ada perangkat OLT.</div>
        @endforelse
    </div>
    <div class="mt-5">{{ $olts->links() }}</div>
</div>
<script>
document.querySelectorAll('select[name="connection_type"]').forEach((select) => {
    select.addEventListener('change', (event) => {
        const container = event.target.closest('form') || event.target.closest('details');
        const port = container?.querySelector('input[name="port"]');
        const transport = container?.querySelector('select[name="transport_type"]');
        if (port) port.value = event.target.value === 'snmp' ? 161 : 22;
        if (transport) transport.value = event.target.value === 'snmp' ? 'udp' : 'tcp';
    });
});
</script>
</x-layouts.dashboard>
