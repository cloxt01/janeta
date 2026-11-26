<x-layouts.dashboard title="Port Monitor">
    <style>
        .port-page .eyebrow{color:#64748b;font-size:10px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}
        .port-toolbar,.port-device,.port-stat{border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgb(15 23 42 / 4%)}
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

        /* ---------- Chassis ---------- */
        .rack-scroll{overflow-x:auto;padding:2px 2px 14px}
        .chassis{margin:0 auto}
        .chassis-top{height:15px;margin:0 1.2%;background:linear-gradient(180deg,#a3a6ab,#8d9096);clip-path:polygon(2.5% 0,97.5% 0,100% 100%,0 100%);position:relative}
        .chassis-top::after{content:"";position:absolute;inset:0;background:repeating-linear-gradient(90deg,rgb(0 0 0 / 7%) 0 1px,transparent 1px 3px);opacity:.6}
        .chassis-face{position:relative;display:flex;align-items:stretch;gap:20px;padding:15px 30px 13px;border-radius:0 0 4px 4px;background:linear-gradient(180deg,#d9dbde 0%,#c6c9cd 55%,#b8bbc0 100%);box-shadow:inset 0 1px 0 rgb(255 255 255 / 65%),inset 0 -1px 0 rgb(0 0 0 / 15%),0 14px 18px -10px rgb(15 23 42 / 40%)}
        .chassis-face::before,.chassis-face::after{content:"";position:absolute;top:50%;width:7px;height:7px;margin-top:-3.5px;border-radius:50%;background:radial-gradient(circle at 35% 30%,#e5e7ea,#7d8087);box-shadow:inset 0 0 0 1px rgb(0 0 0 / 22%)}
        .chassis-face::before{left:11px}.chassis-face::after{right:11px}

        .chassis-left{flex:0 0 auto;display:flex;align-items:center;gap:18px}
        .chassis-vent{width:110px;height:62px;border-radius:2px;background-color:#2a2d33;background-image:radial-gradient(circle,#8b8f96 0 .9px,transparent 1.1px);background-size:4px 4px;box-shadow:inset 0 1px 3px rgb(0 0 0 / 55%),0 1px 0 rgb(255 255 255 / 50%)}
        .chassis-console{display:flex;flex-direction:column;align-items:center;gap:5px}
        .db9{width:34px;height:15px;clip-path:polygon(7% 0,93% 0,100% 100%,0 100%);background:linear-gradient(#5b6068,#2d3036);display:grid;place-items:center}
        .db9::after{content:"";width:70%;height:55%;background:#0d0f12;clip-path:polygon(6% 0,94% 0,100% 100%,0 100%)}
        .chassis-label{color:#5a5e65;font-size:8.5px;font-weight:600;letter-spacing:.06em}

        .chassis-slots{flex:1;display:flex;gap:10px}
        .slot-group{display:flex;flex-direction:column;gap:5px;min-width:0}
        .slot-group-head{display:flex;align-items:baseline;justify-content:space-between;gap:8px;padding:0 3px;color:#4b4f56;font-size:10px;font-weight:650;white-space:nowrap;overflow:hidden}
        .slot-group-head small{color:#6b6f76;font-size:9px;font-weight:500}
        .slot-group{flex-shrink:0}
        .empty-note{margin-top:4px;color:#94a3b8;font-size:11px;text-align:center}
        .sfp-bank{flex:1;display:grid;grid-auto-flow:column;grid-template-rows:repeat(2,auto);grid-auto-columns:minmax(0,1fr);gap:7px 8px;padding:9px 10px;border-radius:3px;background:linear-gradient(180deg,#b3b6bb,#bfc2c6);box-shadow:inset 0 1px 3px rgb(0 0 0 / 25%),0 1px 0 rgb(255 255 255 / 55%)}
        .sfp{display:flex;flex-direction:column;align-items:center;gap:3px;cursor:default;outline:0}
        .sfp-led{width:6px;height:6px;border-radius:50%;background:#6c7077;box-shadow:inset 0 1px 1px rgb(0 0 0 / 50%)}
        .sfp-cage{position:relative;width:100%;height:21px;border:1px solid #6e737b;border-radius:2px;background:linear-gradient(180deg,#3a3e46,#14171b);box-shadow:inset 0 1px 0 rgb(255 255 255 / 12%)}
        .sfp-cage::after{content:"";position:absolute;inset:5px 4px 3px;border-radius:1px;background:#050607;box-shadow:inset 0 1px 2px rgb(0 0 0 / 90%)}
        .sfp-num{color:#4b4f56;font-size:8.5px;font-weight:600;line-height:1}
        .sfp:hover .sfp-cage,.sfp:focus .sfp-cage,.sfp.is-active .sfp-cage{border-color:#1e293b;box-shadow:0 0 0 2px rgb(15 23 42 / 25%)}
        .sfp.online .sfp-led{background:#22c55e;box-shadow:0 0 0 1px rgb(21 128 61 / 55%),0 0 7px 2px rgb(34 197 94 / 60%)}
        .sfp.online .sfp-cage{border-color:#4d8a69;box-shadow:inset 0 1px 0 rgb(255 255 255 / 12%),inset 0 0 8px rgb(34 197 94 / 28%)}
        .sfp.los .sfp-led{background:#9c4a4a}

        .port-detail{display:flex;flex-wrap:wrap;align-items:center;gap:6px 22px;min-height:44px;margin-top:6px;padding:11px 16px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#64748b;font-size:12px}
        .port-detail b{color:#0f172a;font-weight:650}
        .port-detail .pd-state{display:inline-flex;align-items:center;gap:6px;font-weight:650;color:#475569}
        .port-detail .pd-state.online{color:#16805b}
        .port-detail .pd-state.los{color:#b45353}

        .slot-table{width:100%;margin-top:20px;border-collapse:collapse;font-size:12px}
        .slot-table th{padding:9px 12px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:11px;font-weight:600;text-align:left}
        .slot-table td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#334155}
        .slot-table th:not(:first-child),.slot-table td:not(:first-child){text-align:right;font-variant-numeric:tabular-nums}
        .slot-table td:first-child{font-weight:650;color:#0f172a}
        .slot-table .c-online{color:#16805b}.slot-table .c-offline{color:#a16207}.slot-table .c-los{color:#b45353}
        .slot-table tfoot td{border-bottom:0;font-weight:650;color:#0f172a}
        .table-scroll{overflow-x:auto}

        .port-legend{display:flex;flex-wrap:wrap;gap:16px;margin-top:22px;padding-top:16px;border-top:1px solid #e2e8f0;color:#64748b;font-size:11px}
        .port-legend span{display:inline-flex;align-items:center;gap:6px}
        .port-dot{width:7px;height:7px;border-radius:50%;background:#cbd5e1}
        .port-dot.online{background:#22c55e;box-shadow:0 0 5px 1px rgb(34 197 94 / 50%)}
        .port-dot.offline{background:#6c7077}
        .port-dot.los{background:#9c4a4a}
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

        <form method="get" action="{{ route('tools.port-monitor') }}" class="port-toolbar mb-5 grid gap-3 md:grid-cols-4">
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

                @php
                    $slots = collect($scan['slots']);
                    $activeSlots = $slots->filter(fn ($sl) => count($sl['ports']) > 0);
                    $emptySlots = $slots->filter(fn ($sl) => count($sl['ports']) === 0)->pluck('slot');
                    $totalPorts = $activeSlots->sum(fn ($sl) => count($sl['ports']));
                    $chassisMin = 300 + ($totalPorts / 2) * 36 + $activeSlots->count() * 34;
                @endphp

                <div class="rack-scroll">
                    <div class="chassis" style="min-width:{{ (int) $chassisMin }}px">
                        <div class="chassis-top"></div>
                        <div class="chassis-face">
                            <div class="chassis-left">
                                <div class="chassis-vent"></div>
                                <div class="chassis-console"><div class="db9"></div><span class="chassis-label">CONSOLE</span></div>
                            </div>

                            <div class="chassis-slots">
                                @foreach($activeSlots as $slotScan)
                                    @php $ports = collect($slotScan['ports']); $cols = (int) ceil($ports->count() / 2); @endphp
                                    <div class="slot-group" style="flex:{{ max($cols, 1) }} 1 {{ max($cols, 1) * 30 }}px">
                                        <div class="slot-group-head"><span>Slot {{ $slotScan['slot'] }}</span><small>{{ $ports->where('state', 'online')->count() }}/{{ $ports->count() }} up</small></div>
                                        <div class="sfp-bank">
                                            @foreach($ports as $port)
                                                <div class="sfp {{ $port['state'] }}" tabindex="0"
                                                     data-slot="{{ $slotScan['slot'] }}" data-port="{{ $port['port'] }}" data-state="{{ $port['state'] }}"
                                                     data-ont="{{ $port['totalOnt'] ?? 0 }}" data-online="{{ $port['online'] }}" data-offline="{{ $port['offline'] }}" data-los="{{ $port['los'] }}">
                                                    <i class="sfp-led"></i>
                                                    <div class="sfp-cage"></div>
                                                    <span class="sfp-num">{{ $port['port'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @if($emptySlots->isNotEmpty())
                    <p class="empty-note">Slot {{ $emptySlots->implode(', ') }} tidak terpasang kartu (tanpa port).</p>
                @endif

                <div class="port-detail" id="portDetail" aria-live="polite">Arahkan kursor atau fokus ke sebuah port untuk melihat detailnya.</div>

                <div class="table-scroll">
                    <table class="slot-table">
                        <thead><tr><th>Slot</th><th>Port up</th><th>Total ONT</th><th>Online</th><th>Offline</th><th>LOS</th></tr></thead>
                        <tbody>
                        @foreach($slots as $slotScan)
                            @php $ps = collect($slotScan['ports']); $ss = $slotScan['summary'] ?? []; @endphp
                            <tr>
                                <td>Slot {{ $slotScan['slot'] }}</td>
                                <td>{{ $ps->where('state', 'online')->count() }} / {{ $ps->count() }}</td>
                                <td>{{ $ss['totalOnt'] ?? 0 }}</td>
                                <td class="c-online">{{ $ss['online'] ?? 0 }}</td>
                                <td class="c-offline">{{ $ss['offline'] ?? 0 }}</td>
                                <td class="c-los">{{ $ss['los'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td>Total</td>
                            <td>{{ $slots->sum(fn ($sl) => collect($sl['ports'])->where('state', 'online')->count()) }} / {{ $totalPorts }}</td>
                            <td>{{ $summary['totalOnt'] ?? 0 }}</td>
                            <td class="c-online">{{ $summary['online'] ?? 0 }}</td>
                            <td class="c-offline">{{ $summary['offline'] ?? 0 }}</td>
                            <td class="c-los">{{ $summary['los'] ?? 0 }}</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="port-legend"><span><i class="port-dot online"></i>Up (LED hijau menyala)</span><span><i class="port-dot offline"></i>Down (LED mati)</span><span><i class="port-dot los"></i>LOS / gangguan sinyal</span></div>
            </section>
        @endif
    </div>

    <script>
        (function () {
            var box = document.getElementById('portDetail');
            if (!box) return;
            var idle = box.textContent, current = null;
            var label = {online: 'UP', offline: 'DOWN', los: 'LOS'};
            function show(el) {
                if (current) current.classList.remove('is-active');
                current = el; el.classList.add('is-active');
                var d = el.dataset, st = d.state;
                box.textContent = '';
                function add(tag, text, cls) { var n = document.createElement(tag); n.textContent = text; if (cls) n.className = cls; box.appendChild(n); return n; }
                var t = add('span', 'Slot ' + d.slot + ' · Port ' + d.port); t.style.fontWeight = '650'; t.style.color = '#0f172a';
                add('span', label[st] || st.toUpperCase(), 'pd-state ' + st);
                add('span', d.ont + ' ONT');
                add('span', 'Online ' + d.online);
                add('span', 'Offline ' + d.offline);
                add('span', 'LOS ' + d.los);
            }
            document.querySelectorAll('.sfp').forEach(function (el) {
                el.addEventListener('mouseenter', function () { show(el); });
                el.addEventListener('focus', function () { show(el); });
            });
        })();
    </script>
</x-layouts.dashboard>
