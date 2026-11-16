
<x-layouts.dashboard title="Hasil Request">

    @php
        $resultData = $request->result ?? [];
        $displayData = $resultData['data'] ?? $resultData;
        $isCompleted = $request->status === 'completed';

        // Data ONT
        $ont = is_array($displayData) ? $displayData : [];

        $sn = $ont['sn'] ?? '-';
        $olt = $ont['olt'] ?? '-';
        $type = $ont['type'] ?? null;
        $brand = $ont['brand'] ?? 'Unknown';

        $frame = $ont['frame'] ?? '-';
        $slot = $ont['slot'] ?? '-';
        $port = $ont['port'] ?? '-';
        $ontId = $ont['ontId'] ?? '-';

        $state = strtolower($ont['state'] ?? $ont['runState'] ?? 'unknown');
        $isOnline = $state === 'online';

        // ONT Control Flag
        $controlFlag = strtolower(trim((string) ($ont['controlFlag'] ?? '')));

        $customerId = $ont['customerId'] ?? $ont['idcust'] ?? '-';
        $customerName = $ont['customerName'] ?? '-';

        // Image modem
        $modemImagePath = $type
            ? public_path('images/modem/' . basename($type) . '.png')
            : null;

        $modemImage = $modemImagePath && file_exists($modemImagePath)
            ? asset('images/modem/' . basename($type) . '.png')
            : null;

        // WLAN
        $wlan = $ont['wlan'] ?? [];
        $ssids = $wlan['ssids'] ?? [];

        // JSON
        $jsonOutput = json_encode(
            $displayData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $opticalItems = [
            ['label' => 'ONT RX Power', 'value' => $ont['rxPower'] ?? null, 'unit' => 'dBm'],
            ['label' => 'OLT RX Power', 'value' => $ont['oltRxPower'] ?? null, 'unit' => 'dBm'],
            ['label' => 'ONT TX Power', 'value' => $ont['txPower'] ?? null, 'unit' => 'dBm'],
            ['label' => 'Temperature', 'value' => $ont['temp'] ?? null, 'unit' => '°C'],
            ['label' => 'Voltage', 'value' => $ont['voltage'] ?? null, 'unit' => 'V'],
        ];

        $wanItems = [
            ['label' => 'WAN IP Address', 'value' => $ont['wanIp'] ?? '-'],
            ['label' => 'Gateway', 'value' => $ont['wanGateway'] ?? '-'],
            ['label' => 'Subnet Mask', 'value' => $ont['wanSubnet'] ?? '-'],
            ['label' => 'DNS Primary', 'value' => $ont['wanDns1'] ?? '-'],
            ['label' => 'DNS Secondary', 'value' => $ont['wanDns2'] ?? '-'],
            ['label' => 'Connection Type', 'value' => $ont['wanConnType'] ?? '-'],
            ['label' => 'Access Type', 'value' => $ont['wanAccessType'] ?? '-'],
            ['label' => 'WAN Status', 'value' => $ont['wanStatus'] ?? '-'],
        ];

        $deviceItems = [
            ['label' => 'Serial Number', 'value' => $sn],
            ['label' => 'OLT', 'value' => $olt],
            ['label' => 'Brand', 'value' => $brand],
            ['label' => 'Model', 'value' => $type ?? '-'],
            ['label' => 'Customer ID', 'value' => $customerId],
            ['label' => 'Customer Name', 'value' => $customerName],
            ['label' => 'Frame', 'value' => $frame],
            ['label' => 'Slot', 'value' => $slot],
            ['label' => 'Port', 'value' => $port],
            ['label' => 'ONT ID', 'value' => $ontId],
            ['label' => 'Last Up Time', 'value' => $ont['lastUpTime'] ?? '-'],
            ['label' => 'Last Down Cause', 'value' => $ont['lastDownCause'] ?? '-'],
        ];
    @endphp

    {{-- HEADER --}}
    <div class="mb-8">
        <span class="mb-2 inline-block rounded-md bg-cyan-50 px-2.5 py-1 text-xs font-bold uppercase tracking-widest text-cyan-600">
            Operasional Jaringan
        </span>

        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Hasil Operasi JANETA
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Detail hasil eksekusi request perangkat OLT.
        </p>
    </div>

    {{-- REQUEST STATUS --}}
    <div class="mb-6 flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">

        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
            {{ $isCompleted ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">

            @if($isCompleted)
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            @else
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            @endif

        </div>

        <div class="min-w-0">
            <div class="text-sm font-bold {{ $isCompleted ? 'text-emerald-700' : 'text-rose-700' }}">
                {{ $isCompleted ? 'Request berhasil diselesaikan' : 'Request tidak berhasil diselesaikan' }}
            </div>

            <p class="mt-0.5 text-xs text-slate-500">
                {{ strtoupper($request->status) }} · JANETA OLT Management
            </p>
        </div>
    </div>

    @if($isCompleted && !empty($ont))

        {{-- ONT OVERVIEW --}}
        <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">

            <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:p-6">

                {{-- MODEM IMAGE --}}
                <div class="flex h-36 w-full shrink-0 items-center justify-center rounded-xl bg-slate-50 sm:w-40">
                    @if($modemImage)
                        <img
                            src="{{ $modemImage }}"
                            alt="{{ $type }}"
                            class="h-32 w-32 object-contain"
                            onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden')"
                        >
                        <div class="hidden text-center text-xs text-slate-400">
                            Gambar tidak tersedia
                        </div>
                    @else
                        <div class="text-center">
                            <svg class="mx-auto mb-2 h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 10h16M6 10V6h12v4M5 10v9h14v-9M9 14h.01M12 14h.01M15 14h.01"/>
                            </svg>
                            <p class="text-xs text-slate-400">Modem image unavailable</p>
                        </div>
                    @endif
                </div>

                {{-- MODEM INFO --}}
                <div class="min-w-0 flex-1">

                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-cyan-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-cyan-700">
                            {{ $brand }}
                        </span>

                        <span class="rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider
                            {{ $isOnline ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            {{ strtoupper($state) }}
                        </span>
                    </div>

                    <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                        {{ $type ?? 'Unknown Device' }}
                    </h2>

                    <p class="mt-1 break-all font-mono text-xs text-slate-500">
                        {{ $sn }}
                    </p>

                    <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                        <span>OLT <strong class="text-slate-800">{{ $olt }}</strong></span>
                        <span>F/S/P <strong class="text-slate-800">{{ $frame }}/{{ $slot }}/{{ $port }}</strong></span>
                        <span>ONT ID <strong class="text-slate-800">{{ $ontId }}</strong></span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            data-copy="{{ $sn }}"
                            class="copy-value rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                            <span class="copy-label">Salin SN</span>
                        </button>

                        <button
                            type="button"
                            data-copy="{{ $ont['wanIp'] ?? '' }}"
                            class="copy-value rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                            <span class="copy-label">Salin IP</span>
                        </button>
                    </div>

                </div>
            </div>

            <div class="grid grid-cols-2 border-t border-slate-100 sm:grid-cols-4">

                <div class="border-r border-slate-100 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Customer ID</p>
                    <p class="mt-1 truncate text-sm font-bold text-slate-800">{{ $customerId }}</p>
                </div>

                <div class="border-r border-slate-100 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">WAN Status</p>
                    <p class="mt-1 text-sm font-bold text-emerald-600">{{ $ont['wanStatus'] ?? '-' }}</p>
                </div>

                <div class="border-r border-slate-100 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Last Up Time</p>
                    <p class="mt-1 text-sm font-bold text-slate-800">{{ $ont['lastUpTime'] ?? '-' }}</p>
                </div>

                <div class="p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Temperature</p>
                    <p class="mt-1 text-sm font-bold text-slate-800">{{ $ont['temp'] ?? '-' }}{{ isset($ont['temp']) ? ' °C' : '' }}</p>
                </div>

            </div>
        </section>

        {{-- ONT CONTROL --}}
        <section
            id="ontControl"
            class="mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm"
            data-url="{{ route('api.ont.control') }}"
            data-csrf="{{ csrf_token() }}"
            data-olt="{{ $olt }}"
            data-frame="{{ $frame }}"
            data-slot="{{ $slot }}"
            data-port="{{ $port }}"
            data-ont-id="{{ $ontId }}"
            data-control-flag="{{ $controlFlag }}"
        >

            {{-- CONTROL HEADER --}}
            <div class="border-b border-slate-100 p-5 sm:p-6">

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-slate-800">ONT Control</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Kelola status dan operasional perangkat ONT.
                        </p>
                    </div>

                    <span class="flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Remote Management
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                    <span>OLT <strong class="text-slate-800">{{ $olt }}</strong></span>
                    <span>F/S/P <strong class="text-slate-800">{{ $frame }}/{{ $slot }}/{{ $port }}</strong></span>
                    <span>ONT ID <strong class="text-slate-800">{{ $ontId }}</strong></span>
                </div>

            </div>

            {{-- CONTROL BUTTONS --}}
            <div class="grid gap-3 p-5 sm:grid-cols-3 sm:p-6">

                {{-- RESTART: ALWAYS AVAILABLE --}}
                <button
                    type="button"
                    data-ont-action="restart"
                    class="ont-control-button group flex items-center gap-3 rounded-xl border border-amber-200/80 bg-amber-50/40 p-4 text-left transition-all duration-200 hover:border-amber-400 hover:bg-amber-50 active:scale-[0.98] active:border-amber-500 active:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
                >

                    <span class="ont-button-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 transition-all duration-200 group-hover:bg-amber-200 group-hover:text-amber-700 group-active:bg-amber-300 group-active:text-amber-800">

                        {{-- REFRESH CW --}}
                        <svg class="h-5 w-5 transition-transform duration-300 group-hover:rotate-90"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M20 7v5h-5M4 17v-5h5"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M5.64 9a7 7 0 0 1 11.67-2L20 12M4 12l2.69 5a7 7 0 0 0 11.67-2"/>
                        </svg>

                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="ont-button-label block text-sm font-bold text-amber-800 transition-colors duration-200">
                            Restart ONT
                        </span>

                        <span class="block text-[11px] text-amber-700/70 transition-colors duration-200">
                            Reboot perangkat
                        </span>
                    </span>

                    <svg class="h-4 w-4 shrink-0 text-amber-400 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-amber-600"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="m9 18 6-6-6-6"/>
                    </svg>

                </button>

                {{-- ACTIVATE: ONLY WHEN DEACTIVE --}}
                @if($controlFlag === 'deactivated')
                    <button
                        type="button"
                        data-ont-action="activate"
                        class="ont-control-button group flex items-center gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-4 text-left transition-all duration-200 hover:border-emerald-400 hover:bg-emerald-50 active:scale-[0.98] active:border-emerald-500 active:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
                    >

                        <span class="ont-button-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 transition-all duration-200 group-hover:bg-emerald-200 group-hover:text-emerald-700 group-active:bg-emerald-300 group-active:text-emerald-800">

                            {{-- POWER --}}
                            <svg class="h-5 w-5 transition-transform duration-200 group-hover:scale-110"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 2v10"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6.2 5.8a8 8 0 1 0 11.6 0"/>
                            </svg>

                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="ont-button-label block text-sm font-bold text-emerald-800 transition-colors duration-200">
                                Aktifkan ONT
                            </span>

                            <span class="block text-[11px] text-emerald-700/70 transition-colors duration-200">
                                Enable perangkat
                            </span>
                        </span>

                        <svg class="h-4 w-4 shrink-0 text-emerald-400 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-emerald-600"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="m9 18 6-6-6-6"/>
                        </svg>

                    </button>
                @endif

                @if($controlFlag === 'active')
                    <button
                        type="button"
                        data-ont-action="deactivate"
                        class="ont-control-button group flex items-center gap-3 rounded-xl border border-rose-200/80 bg-rose-50/40 p-4 text-left transition-all duration-200 hover:border-rose-400 hover:bg-rose-50 active:scale-[0.98] active:border-rose-500 active:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50"
                    >

                        <span class="ont-button-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 transition-all duration-200 group-hover:bg-rose-200 group-hover:text-rose-700 group-active:bg-rose-300 group-active:text-rose-800">

                            {{-- POWER OFF --}}
                            <svg class="h-5 w-5 transition-transform duration-200 group-hover:scale-110"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 2v8"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6.2 5.8a8 8 0 1 0 11.6 0"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m4 4 16 16"/>
                            </svg>

                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="ont-button-label block text-sm font-bold text-rose-800 transition-colors duration-200">
                                Nonaktifkan ONT
                            </span>

                            <span class="block text-[11px] text-rose-700/70 transition-colors duration-200">
                                Disable perangkat
                            </span>
                        </span>

                        <svg class="h-4 w-4 shrink-0 text-rose-400 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-rose-600"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="m9 18 6-6-6-6"/>
                        </svg>

                    </button>
                @endif

            </div>

            {{-- RESPONSE MESSAGE --}}
            <div id="ontControlMessage" class="hidden border-t border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-start gap-2">
                    <span id="ontControlIcon" class="mt-0.5"></span>

                    <div class="min-w-0">
                        <p id="ontControlTitle" class="text-sm font-bold"></p>
                        <p id="ontControlText" class="mt-1 break-words text-xs"></p>
                    </div>
                </div>
            </div>

        </section>

        {{-- OPTICAL POWER --}}
        <section class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">

            <div class="mb-5">
                <h2 class="font-bold text-slate-800">Optical Power</h2>
                <p class="mt-1 text-xs text-slate-500">Informasi daya optik dan kondisi perangkat.</p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">

                @foreach($opticalItems as $item)
                    @if($item['value'] !== null)
                        @php
                            $numericValue = is_numeric($item['value']) ? (float) $item['value'] : null;
                            $isRx = str_contains($item['label'], 'RX');
                            $powerWarning = $isRx && $numericValue !== null && $numericValue <= -27;
                        @endphp

                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                            <p class="text-[11px] font-medium text-slate-500">{{ $item['label'] }}</p>

                            <div class="mt-2 flex items-baseline gap-1">
                                <span class="text-xl font-bold tracking-tight {{ $powerWarning ? 'text-amber-600' : 'text-slate-900' }}">
                                    {{ $item['value'] }}
                                </span>
                                <span class="text-[10px] text-slate-400">{{ $item['unit'] }}</span>
                            </div>
                        </div>
                    @endif
                @endforeach

            </div>
        </section>

        {{-- DEVICE INFORMATION --}}
        <section class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">

            <div class="mb-5">
                <h2 class="font-bold text-slate-800">Informasi Perangkat</h2>
                <p class="mt-1 text-xs text-slate-500">Identitas ONT dan lokasi perangkat pada OLT.</p>
            </div>

            <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">

                @foreach($deviceItems as $item)
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            {{ $item['label'] }}
                        </p>

                        <p class="mt-1 break-all text-sm font-semibold text-slate-700">
                            {{ $item['value'] ?? '-' }}
                        </p>
                    </div>
                @endforeach

            </div>
        </section>

        {{-- WAN INFORMATION --}}
        <section class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">

            <div class="mb-5">
                <h2 class="font-bold text-slate-800">WAN Configuration</h2>
                <p class="mt-1 text-xs text-slate-500">Informasi koneksi jaringan pelanggan.</p>
            </div>

            <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">

                @foreach($wanItems as $item)
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            {{ $item['label'] }}
                        </p>

                        <div class="mt-1 flex items-center gap-2">
                            <p class="break-all text-sm font-semibold text-slate-700">
                                {{ $item['value'] }}
                            </p>

                            @if($item['value'] !== '-')
                                <button
                                    type="button"
                                    data-copy="{{ $item['value'] }}"
                                    class="copy-value shrink-0 text-slate-400 transition hover:text-cyan-600"
                                    title="Salin">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <rect x="8" y="8" width="13" height="13" rx="2" stroke-width="1.7"/>
                                        <path stroke-linecap="round" stroke-width="1.7" d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach

            </div>
        </section>

        {{-- WLAN INFORMATION --}}
        @if(!empty($ssids))
            <section class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">

                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-slate-800">Wireless / WLAN</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Informasi SSID dan perangkat yang terhubung.
                        </p>
                    </div>

                    <span class="rounded-lg bg-cyan-50 px-2.5 py-1.5 text-xs font-bold text-cyan-700">
                        {{ count($ssids) }} SSID
                    </span>
                </div>

                <div class="divide-y divide-slate-100">

                    @foreach($ssids as $ssid)
                        @php
                            $ssidIndex = (string) ($ssid['index'] ?? '');
                            $is5G = in_array($ssidIndex, ['5', '6', '7', '8']);
                            $ssidOnline = strtolower($ssid['opState'] ?? '') === 'up';
                        @endphp

                        <div class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">

                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                            d="M5 9a11 11 0 0 1 14 0M8 12a7 7 0 0 1 8 0m-5 3a3 3 0 0 1 2 0m-1 4h.01"/>
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-800">
                                        {{ $ssid['name'] ?? 'Unknown SSID' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Index {{ $ssidIndex }} · {{ $is5G ? '5 GHz' : '2.4 GHz' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 sm:justify-end">

                                <div class="text-right">
                                    <p class="text-sm font-bold text-slate-800">
                                        {{ $ssid['deviceCount'] ?? 0 }}
                                        <span class="font-normal text-slate-400">
                                            / {{ $ssid['deviceMax'] ?? '-' }}
                                        </span>
                                    </p>
                                    <p class="text-[10px] text-slate-400">Connected devices</p>
                                </div>

                                <span class="rounded-md px-2 py-1 text-[10px] font-bold uppercase
                                    {{ $ssidOnline ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ $ssid['opState'] ?? 'unknown' }}
                                </span>

                                <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-500">
                                    {{ $ssid['adminState'] ?? '-' }}
                                </span>

                                <button
                                    type="button"
                                    data-copy="{{ $ssid['name'] ?? '' }}"
                                    class="copy-value rounded-lg border border-slate-200 p-2 text-slate-400 transition hover:bg-slate-50 hover:text-cyan-600"
                                    title="Salin SSID">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <rect x="8" y="8" width="13" height="13" rx="2" stroke-width="1.7"/>
                                        <path stroke-linecap="round" stroke-width="1.7" d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>
                                    </svg>
                                </button>

                            </div>
                        </div>
                    @endforeach

                </div>
            </section>
        @endif

    @elseif(!$isCompleted)

        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-5">
            <h2 class="font-bold text-rose-700">Request gagal diproses</h2>
            <p class="mt-1 text-sm text-rose-600">
                {{ $displayData['message'] ?? $resultData['message'] ?? 'Terjadi kesalahan saat memproses request.' }}
            </p>
        </div>

    @else

        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 text-center">
            <p class="text-sm text-slate-500">Tidak ada data ONT yang dikembalikan.</p>
        </div>

    @endif

    {{-- REQUEST INFORMATION --}}
    <section class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">

        <h2 class="mb-5 font-bold text-slate-800">Informasi Request</h2>

        <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2">

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Operation</p>
                <p class="mt-2 text-sm font-semibold text-cyan-700">
                    {{ strtoupper(str_replace('.', ' · ', $request->operation)) }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Status</p>
                <p class="mt-2 text-sm font-semibold {{ $isCompleted ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ strtoupper($request->status) }}
                </p>
            </div>

            <div class="sm:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Request ID</p>
                <p class="mt-2 break-all font-mono text-xs text-slate-600">{{ $request->uuid }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Started At</p>
                <p class="mt-2 text-sm font-medium text-slate-700">
                    {{ $request->started_at?->format('d-m-Y H:i:s') ?? '-' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Duration</p>
                <p class="mt-2 text-sm font-medium text-slate-700">
                    {{ $request->duration_ms !== null ? number_format($request->duration_ms) . ' ms' : '-' }}
                </p>
            </div>

        </div>
    </section>

    {{-- RAW JSON --}}
    @if(!empty($displayData))
        <section class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">

            <details>
                <summary class="cursor-pointer text-sm font-bold text-slate-700 hover:text-cyan-700">
                    Lihat JSON mentah
                </summary>

                <div class="mt-4">
                    <div class="mb-3 flex justify-end">
                        <button
                            type="button"
                            id="copyResult"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                            Salin JSON
                        </button>
                    </div>

                    <pre id="resultJson" class="max-h-[500px] overflow-auto rounded-xl border border-slate-100 bg-slate-50 p-4 font-mono text-xs leading-relaxed whitespace-pre-wrap break-words text-slate-700">{{ $jsonOutput }}</pre>
                </div>
            </details>
        </section>
    @endif

    {{-- ACTIONS --}}
    <div class="mt-6 flex flex-wrap gap-3">

        <a href="{{ route('dashboard') }}" class="jn-button px-5 py-2.5 text-sm shadow-sm">
            Dashboard
        </a>

        <a href="{{ url()->previous() }}"
           class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
            Kembali
        </a>

    </div>

    {{-- COPY & ONT CONTROL HANDLER --}}
    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // ========================================
        // COPY HANDLER
        // ========================================

        async function copyText(value, button = null) {
            if (!value) return;

            try {
                await navigator.clipboard.writeText(value);

                if (button) {
                    const label = button.querySelector('.copy-label');
                    const original = label ? label.textContent : button.textContent;

                    if (label) {
                        label.textContent = 'Tersalin';
                    } else {
                        button.textContent = 'Tersalin';
                    }

                    button.classList.add('text-emerald-600');

                    setTimeout(() => {
                        if (label) {
                            label.textContent = original;
                        } else {
                            button.textContent = original;
                        }

                        button.classList.remove('text-emerald-600');
                    }, 1500);
                }

            } catch (error) {
                console.error('[JANETA] Copy error:', error);
                if (button) button.textContent = 'Gagal menyalin';
            }
        }

        document.querySelectorAll('.copy-value').forEach(button => {
            button.addEventListener('click', () => {
                copyText(button.dataset.copy, button);
            });
        });

        const copyJsonButton = document.getElementById('copyResult');
        const jsonContent = document.getElementById('resultJson');

        if (copyJsonButton && jsonContent) {
            copyJsonButton.addEventListener('click', () => {
                copyText(jsonContent.textContent, copyJsonButton);
            });
        }


        // ========================================
        // JANETA - ONT CONTROL
        // ========================================

        const ontControl = document.getElementById('ontControl');

        if (!ontControl) return;

        const controlButtons = ontControl.querySelectorAll('.ont-control-button');

        const messageBox = document.getElementById('ontControlMessage');
        const messageTitle = document.getElementById('ontControlTitle');
        const messageText = document.getElementById('ontControlText');
        const messageIcon = document.getElementById('ontControlIcon');

        const actionConfig = {
            restart: {
                title: 'Restart ONT',
                loading: 'Restarting...',
                confirm: 'Restart ONT ini?\n\nKoneksi pelanggan mungkin terputus sementara.',
            },

            activate: {
                title: 'Aktifkan ONT',
                loading: 'Activating...',
                confirm: 'Aktifkan kembali ONT ini?',
            },

            deactivate: {
                title: 'Nonaktifkan ONT',
                loading: 'Deactivating...',
                confirm: 'Nonaktifkan ONT ini?\n\nPERINGATAN: Koneksi pelanggan akan terputus.',
            },
        };


        // ========================================
        // MESSAGE HANDLER
        // ========================================

        function showControlMessage(type, title, message) {

            const styles = {
                success: {
                    box: 'bg-emerald-50',
                    title: 'text-emerald-700',
                    text: 'text-emerald-600',
                    icon: `
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="m5 13 4 4L19 7"/>
                        </svg>
                    `,
                },

                error: {
                    box: 'bg-rose-50',
                    title: 'text-rose-700',
                    text: 'text-rose-600',
                    icon: `
                        <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    `,
                },

                info: {
                    box: 'bg-cyan-50',
                    title: 'text-cyan-700',
                    text: 'text-cyan-600',
                    icon: `
                        <svg class="h-4 w-4 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="9" stroke-width="2"/>
                            <path stroke-width="2" stroke-linecap="round" d="M12 11v5m0-8h.01"/>
                        </svg>
                    `,
                },
            };

            const style = styles[type] || styles.info;

            messageBox.className =
                `border-t border-slate-100 px-5 py-4 sm:px-6 ${style.box}`;

            messageBox.classList.remove('hidden');

            messageTitle.className = `text-sm font-bold ${style.title}`;
            messageText.className = `mt-1 break-words text-xs ${style.text}`;

            messageTitle.textContent = title;
            messageText.textContent = message;
            messageIcon.innerHTML = style.icon;
        }


        // ========================================
        // BUTTON LOADING
        // Preserve original icon
        // ========================================

        function setButtonLoading(button, loading, action) {

            const label = button.querySelector('.ont-button-label');
            const icon = button.querySelector('.ont-button-icon');

            if (loading) {

                button.dataset.originalLabel = label.textContent.trim();
                button.dataset.originalIcon = icon.innerHTML;

                label.textContent = actionConfig[action].loading;

                icon.innerHTML = `
                    <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25"
                            cx="12" cy="12" r="10"
                            stroke="currentColor"
                            stroke-width="3">
                        </circle>

                        <path class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z">
                        </path>
                    </svg>
                `;

            } else {

                label.textContent = button.dataset.originalLabel || label.textContent;
                icon.innerHTML = button.dataset.originalIcon || icon.innerHTML;

            }

            // Disable semua tombol selama request berlangsung
            controlButtons.forEach(item => {
                item.disabled = loading;
            });
        }


        // ========================================
        // REQUEST HANDLER
        // ========================================

        controlButtons.forEach(button => {

            button.addEventListener('click', async () => {

                const action = button.dataset.ontAction;
                const config = actionConfig[action];

                if (!config || button.disabled) return;

                if (!confirm(config.confirm)) return;

                messageBox.classList.add('hidden');

                setButtonLoading(button, true, action);

                try {

                    const response = await fetch(ontControl.dataset.url, {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': ontControl.dataset.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },

                        body: JSON.stringify({
                            action: action,
                            olt: ontControl.dataset.olt,
                            frame: ontControl.dataset.frame,
                            slot: ontControl.dataset.slot,
                            port: ontControl.dataset.port,
                            ont_id: ontControl.dataset.ontId,
                        }),
                    });

                    const result = await response.json();

                    if (!response.ok) {

                        let errorMessage = result.message || 'Request gagal dikirim.';

                        if (result.errors) {
                            errorMessage = Object.values(result.errors)
                                .flat()
                                .join(' ');
                        }

                        throw new Error(errorMessage);
                    }

                    showControlMessage(
                        'success',
                        'Request masuk antrean',
                        result.message || `${config.title} berhasil dimasukkan ke antrean JANETA.`
                    );

                } catch (error) {

                    console.error('[JANETA] ONT Control Error:', error);

                    showControlMessage(
                        'error',
                        'Request gagal',
                        error.message || 'Terjadi kesalahan saat mengirim request.'
                    );

                } finally {

                    setButtonLoading(button, false, action);

                }

            });

        });

    });
    </script>

</x-layouts.dashboard>
