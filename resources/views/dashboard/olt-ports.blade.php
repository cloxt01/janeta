<x-layouts.dashboard title="Port Monitor">
<style>
    .port-page .eyebrow{color:#64748b;font-size:10px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}
    .port-toolbar,.port-device,.port-slot,.port-stat{border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgb(15 23 42 / 4%)}
    .port-toolbar{padding:16px}
    .port-toolbar label{color:#64748b;font-size:11px;font-weight:600}
    .port-toolbar select,.port-toolbar input{margin-top:7px;width:100%;border:1px solid #cbd5e1;border-radius:7px;background:#fff;padding:9px 11px;color:#1e293b;font-size:13px;outline:0}
    .port-toolbar select:focus,.port-toolbar input:focus{border-color:#64748b;box-shadow:0 0 0 3px rgb(100 116 139 / 12%)}
    .port-device{padding:20px}
    .port-device-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;border-bottom:1px solid #e2e8f0;padding-bottom:18px}
    .port-device-title{display:flex;align-items:center;gap:11px;color:#0f172a;font-size:17px;font-weight:650}
    .port-device-icon{display:grid;width:36px;height:36px;place-items:center;border:1px solid #cbd5e1;border-radius:9px;background:#f8fafc;color:#334155}
    .port-device-icon svg{width:18px;height:18px}
    .port-device-meta{margin-top:5px;color:#64748b;font-size:12px}
    .port-updated{color:#94a3b8;font-size:11px;text-align:right}
    .port-updated strong{display:block;margin-top:3px;color:#475569;font-weight:500}
    .port-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:18px 0}
    .port-stat{padding:14px 15px;box-shadow:none}
    .port-stat-label{color:#64748b;font-size:11px}
    .port-stat-value{margin-top:5px;color:#0f172a;font-size:21px;font-weight:650;line-height:1}
    .port-stat.online .port-stat-value{color:#16805b}
    .port-stat.offline .port-stat-value{color:#a16207}
    .port-stat.los .port-stat-value{color:#b45353}
    .port-stat-note{margin-top:6px;color:#94a3b8;font-size:10px}
    .port-slot-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px}
    .port-slot{padding:14px;box-shadow:none}
    .port-slot-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
    .port-slot-name{color:#1e293b;font-size:12px;font-weight:650}
    .port-slot-count{color:#94a3b8;font-size:10px}
    .port-slot-summary{display:flex;gap:9px;margin-bottom:12px;color:#64748b;font-size:10px}
    .port-slot-summary span{display:inline-flex;align-items:center;gap:4px}
    .port-dot{width:6px;height:6px;border-radius:50%;background:#cbd5e1}
    .port-dot.online{background:#16805b}.port-dot.los{background:#b45353}.port-dot.offline{background:#c28a2c}
    .port-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px}
    .port-item{display:flex;min-height:42px;flex-direction:column;align-items:center;justify-content:center;gap:4px;border:1px solid #e2e8f0;border-radius:7px;background:#f8fafc;color:#64748b;font-size:10px}
    .port-item:hover{border-color:#94a3b8;background:#f1f5f9;color:#1e293b}
    .port-item.online{border-color:#c9e5d8;background:#f5fbf7;color:#287a5b}
    .port-item.los{border-color:#e5caca;background:#fdf8f8;color:#9f4b4b}
    .port-item.offline{border-color:#eadbbd;background:#fdfaf3;color:#a16207}
    .port-item .port-led{width:7px;height:7px;border-radius:50%;background:#cbd5e1}
    .port-item.online .port-led{background:#16805b}.port-item.los .port-led{background:#b45353}.port-item.offline .port-led{background:#c28a2c}
    .port-legend{display:flex;flex-wrap:wrap;gap:14px;margin-top:18px;color:#64748b;font-size:11px}
    .port-legend span{display:inline-flex;align-items:center;gap:6px}
    @media(max-width:700px){.port-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.port-device-head{flex-direction:column}.port-updated{text-align:left}}
</style>

@php
    $selectedOlt = $olts->firstWhere('name', $oltName);
    $summary = $scan['summary'] ?? [];
@endphp

<div class="port-page">
    <div class="mb-6">
        <p class="eyebrow mb-2">Infrastructure</p>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Port Monitor</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau kesehatan port GPON dan distribusi ONT dari satu tampilan.</p>
    </div>

    <form method="get" action="{{ route('olts.ports') }}" class="port-toolbar mb-5 grid gap-3 md:grid-cols-4">
        <label>Perangkat
            <select name="olt" onchange="this.form.submit()">
                @foreach($olts as $olt)<option value="{{ $olt->name }}" @selected($oltName === $olt->name)>{{ $olt->name }} · {{ $olt->host }}</option>@endforeach
            </select>
        </label>
        <label>Frame
            <input name="frame" value="{{ $frame }}" inputmode="numeric">
        </label>
        <div class="flex items-end md:col-span-2"><button class="jn-button w-full">Refresh data</button></div>
    </form>

    @if($error)
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $error }}</div>
    @endif

    @if($scan)
        <section class="port-device">
            <div class="port-device-head">
                <div>
                    <div class="port-device-title"><span class="port-device-icon"><i data-lucide="server"></i></span>{{ $selectedOlt?->name ?? $scan['olt'] }} <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-500">Frame {{ $scan['frame'] }}</span></div>
                    <p class="port-device-meta">{{ $selectedOlt?->host ?? 'OLT device' }} · {{ strtoupper($selectedOlt?->connection_type ?? 'connection') }}</p>
                </div>
                <div class="port-updated">Terakhir diperbarui<strong>{{ $scan['scanned_at']->format('d M Y, H:i:s') }}</strong></div>
            </div>

            <div class="port-stats">
                <div class="port-stat"><div class="port-stat-label">Total ONT</div><div class="port-stat-value">{{ $summary['totalOnt'] ?? 0 }}</div><div class="port-stat-note">terdeteksi</div></div>
                <div class="port-stat online"><div class="port-stat-label">Online</div><div class="port-stat-value">{{ $summary['online'] ?? 0 }}</div><div class="port-stat-note">koneksi aktif</div></div>
                <div class="port-stat offline"><div class="port-stat-label">Offline</div><div class="port-stat-value">{{ $summary['offline'] ?? 0 }}</div><div class="port-stat-note">perlu diperiksa</div></div>
                <div class="port-stat los"><div class="port-stat-label">LOS</div><div class="port-stat-value">{{ $summary['los'] ?? 0 }}</div><div class="port-stat-note">gangguan sinyal</div></div>
            </div>

            <div class="port-slot-grid">
                @foreach($scan['slots'] as $slotScan)
                    <article class="port-slot">
                        <div class="port-slot-head"><span class="port-slot-name">Slot {{ $slotScan['slot'] }}</span><span class="port-slot-count">{{ $slotScan['summary']['totalOnt'] ?? 0 }} ONT</span></div>
                        <div class="port-slot-summary"><span><i class="port-dot online"></i>{{ $slotScan['summary']['online'] ?? 0 }}</span><span><i class="port-dot offline"></i>{{ $slotScan['summary']['offline'] ?? 0 }}</span><span><i class="port-dot los"></i>{{ $slotScan['summary']['los'] ?? 0 }}</span></div>
                        <div class="port-grid">
                            @foreach($slotScan['ports'] as $port)
                                <div class="port-item {{ $port['state'] }}" title="Port {{ $port['port'] }} · {{ $port['totalOnt'] ?? 0 }} ONT · Online {{ $port['online'] }} · Offline {{ $port['offline'] }} · LOS {{ $port['los'] }}"><i class="port-led"></i><span>{{ $port['port'] }}</span></div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="port-legend"><span><i class="port-dot online"></i>Aktif</span><span><i class="port-dot offline"></i>Offline</span><span><i class="port-dot los"></i>LOS / gangguan sinyal</span></div>
        </section>
    @endif
</div>
</x-layouts.dashboard>
