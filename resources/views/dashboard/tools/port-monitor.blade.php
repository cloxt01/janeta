<x-layouts.dashboard title="Port Monitor">

    <style>
        /* ---------- Chassis (ilustrasi perangkat, versi gelap) ---------- */
        .rack-scroll{overflow-x:auto;padding:2px 2px 14px}
        .chassis{margin:0 auto}
        .chassis-top{height:14px;margin:0 1.2%;background:linear-gradient(180deg,#4a4d54,#3a3d43);clip-path:polygon(2.5% 0,97.5% 0,100% 100%,0 100%);position:relative}
        .chassis-top::after{content:"";position:absolute;inset:0;background:repeating-linear-gradient(90deg,rgb(255 255 255 / 5%) 0 1px,transparent 1px 3px)}
        .chassis-face{position:relative;display:flex;align-items:stretch;gap:20px;padding:15px 30px 13px;border-radius:0 0 6px 6px;background:linear-gradient(180deg,#3b3e44 0%,#2f3237 55%,#292b30 100%);box-shadow:inset 0 1px 0 rgb(255 255 255 / 10%),inset 0 -1px 0 rgb(0 0 0 / 40%),0 14px 18px -10px rgb(0 0 0 / 60%)}
        .chassis-face::before,.chassis-face::after{content:"";position:absolute;top:50%;width:7px;height:7px;margin-top:-3.5px;border-radius:50%;background:radial-gradient(circle at 35% 30%,#7a7d84,#35373c);box-shadow:inset 0 0 0 1px rgb(0 0 0 / 45%)}
        .chassis-face::before{left:11px}.chassis-face::after{right:11px}

        .chassis-left{flex:0 0 auto;display:flex;align-items:center;gap:18px}
        .chassis-vent{width:110px;height:62px;border-radius:3px;background-color:#16181b;background-image:radial-gradient(circle,#5d6068 0 .9px,transparent 1.1px);background-size:4px 4px;box-shadow:inset 0 1px 3px rgb(0 0 0 / 70%),0 1px 0 rgb(255 255 255 / 8%)}
        .chassis-console{display:flex;flex-direction:column;align-items:center;gap:5px}
        .db9{width:34px;height:15px;clip-path:polygon(7% 0,93% 0,100% 100%,0 100%);background:linear-gradient(#6a6e76,#3a3d43);display:grid;place-items:center}
        .db9::after{content:"";width:70%;height:55%;background:#0a0b0d;clip-path:polygon(6% 0,94% 0,100% 100%,0 100%)}
        .chassis-label{color:#8a8d94;font-size:8.5px;font-weight:600;letter-spacing:.06em}

        .chassis-slots{flex:1;display:flex;gap:10px}
        .slot-group{display:flex;flex-direction:column;gap:5px;min-width:0;flex-shrink:0}
        .slot-group-head{display:flex;align-items:baseline;justify-content:space-between;gap:8px;padding:0 3px;color:#b5b8be;font-size:10px;font-weight:650;white-space:nowrap;overflow:hidden}
        .slot-group-head small{color:#80838a;font-size:9px;font-weight:500}
        .sfp-bank{flex:1;display:grid;grid-auto-flow:column;grid-template-rows:repeat(2,auto);grid-auto-columns:minmax(0,1fr);gap:7px 8px;padding:9px 10px;border-radius:4px;background:#1b1d21;box-shadow:inset 0 1px 3px rgb(0 0 0 / 60%),0 1px 0 rgb(255 255 255 / 7%)}
        .sfp{display:flex;flex-direction:column;align-items:center;gap:3px;cursor:default;outline:0}
        .sfp-led{width:6px;height:6px;border-radius:50%;background:#44474d;box-shadow:inset 0 1px 1px rgb(0 0 0 / 60%)}
        .sfp-cage{position:relative;width:100%;height:21px;border:1px solid #50545c;border-radius:2px;background:linear-gradient(180deg,#3a3e46,#14171b);box-shadow:inset 0 1px 0 rgb(255 255 255 / 10%)}
        .sfp-cage::after{content:"";position:absolute;inset:5px 4px 3px;border-radius:1px;background:#050607;box-shadow:inset 0 1px 2px rgb(0 0 0 / 90%)}
        .sfp-num{color:#80838a;font-size:8.5px;font-weight:600;line-height:1}
        .sfp:hover .sfp-cage,.sfp:focus .sfp-cage,.sfp.is-active .sfp-cage{border-color:#d0d0d0;box-shadow:0 0 0 2px rgb(255 255 255 / 12%)}
        .sfp.online .sfp-led{background:#22c55e;box-shadow:0 0 0 1px rgb(21 128 61 / 55%),0 0 7px 2px rgb(34 197 94 / 60%)}
        .sfp.online .sfp-cage{border-color:#2f7a52;box-shadow:inset 0 1px 0 rgb(255 255 255 / 10%),inset 0 0 8px rgb(34 197 94 / 30%)}
        .sfp.los .sfp-led{background:#ef4444;box-shadow:0 0 6px 1px rgb(239 68 68 / 45%)}

        .pd-state{display:inline-flex;align-items:center;font-weight:650;color:#b5b8be}
        .pd-state.online{color:#4cd286}
        .pd-state.los{color:#f08a8a}

        .port-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#44474d}
        .port-dot.online{background:#22c55e;box-shadow:0 0 5px 1px rgb(34 197 94 / 50%)}
        .port-dot.los{background:#ef4444}
    </style>

    @php
        $selectedOlt = $olts->firstWhere('name', $oltName);
        $summary = $scan['summary'] ?? [];
    @endphp

    {{-- PAGE HEADER --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
                Tools · Port monitor
            </div>
            <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
                Port <span class="text-[#8a8a8a]">monitor.</span>
            </h1>
            <p class="mt-2.5 text-xs text-[#a0a0a0]">
                Pantau kesehatan port GPON dan distribusi ONT dari satu tampilan
            </p>
        </div>

        {{-- TOOLBAR --}}
        <form method="get" action="{{ route('tools.port-monitor') }}" class="flex flex-wrap items-center gap-2">
            <select name="olt" onchange="this.form.submit()"
                    class="h-9 max-w-[280px] rounded-full border border-[#3a3a3a] bg-[#1c1c1c] px-4 text-xs text-[#d0d0d0] outline-none focus:border-[#5a5a5a]">
                @foreach($olts as $olt)
                    <option value="{{ $olt->name }}" @selected($oltName === $olt->name)>{{ $olt->name }} · {{ $olt->host }}</option>
                @endforeach
            </select>

            <label class="flex h-9 items-center gap-2 rounded-full border border-[#3a3a3a] bg-[#1c1c1c] pl-4 pr-1 text-xs text-[#8a8a8a] focus-within:border-[#5a5a5a]">
                Frame
                <input name="frame" value="{{ $frame }}" inputmode="numeric"
                       class="h-7 w-12 rounded-full bg-transparent px-2 text-center text-xs text-[#e5e5e5] outline-none">
            </label>

            <button type="submit"
                    class="h-9 rounded-full bg-[#0b8a3e] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#0da048]">
                Refresh data
            </button>
        </form>
    </div>

    @if($error)
        <div class="mb-5 rounded-2xl border border-[#5e2b2b] bg-[#2a1717] px-5 py-3.5 text-sm text-[#f08a8a]">
            {{ $error }}
        </div>
    @endif

    @if($scan)

        {{-- STAT STRIP --}}
        <div class="mb-5 grid grid-cols-2 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] md:grid-cols-4">
            <div class="border-b border-r border-[#2e2e2e] px-5 py-4 md:border-b-0">
                <div class="text-[11px] text-[#8a8a8a]">Total ONT</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $summary['totalOnt'] ?? 0 }}</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">terdeteksi</div>
            </div>
            <div class="border-b border-[#2e2e2e] px-5 py-4 md:border-b-0 md:border-r">
                <div class="text-[11px] text-[#8a8a8a]">Online</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#4cd286]">{{ $summary['online'] ?? 0 }}</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">koneksi aktif</div>
            </div>
            <div class="border-r border-[#2e2e2e] px-5 py-4">
                <div class="text-[11px] text-[#8a8a8a]">Offline</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#e0b155]">{{ $summary['offline'] ?? 0 }}</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">perlu diperiksa</div>
            </div>
            <div class="px-5 py-4">
                <div class="text-[11px] text-[#8a8a8a]">LOS</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f08a8a]">{{ $summary['los'] ?? 0 }}</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">gangguan sinyal</div>
            </div>
        </div>

        {{-- DEVICE CARD --}}
        <section class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2e2e2e] px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-[#2c2c2c] text-[#bbb]">
                        <i data-lucide="server" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-[#f2f2f2]">
                            {{ $selectedOlt?->name ?? $scan['olt'] }}
                            <span class="rounded-full border border-[#3a3a3a] bg-[#242424] px-2 py-0.5 text-[10px] font-medium text-[#bbb]">
                                Frame {{ $scan['frame'] }}
                            </span>
                        </h2>
                        <p class="mt-0.5 text-[11px] text-[#8a8a8a]">
                            {{ $selectedOlt?->host ?? 'OLT device' }} · {{ strtoupper($selectedOlt?->connection_type ?? 'connection') }}
                        </p>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-[11px] text-[#8a8a8a]">Terakhir diperbarui</div>
                    <div class="mt-0.5 text-xs font-semibold text-[#e5e5e5]">{{ $scan['scanned_at']->format('d M Y, H:i:s') }}</div>
                </div>
            </div>

            @php
                $slots = collect($scan['slots']);
                $activeSlots = $slots->filter(fn ($sl) => count($sl['ports']) > 0);
                $emptySlots = $slots->filter(fn ($sl) => count($sl['ports']) === 0)->pluck('slot');
                $totalPorts = $activeSlots->sum(fn ($sl) => count($sl['ports']));
                $chassisMin = 300 + ($totalPorts / 2) * 36 + $activeSlots->count() * 34;
            @endphp

            <div class="px-5 pb-2 pt-5">

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
                                        <div class="slot-group-head">
                                            <span>Slot {{ $slotScan['slot'] }}</span>
                                            <small>{{ $ports->where('state', 'online')->count() }}/{{ $ports->count() }} up</small>
                                        </div>
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
                    <p class="mt-1 text-center text-[11px] text-[#8a8a8a]">
                        Slot {{ $emptySlots->implode(', ') }} tidak terpasang kartu (tanpa port).
                    </p>
                @endif

                <div id="portDetail" aria-live="polite"
                     class="mt-3 flex min-h-[44px] flex-wrap items-center gap-x-5 gap-y-1.5 rounded-xl border border-[#2e2e2e] bg-[#202020] px-4 py-2.5 text-xs text-[#8a8a8a]">
                    Arahkan kursor atau fokus ke sebuah port untuk melihat detailnya.
                </div>
            </div>

            {{-- SLOT TABLE --}}
            <div class="mt-4 overflow-x-auto border-t border-[#2e2e2e]">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-[#2e2e2e] text-[10px] font-semibold uppercase tracking-[0.1em] text-[#8a8a8a]">
                    <tr>
                        <th class="px-5 py-3.5">Slot</th>
                        <th class="px-5 py-3.5 text-right">Port up</th>
                        <th class="px-5 py-3.5 text-right">Total ONT</th>
                        <th class="px-5 py-3.5 text-right">Online</th>
                        <th class="px-5 py-3.5 text-right">Offline</th>
                        <th class="px-5 py-3.5 text-right">LOS</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-[#272727] tabular-nums">
                    @foreach($slots as $slotScan)
                        @php $ps = collect($slotScan['ports']); $ss = $slotScan['summary'] ?? []; @endphp
                        <tr class="transition-colors hover:bg-[#232323]">
                            <td class="px-5 py-3.5 text-[13px] font-semibold text-[#f2f2f2]">Slot {{ $slotScan['slot'] }}</td>
                            <td class="px-5 py-3.5 text-right text-xs text-[#c4c4c4]">{{ $ps->where('state', 'online')->count() }} / {{ $ps->count() }}</td>
                            <td class="px-5 py-3.5 text-right text-xs text-[#c4c4c4]">{{ $ss['totalOnt'] ?? 0 }}</td>
                            <td class="px-5 py-3.5 text-right text-xs text-[#4cd286]">{{ $ss['online'] ?? 0 }}</td>
                            <td class="px-5 py-3.5 text-right text-xs text-[#e0b155]">{{ $ss['offline'] ?? 0 }}</td>
                            <td class="px-5 py-3.5 text-right text-xs text-[#f08a8a]">{{ $ss['los'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                    </tbody>

                    <tfoot class="border-t border-[#363636] tabular-nums">
                    <tr class="bg-[#202020]">
                        <td class="px-5 py-3.5 text-[13px] font-semibold text-[#f2f2f2]">Total</td>
                        <td class="px-5 py-3.5 text-right text-xs font-semibold text-[#f2f2f2]">
                            {{ $slots->sum(fn ($sl) => collect($sl['ports'])->where('state', 'online')->count()) }} / {{ $totalPorts }}
                        </td>
                        <td class="px-5 py-3.5 text-right text-xs font-semibold text-[#f2f2f2]">{{ $summary['totalOnt'] ?? 0 }}</td>
                        <td class="px-5 py-3.5 text-right text-xs font-semibold text-[#4cd286]">{{ $summary['online'] ?? 0 }}</td>
                        <td class="px-5 py-3.5 text-right text-xs font-semibold text-[#e0b155]">{{ $summary['offline'] ?? 0 }}</td>
                        <td class="px-5 py-3.5 text-right text-xs font-semibold text-[#f08a8a]">{{ $summary['los'] ?? 0 }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            {{-- LEGEND --}}
            <div class="flex flex-wrap gap-x-5 gap-y-2 border-t border-[#2e2e2e] px-5 py-4 text-[11px] text-[#8a8a8a]">
                <span class="inline-flex items-center gap-1.5"><i class="port-dot online"></i>Up (LED hijau menyala)</span>
                <span class="inline-flex items-center gap-1.5"><i class="port-dot"></i>Down (LED mati)</span>
                <span class="inline-flex items-center gap-1.5"><i class="port-dot los"></i>LOS / gangguan sinyal</span>
            </div>

        </section>
    @endif

    <script>
        (function () {
            var box = document.getElementById('portDetail');
            if (!box) return;

            var current = null;
            var label = {online: 'UP', offline: 'DOWN', los: 'LOS'};

            function show(el) {
                if (current) current.classList.remove('is-active');
                current = el;
                el.classList.add('is-active');

                var d = el.dataset, st = d.state;
                box.textContent = '';

                function add(text, cls, color) {
                    var n = document.createElement('span');
                    n.textContent = text;
                    if (cls) n.className = cls;
                    if (color) n.style.color = color;
                    box.appendChild(n);
                    return n;
                }

                var title = add('Slot ' + d.slot + ' · Port ' + d.port, '', '#f2f2f2');
                title.style.fontWeight = '650';

                add(label[st] || st.toUpperCase(), 'pd-state ' + st);
                add(d.ont + ' ONT', '', '#c4c4c4');
                add('Online ' + d.online, '', '#4cd286');
                add('Offline ' + d.offline, '', '#e0b155');
                add('LOS ' + d.los, '', '#f08a8a');
            }

            document.querySelectorAll('.sfp').forEach(function (el) {
                el.addEventListener('mouseenter', function () { show(el); });
                el.addEventListener('focus', function () { show(el); });
            });
        })();
    </script>

</x-layouts.dashboard>
