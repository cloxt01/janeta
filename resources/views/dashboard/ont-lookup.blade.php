<x-layouts.dashboard title="Cari ONT">
    <!-- Header Page -->
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <span class="inline-block rounded-md bg-cyan-50 px-2.5 py-1 text-xs font-bold uppercase tracking-widest text-cyan-600 mb-2">Operasional Jaringan</span>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pencarian & Detail ONT</h1>
            <p class="mt-1 text-sm text-slate-500">Tarik status real-time, daya optik, WAN, dan informasi perangkat dari OLT.</p>
        </div>
    </div>

    <!-- Search Form -->
    <form method="get" action="{{ route('ont.lookup') }}" class="mb-8 flex flex-col sm:flex-row gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input name="id" value="{{ $id }}" placeholder="Masukkan ID Customer (contoh: 1JN2024...)" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 pl-11 pr-4 text-sm font-medium text-slate-800 outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20">
        </div>
        <div class="flex items-center gap-2">
            <button class="jn-button px-6 py-3 shadow-sm">Cari Perangkat</button>
            @if($result)
                <button name="wifi" value="1" class="jn-button jn-button-secondary py-3">Cek Wi‑Fi</button>
            @endif
        </div>
    </form>

    <!-- Error Alert -->
    @if($error)
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800 shadow-sm">
            <svg class="h-5 w-5 flex-shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ $error }}</span>
        </div>
    @endif

    <!-- Warning Version Alert -->
    @if($result && ($result['versionAvailable'] ?? true) === false)
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-800 shadow-sm">
            <svg class="h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><b>Data Spesifikasi Terbatas:</b> {{ $result['versionReason'] }}</span>
        </div>
    @endif

    @if($result)
        @php($isOnline = strtolower($result['runState'] ?? '') === 'online')

        <!-- Metrics Grid -->
        <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Status Card -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Status ONT</p>
                <div class="mt-3 flex items-center gap-2.5">
                    <span class="relative flex h-3 w-3">
                      @if($isOnline)
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                      @else
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-rose-500"></span>
                      @endif
                    </span>
                    <span class="text-xl font-bold capitalize {{ $isOnline ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $result['runState'] ?? '-' }}
                    </span>
                </div>
            </div>

            <!-- Optical RX Power -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">RX Power (ONT)</p>
                <p class="mt-3 text-xl font-bold text-slate-800">
                    {{ $result['rxPower'] ?? '-' }} <span class="text-xs font-normal text-slate-400">{{ ($result['rxPower'] ?? '-') !== '-' ? 'dBm' : '' }}</span>
                </p>
            </div>

            <!-- Optical TX Power -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">TX Power (ONT)</p>
                <p class="mt-3 text-xl font-bold text-slate-800">
                    {{ $result['txPower'] ?? '-' }} <span class="text-xs font-normal text-slate-400">{{ ($result['txPower'] ?? '-') !== '-' ? 'dBm' : '' }}</span>
                </p>
            </div>

            <!-- OLT RX Power -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">OLT RX Power</p>
                <p class="mt-3 text-xl font-bold text-slate-800">
                    {{ $result['oltRxPower'] ?? '-' }} <span class="text-xs font-normal text-slate-400">{{ ($result['oltRxPower'] ?? '-') !== '-' ? 'dBm' : '' }}</span>
                </p>
            </div>

            <!-- Suhu -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Suhu Perangkat</p>
                <p class="mt-3 text-xl font-bold text-slate-800">{{ $result['temp'] ?? '-' }}</p>
            </div>

            <!-- Voltage -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Voltase</p>
                <p class="mt-3 text-xl font-bold text-slate-800">{{ $result['voltage'] ?? '-' }}</p>
            </div>

            <!-- WAN IP -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">WAN IP</p>
                <p class="mt-3 text-base font-bold text-slate-800 truncate" title="{{ $result['wanIp'] ?? '-' }}">{{ $result['wanIp'] ?? '-' }}</p>
            </div>

            <!-- WAN Status / Uptime / Down Cause -->
            @if($isOnline)
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Last Up Time</p>
                    <p class="mt-3 text-sm font-bold text-slate-800 truncate" title="{{ $result['lastUpTime'] ?? '-' }}">{{ $result['lastUpTime'] ?? '-' }}</p>
                </div>
            @else
                <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-5 shadow-sm transition hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-500">Last Down Cause</p>
                    <p class="mt-3 text-sm font-bold text-rose-700 capitalize">{{ $result['lastDownCause'] ?? '-' }}</p>
                </div>
            @endif
        </div>

        <!-- Detail Sections: Lokasi & Spesifikasi -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Lokasi ONT -->
            <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-bold text-slate-800">Lokasi & Topologi</h2>
                    <span class="text-xs font-medium text-slate-400">OLT: <b class="text-slate-700">{{ $result['olt'] ?? '-' }}</b></span>
                </div>
                <dl class="space-y-3 text-sm">
                    @foreach(['idcust' => 'ID Customer', 'frame' => 'Frame', 'slot' => 'Slot', 'port' => 'Port', 'ontId' => 'ONT ID'] as $key => $label)
                        <div class="flex justify-between items-center py-1">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="font-semibold text-slate-800">{{ $result[$key] ?? '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <!-- Spesifikasi Perangkat -->
            <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-bold text-slate-800">Spesifikasi Perangkat</h2>
                    <span class="text-xs font-medium text-slate-400">Hardware Info</span>
                </div>
                <dl class="space-y-3 text-sm">
                    @foreach(['sn' => 'Serial Number', 'brand' => 'Merek', 'type' => 'Tipe', 'wlanType' => 'WLAN Band'] as $key => $label)
                        <div class="flex justify-between items-center py-1 gap-4">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="font-semibold text-slate-800 text-right truncate max-w-[240px]" title="{{ $result[$key] ?? '-' }}">{{ $result[$key] ?? '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>
    @endif

    <!-- Wi-Fi Section -->
    @if($result && request()->boolean('wifi'))
        <section class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Wi‑Fi / WLAN ONT</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Data SSID ditarik langsung dari OLT via SSH.</p>
                </div>
                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600 border border-indigo-100">
                    {{ count($result['wlan']['ssids'] ?? []) }} SSID Aktif
                </span>
            </div>

            @if(empty($result['wlan']['ssids']))
                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 text-sm text-amber-800">
                    Tidak ada SSID yang terbaca. Pastikan format output command Huawei terbaca dengan benar di log aplikasi.
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($result['wlan']['ssids'] as $ssid)
                        <div class="rounded-xl border border-slate-200/70 bg-slate-50/50 p-4 transition hover:bg-slate-50">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">SSID {{ $ssid['index'] }}</span>
                                <span class="rounded-full bg-emerald-100/60 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 uppercase">{{ $ssid['opState'] }}</span>
                            </div>
                            <p class="mt-2 text-base font-bold text-slate-800">{{ $ssid['name'] }}</p>
                            <div class="mt-3 flex items-center justify-between text-xs text-slate-500 border-t border-slate-200/60 pt-3">
                                <span>Admin State: <strong class="text-slate-700 capitalize">{{ $ssid['adminState'] }}</strong></span>
                                <span>Koneksi Perangkat: <strong class="text-slate-700">{{ $ssid['deviceCount'] }} / {{ $ssid['deviceMax'] }}</strong></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</x-layouts.dashboard>