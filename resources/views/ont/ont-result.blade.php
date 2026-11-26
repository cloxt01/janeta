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
        $fsp = "{$frame}/{$slot}/{$port}";

        $state = strtolower($ont['state'] ?? $ont['runState'] ?? 'unknown');
        $isOnline = $state === 'online';

        $controlFlag = strtolower(trim((string) ($ont['controlFlag'] ?? '')));

        $customerId = $ont['customerId'] ?? $ont['idcust'] ?? '-';
        $customerName = $ont['customerName'] ?? '-';

        // Gambar modem
        $modemImagePath = $type ? public_path('images/modem/' . basename($type) . '.png') : null;
        $modemImage = $modemImagePath && file_exists($modemImagePath)
            ? asset('images/modem/' . basename($type) . '.png')
            : null;

        // WLAN
        $wlan = $ont['wlan'] ?? [];
        $ssids = $wlan['ssids'] ?? [];
        $wifiDevices = collect($ssids)->sum(fn ($s) => (int) ($s['deviceCount'] ?? 0));
        $wifiUp = collect($ssids)->contains(fn ($s) => strtolower($s['opState'] ?? '') === 'up');

        $jsonOutput = json_encode($displayData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ---- Optik ----
        $num = fn ($v) => is_numeric($v) && (float) $v > -79 ? (float) $v : null; // -80.00 = placeholder tanpa data
        $fmt = fn ($v, $d = 2) => $v === null ? '-' : number_format($v, $d);
        $grade = function (?float $v): string {
            if ($v === null) return 'na';
            if ($v > -8 || $v <= -28) return 'bad';
            if ($v < -25) return 'warn';
            return 'ok';
        };
        $ontRx = $num($ont['rxPower'] ?? null);
        $oltRx = $num($ont['oltRxPower'] ?? null);
        $ontTx = $num($ont['txPower'] ?? null);
        $oltTx = $num($ont['oltTxPower'] ?? null);
        $lossUp = ($ontTx !== null && $oltRx !== null) ? $ontTx - $oltRx : null;
        $lossDown = ($oltTx !== null && $ontRx !== null) ? $oltTx - $ontRx : null;
        $gDown = $grade($ontRx);
        $gUp = $grade($oltRx);
        $rank = ['na' => 0, 'ok' => 1, 'warn' => 2, 'bad' => 3];
        $worst = collect([$gDown, $gUp])->sortByDesc(fn ($g) => $rank[$g])->first();
        $overall = $isOnline ? $worst : 'bad';
        $gText = ['ok' => 'OK', 'warn' => 'Lemah', 'bad' => 'Kritis', 'na' => 'N/A'];
        $lineColor = ['ok' => '#7fcf8a', 'warn' => '#e0b155', 'bad' => '#f08a8a', 'na' => '#5a5f66'][$overall];
        $pillColor = ['ok' => '#4cd286', 'warn' => '#e0b155', 'bad' => '#f08a8a', 'na' => '#6b7078'][$overall];
        $ponType = $ont['ponType'] ?? 'GPON';
        $odnClass = $ont['odnClass'] ?? '-';
        $distance = isset($ont['distance']) && is_numeric($ont['distance']) ? number_format((float) $ont['distance']) . ' m' : '-';

        $wanOk = in_array(strtolower((string) ($ont['wanStatus'] ?? '')), ['connected', 'up', 'online'], true);

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
            ['label' => 'Brand', 'value' => $brand],
            ['label' => 'Model', 'value' => $type ?? '-'],
            ['label' => 'OLT', 'value' => $olt],
            ['label' => 'F/S/P', 'value' => $fsp],
            ['label' => 'ONT ID', 'value' => $ontId],
            ['label' => 'Status', 'value' => strtoupper($state)],
            ['label' => 'Control Flag', 'value' => $controlFlag ?: '-'],
            ['label' => 'Last Up Time', 'value' => $ont['lastUpTime'] ?? '-'],
            ['label' => 'Last Down Cause', 'value' => $ont['lastDownCause'] ?? '-'],
        ];

        $copyIcon = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="8" y="8" width="13" height="13" rx="2" stroke-width="1.7"/><path stroke-linecap="round" stroke-width="1.7" d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>';
    @endphp

    <style>
        /* ---------- Diagram ---------- */
        .ont-stage{border-radius:14px;background:#202020;border:1px solid #2e2e2e;padding:12px 6px 6px;overflow-x:auto}
        .ont-stage svg{display:block;width:100%;min-width:620px;max-width:880px;height:auto;margin:0 auto;font-family:ui-sans-serif,system-ui,sans-serif}
        .ont-stage .t-label{fill:#e5e5e5;font-size:13px;font-weight:600}
        .ont-stage .t-sub{fill:#8a8a8a;font-size:11px}
        .ont-stage [data-target]{cursor:pointer;outline:0}
        .ont-stage .ring{fill:none;stroke:transparent;stroke-width:3;transition:stroke .15s}
        .ont-stage [data-target]:hover .ring,.ont-stage [data-target]:focus-visible .ring,.ont-stage [data-target].is-active .ring{stroke:#4cd286}
        .ont-stage .body-ring{fill:none;stroke:transparent;stroke-width:3;transition:stroke .15s}
        .ont-stage [data-target]:hover .body-ring,.ont-stage [data-target]:focus-visible .body-ring,.ont-stage [data-target].is-active .body-ring{stroke:#4cd286}
        .ont-stage .hit{fill:transparent}
        .stage-hint{margin:10px 0 2px;color:#8a8a8a;font-size:12px;text-align:center}

        /* ---------- Tooltip & popup ---------- */
        .tip{position:fixed;z-index:60;max-width:260px;pointer-events:none;border-radius:10px;border:1px solid #3a3a3a;background:#111;color:#d0d0d0;padding:8px 11px;font-size:12px;line-height:1.45;box-shadow:0 8px 24px rgb(0 0 0 / 50%);opacity:0;transform:translateY(4px);transition:opacity .12s,transform .12s}
        .tip.show{opacity:1;transform:none}
        .tip b{display:block;color:#fff;font-size:12.5px}
        .tip i{display:block;margin-top:3px;font-style:normal;color:#4cd286;font-size:11px}
        .popup{position:fixed;inset:0;z-index:70;display:none;align-items:center;justify-content:center;padding:16px;background:rgb(0 0 0 / 65%);backdrop-filter:blur(3px)}
        .popup.open{display:flex}
        .popup-dialog{position:relative;width:100%;max-width:760px;max-height:88vh;overflow:auto;border-radius:18px;border:1px solid #2e2e2e;background:#1c1c1c;padding:24px;box-shadow:0 24px 60px rgb(0 0 0 / 60%);outline:0;animation:pop .18s ease-out}
        .popup-close{position:absolute;top:12px;right:12px;display:grid;place-items:center;width:32px;height:32px;border-radius:9999px;border:0;background:#2c2c2c;color:#bbb;font-size:18px;line-height:1;cursor:pointer}
        .popup-close:hover{background:#3a3a3a;color:#fff}
        .popup-body .info-panel{margin:0;border:0;box-shadow:none;padding:0;border-radius:0;background:transparent}
        .popup-body .info-panel>h2:first-child,.popup-body>.info-panel h2{padding-right:36px}
        @keyframes pop{from{opacity:0;transform:translateY(8px) scale(.98)}to{opacity:1;transform:none}}
        @media(prefers-reduced-motion:reduce){.popup-dialog{animation:none}.tip{transition:none}}

        /* ---------- Panel info ---------- */
        .info-panel{border:1px solid #2e2e2e;border-radius:14px;background:#1c1c1c;padding:20px}
        .info-panel h2{font-size:15px;font-weight:600;color:#f2f2f2}
        .info-panel .sub{margin-top:3px;font-size:12px;color:#8a8a8a}
        .kv{display:grid;gap:14px 28px;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-top:16px}
        .kv dt{font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#8a8a8a}
        .kv dd{margin-top:3px;font-size:13.5px;font-weight:600;color:#f2f2f2;word-break:break-all}

        /* ---------- Tabel pengukuran optik ---------- */
        .ld-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}
        .ld-table{display:grid;grid-template-columns:minmax(96px,.9fr) 1.1fr;gap:2px;align-content:start}
        .ld-l,.ld-v{display:flex;align-items:center;min-height:32px;padding:4px 10px;font-size:12.5px}
        .ld-l{background:#262626;color:#b5b5b5;font-weight:500;border-radius:6px 0 0 6px}
        .ld-v{justify-content:flex-end;background:#222;color:#f2f2f2;font-weight:700;font-variant-numeric:tabular-nums;text-align:right;border-radius:0 6px 6px 0}
        .ld-ok{background:#14281d!important;color:#4cd286!important}
        .ld-warn{background:#2a2417!important;color:#e0b155!important}
        .ld-bad{background:#2a1717!important;color:#f08a8a!important}
        .ld-na{background:#242424!important;color:#8a8a8a!important}
        .ld-chip{display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:4px 12px;font-size:12px;font-weight:700}
        .ld-foot{margin-top:12px;color:#8a8a8a;font-size:11px}
        @media(max-width:760px){.ld-grid{grid-template-columns:1fr}}
    </style>

    {{-- HEADER --}}
    <div class="mb-6">
        <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
            Workspace · Hasil request
        </div>

        <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
            Hasil <span class="text-[#8a8a8a]">operasi.</span>
        </h1>

        <p class="mt-2.5 text-xs text-[#a0a0a0]">
            Detail hasil eksekusi request perangkat OLT
        </p>
    </div>

    {{-- REQUEST STATUS --}}
    <div class="mb-5 flex items-center gap-4 rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] px-5 py-4">

        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full
            {{ $isCompleted ? 'bg-[#14281d] text-[#4cd286]' : 'bg-[#2a1717] text-[#f08a8a]' }}">

            @if($isCompleted)
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            @endif

        </div>

        <div class="min-w-0">
            <div class="text-sm font-semibold {{ $isCompleted ? 'text-[#4cd286]' : 'text-[#f08a8a]' }}">
                {{ $isCompleted ? 'Request berhasil diselesaikan' : 'Request tidak berhasil diselesaikan' }}
            </div>

            <p class="mt-0.5 text-[11px] text-[#8a8a8a]">
                {{ strtoupper($request->status) }} · JANETA OLT Management
            </p>
        </div>
    </div>

    @if($isCompleted && !empty($ont))

        <section class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2e2e2e] px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-[#f2f2f2]">
                        {{ $type ?? 'ONT' }}
                        <span class="font-normal text-[#8a8a8a]">· {{ $customerId }}</span>
                    </h2>
                    <p class="mt-0.5 text-[11px] text-[#8a8a8a]">{{ $olt }} · F/S/P {{ $fsp }} · ONT ID {{ $ontId }}</p>
                </div>

                <span class="rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-wider
                    {{ $isOnline ? 'border-[#1f5a38] bg-[#14281d] text-[#4cd286]' : 'border-[#5e2b2b] bg-[#2a1717] text-[#f08a8a]' }}">
                    {{ strtoupper($state) }}
                </span>
            </div>

            <div class="p-5">

                {{-- ===== DIAGRAM INTERAKTIF ===== --}}
                <div class="ont-stage" id="ontStage">
                    <svg viewBox="0 0 800 520" role="group" aria-label="Diagram ONT {{ $sn }}">
                        <defs>
                            <linearGradient id="ontBody" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#6cbcf2"/><stop offset="1" stop-color="#4a9fe0"/>
                            </linearGradient>
                            <linearGradient id="ontBodyOff" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#7d8793"/><stop offset="1" stop-color="#5f6872"/>
                            </linearGradient>
                        </defs>

                        {{-- OLT (kiri atas) → Optik --}}
                        <path d="M150 70 H280 V200" fill="none" stroke="{{ $lineColor }}" stroke-width="2" {!! $overall === 'na' ? 'stroke-dasharray="5 4"' : '' !!}/>
                        <g data-target="optical" tabindex="0" data-tip-title="Optik &amp; Link · {{ $isOnline ? $gText[$overall] : 'ONT offline' }}" data-tip-sub="ONT RX {{ $ontRx !== null ? $fmt($ontRx) . ' dBm' : '-' }} · OLT RX {{ $oltRx !== null ? $fmt($oltRx) . ' dBm' : '-' }}" role="button" aria-label="Info optik dan link">
                            <rect class="hit" x="60" y="30" width="240" height="180"/>
                            <circle cx="120" cy="70" r="30" fill="#2f9e4f"/>
                            <circle class="ring" cx="120" cy="70" r="36"/>
                            <g fill="none" stroke="#fff" stroke-width="2" stroke-linejoin="round">
                                <rect x="106" y="55" width="28" height="9" rx="2"/><rect x="106" y="66" width="28" height="9" rx="2"/><rect x="106" y="77" width="28" height="9" rx="2"/>
                            </g>
                            <g fill="#fff"><circle cx="111" cy="59.5" r="1.4"/><circle cx="111" cy="70.5" r="1.4"/><circle cx="111" cy="81.5" r="1.4"/></g>
                            <text x="120" y="122" text-anchor="middle" class="t-label">OLT</text>
                            <text x="120" y="138" text-anchor="middle" class="t-sub">{{ $olt }}</text>
                            <rect x="273" y="128" width="14" height="26" rx="7" fill="{{ $pillColor }}"/><path d="M280 134V148" stroke="#111" stroke-width="2" stroke-linecap="round"/>
                            <text x="296" y="145" class="t-sub">Optik · {{ $gText[$overall] }}</text>
                        </g>

                        {{-- Wi-Fi (kanan atas) --}}
                        <path d="M620 140V200" stroke="#4a7ea8" stroke-width="2" stroke-dasharray="5 4"/>
                        <g data-target="wifi" tabindex="0" data-tip-title="Wi-Fi" data-tip-sub="{{ count($ssids) }} SSID · {{ $wifiDevices }} perangkat terhubung" role="button" aria-label="Info Wi-Fi">
                            <rect class="hit" x="540" y="20" width="160" height="125"/>
                            <text x="620" y="40" text-anchor="middle" class="t-label">Wi-Fi ({{ $wifiDevices }} perangkat)</text>
                            <g fill="none" stroke="{{ $wifiUp ? '#6cbcf2' : '#555b63' }}" stroke-width="3" stroke-linecap="round">
                                <path d="M601.6 81.6A26 26 0 0 0 601.6 118.4"/><path d="M638.4 81.6A26 26 0 0 1 638.4 118.4"/>
                                <path d="M591.7 71.7A40 40 0 0 0 591.7 128.3" opacity=".7"/><path d="M648.3 71.7A40 40 0 0 1 648.3 128.3" opacity=".7"/>
                                <path d="M580 62A54 54 0 0 0 580 138" opacity=".4"/><path d="M660 62A54 54 0 0 1 660 138" opacity=".4"/>
                            </g>
                            <circle cx="620" cy="100" r="13" fill="none" stroke="{{ $wifiUp ? '#4aa3e8' : '#6b7078' }}" stroke-width="2" stroke-dasharray="3 3"/>
                            <circle cx="620" cy="100" r="7" fill="{{ $wifiUp ? '#4aa3e8' : '#6b7078' }}"/>
                            <circle class="ring" cx="620" cy="100" r="62"/>
                        </g>

                        {{-- Badan ONT --}}
                        <g data-target="device" tabindex="0" data-tip-title="{{ $type ?? 'ONT' }}" data-tip-sub="{{ $sn }} · {{ strtoupper($state) }}" role="button" aria-label="Info perangkat ONT">
                            <rect x="70" y="200" width="660" height="110" rx="14" fill="url(#{{ $isOnline ? 'ontBody' : 'ontBodyOff' }})"/>
                            <rect x="70" y="200" width="660" height="110" rx="14" fill="none" stroke="#fff" stroke-opacity=".35"/>
                            <rect class="body-ring" x="68" y="198" width="664" height="114" rx="15"/>
                            <g stroke="#fff" stroke-opacity=".7" stroke-width="2" stroke-linecap="round">
                                <path d="M80 222H90M80 230H90M80 238H90M710 222H720M710 230H720M710 238H720"/>
                            </g>
                            <rect x="150" y="310" width="34" height="9" rx="3" fill="{{ $isOnline ? '#4a9fe0' : '#5f6872' }}"/><rect x="616" y="310" width="34" height="9" rx="3" fill="{{ $isOnline ? '#4a9fe0' : '#5f6872' }}"/>

                            <text x="112" y="240" fill="#fff" font-size="17" font-weight="700">{{ \Illuminate\Support\Str::limit($type ?? 'ONT', 20, '…') }}</text>
                            <text x="112" y="260" fill="#fff" fill-opacity=".85" font-size="11" font-family="ui-monospace,Menlo,monospace">{{ $sn }}</text>
                            <circle cx="116" cy="284" r="5" fill="{{ $isOnline ? '#6cf08a' : '#ef6b6b' }}"/>
                            <text x="127" y="288" fill="#fff" font-size="12" font-weight="600">{{ $isOnline ? 'Online' : ucfirst($state) }}</text>

                            <circle cx="330" cy="262" r="6" fill="#fff" fill-opacity=".45"/>
                            <g fill="#fff" fill-opacity=".45"><rect x="380" y="250" width="30" height="22" rx="3"/></g>
                            <g stroke="#4a9fe0" stroke-width="2"><path d="M386 255v8M391 255v8M396 255v8M401 255v8"/></g>
                            <rect x="470" y="252" width="46" height="16" rx="2" fill="#fff" fill-opacity=".92" stroke="#0f2a4a" stroke-width="2"/>
                            <rect x="476" y="258" width="34" height="4" fill="#0f2a4a"/>
                            <text x="493" y="287" text-anchor="middle" fill="#0f2a4a" font-size="11" font-weight="700">USB</text>
                        </g>

                        {{-- RESET → Kontrol --}}
                        <g data-target="control" tabindex="0" data-tip-title="Kontrol ONT" data-tip-sub="Restart, aktifkan, atau nonaktifkan ONT" role="button" aria-label="Kontrol ONT: restart, aktifkan, nonaktifkan">
                            <circle cx="600" cy="258" r="17" fill="#ef6b6b"/>
                            <circle class="ring" cx="600" cy="258" r="22"/>
                            <g stroke="#fff" stroke-width="2.2" stroke-linecap="round"><path d="M600 248v20M591 253l18 10M591 263l18-10"/></g>
                            <text x="600" y="291" text-anchor="middle" fill="#0f2a4a" font-size="11" font-weight="700">RESET</text>
                        </g>

                        {{-- WAN --}}
                        <path d="M170 319V398" stroke="{{ $wanOk ? '#7fcf8a' : '#5a5f66' }}" stroke-width="2" {!! $wanOk ? '' : 'stroke-dasharray="5 4"' !!}/>
                        <g data-target="wan" tabindex="0" data-tip-title="WAN · {{ $ont['wanStatus'] ?? '-' }}" data-tip-sub="IP {{ $ont['wanIp'] ?? '-' }}" role="button" aria-label="Info WAN">
                            <rect class="hit" x="110" y="395" width="120" height="110"/>
                            @if($wanOk)<rect x="163" y="344" width="14" height="26" rx="7" fill="#4cd286"/><path d="M170 350V364" stroke="#111" stroke-width="2" stroke-linecap="round"/>@endif
                            <circle cx="170" cy="430" r="30" fill="{{ $wanOk ? '#2f9e4f' : '#555b63' }}"/>
                            <circle class="ring" cx="170" cy="430" r="36"/>
                            <g fill="none" stroke="#fff" stroke-width="2"><circle cx="170" cy="430" r="15"/><ellipse cx="170" cy="430" rx="6.5" ry="15"/><path d="M155 430H185M157 422H183M157 438H183"/></g>
                            <text x="170" y="485" text-anchor="middle" class="t-label">WAN</text>
                            <text x="170" y="502" text-anchor="middle" class="t-sub">{{ $ont['wanIp'] ?? '-' }}</text>
                        </g>

                        {{-- Pelanggan --}}
                        <path d="M400 310V398" stroke="#7fcf8a" stroke-width="2"/>
                        <g data-target="customer" tabindex="0" data-tip-title="Pelanggan" data-tip-sub="{{ $customerId }} · {{ $customerName }}" role="button" aria-label="Info pelanggan">
                            <rect class="hit" x="340" y="395" width="120" height="110"/>
                            <circle cx="400" cy="430" r="30" fill="#3b82c4"/>
                            <circle class="ring" cx="400" cy="430" r="36"/>
                            <g fill="#fff"><circle cx="400" cy="422" r="7"/><path d="M386 445c0-8 6-13 14-13s14 5 14 13z"/></g>
                            <text x="400" y="485" text-anchor="middle" class="t-label">Pelanggan</text>
                            <text x="400" y="502" text-anchor="middle" class="t-sub">{{ \Illuminate\Support\Str::limit($customerId, 22) }}</text>
                        </g>

                        {{-- Request --}}
                        <path d="M630 319V398" stroke="#7fcf8a" stroke-width="2"/>
                        <g data-target="request" tabindex="0" data-tip-title="Request" data-tip-sub="{{ strtoupper(str_replace('.', ' · ', $request->operation)) }} · {{ $request->duration_ms !== null ? number_format($request->duration_ms) . ' ms' : '-' }}" role="button" aria-label="Info request">
                            <rect class="hit" x="570" y="395" width="120" height="110"/>
                            <circle cx="630" cy="430" r="30" fill="#2f9e4f"/>
                            <circle class="ring" cx="630" cy="430" r="36"/>
                            <g fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round"><rect x="618" y="416" width="24" height="28" rx="3"/><path d="M623 424H637M623 430H637M623 436H631"/></g>
                            <text x="630" y="485" text-anchor="middle" class="t-label">Request</text>
                            <text x="630" y="502" text-anchor="middle" class="t-sub">{{ $request->duration_ms !== null ? number_format($request->duration_ms) . ' ms' : '-' }}</text>
                        </g>
                    </svg>
                </div>

                <p class="stage-hint">Arahkan kursor untuk ringkasan, klik bagian gambar untuk melihat detail teknisnya.</p>
            </div>

            {{-- ===== PANEL INFO (sumber konten popup) ===== --}}
            <div id="popupSource" class="hidden">

                {{-- Optik --}}
                <div data-panel="optical" class="info-panel ld hidden">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2>Optik &amp; Link</h2>
                            <p class="sub">Daya optik jalur OLT ⇄ ONT · {{ $ponType }}</p>
                        </div>
                        <span class="ld-chip ld-{{ $overall }}">{{ $isOnline ? $gText[$overall] : 'ONT tidak online' }}</span>
                    </div>

                    <div class="ld-grid">
                        <div class="ld-table">
                            <div class="ld-l">ONT RX</div><div class="ld-v ld-{{ $gDown }}">{{ $ontRx !== null ? $fmt($ontRx) . ' dBm' : '-' }}</div>
                            <div class="ld-l">OLT RX</div><div class="ld-v ld-{{ $gUp }}">{{ $oltRx !== null ? $fmt($oltRx) . ' dBm' : '-' }}</div>
                            <div class="ld-l">ONT TX</div><div class="ld-v">{{ $ontTx !== null ? $fmt($ontTx) . ' dBm' : '-' }}</div>
                            <div class="ld-l">OLT TX</div><div class="ld-v">{{ $oltTx !== null ? $fmt($oltTx) . ' dBm' : '-' }}</div>
                        </div>
                        <div class="ld-table">
                            <div class="ld-l">ODN Loss ↑</div><div class="ld-v">{{ $lossUp !== null ? $fmt($lossUp, 1) . ' dB' : '-' }}</div>
                            <div class="ld-l">ODN Loss ↓</div><div class="ld-v">{{ $lossDown !== null ? $fmt($lossDown, 1) . ' dB' : '-' }}</div>
                            <div class="ld-l">Suhu</div><div class="ld-v">{{ isset($ont['temp']) && $ont['temp'] !== '-' ? $ont['temp'] . ' °C' : '-' }}</div>
                            <div class="ld-l">Voltase</div><div class="ld-v">{{ isset($ont['voltage']) && $ont['voltage'] !== '0' ? $ont['voltage'] . ' V' : '-' }}</div>
                            <div class="ld-l">Jarak</div><div class="ld-v">{{ $distance }}</div>
                            <div class="ld-l">Kelas ODN</div><div class="ld-v">{{ $odnClass }}</div>
                        </div>
                    </div>
                    <p class="ld-foot">Ambang: OK −8 s/d −25 dBm · Lemah −25 s/d −28 dBm · Kritis di bawah −28 atau di atas −8 dBm. Nilai kosong berarti OLT tidak mengembalikan data.</p>
                </div>

                {{-- Perangkat --}}
                <div data-panel="device" class="info-panel hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-4">
                            @if($modemImage)
                                <img src="{{ $modemImage }}" alt="{{ $type }}" class="h-16 w-16 rounded-xl bg-[#242424] object-contain p-1" onerror="this.style.display='none'">
                            @endif
                            <div>
                                <h2>Informasi perangkat</h2>
                                <p class="sub">Identitas ONT dan lokasinya pada OLT.</p>
                            </div>
                        </div>
                        <button type="button" data-copy="{{ $sn }}" class="copy-value rounded-full border border-[#3a3a3a] px-4 py-1.5 text-xs font-semibold text-[#c4c4c4] transition hover:bg-[#2a2a2a]">
                            <span class="copy-label">Salin SN</span>
                        </button>
                    </div>
                    <dl class="kv">
                        @foreach($deviceItems as $item)
                            <div><dt>{{ $item['label'] }}</dt><dd>{{ $item['value'] ?? '-' }}</dd></div>
                        @endforeach
                    </dl>
                </div>

                {{-- Wi-Fi --}}
                <div data-panel="wifi" class="info-panel hidden">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2>Wireless / WLAN</h2>
                            <p class="sub">SSID dan perangkat yang terhubung.</p>
                        </div>
                        <span class="rounded-full border border-[#3a3a3a] bg-[#242424] px-3 py-1 text-[11px] font-medium text-[#c4c4c4]">{{ count($ssids) }} SSID · {{ $wifiDevices }} perangkat</span>
                    </div>

                    @if(empty($ssids))
                        <p class="mt-4 text-sm text-[#8a8a8a]">Data WLAN tidak tersedia. ONT mungkin tidak mendukung pembacaan WLAN atau sedang offline.</p>
                    @else
                        <div class="mt-4 divide-y divide-[#272727]">
                            @foreach($ssids as $ssid)
                                @php
                                    $ssidIndex = (string) ($ssid['index'] ?? '');
                                    $is5G = in_array($ssidIndex, ['5', '6', '7', '8']);
                                    $ssidOnline = strtolower($ssid['opState'] ?? '') === 'up';
                                @endphp
                                <div class="flex flex-col gap-3 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-[#f2f2f2]">{{ $ssid['name'] ?? 'Unknown SSID' }}</p>
                                        <p class="mt-1 text-xs text-[#8a8a8a]">Index {{ $ssidIndex }} · {{ $is5G ? '5 GHz' : '2.4 GHz' }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                                        <p class="text-sm font-semibold text-[#f2f2f2]">{{ $ssid['deviceCount'] ?? 0 }}<span class="font-normal text-[#8a8a8a]"> / {{ $ssid['deviceMax'] ?? '-' }}</span> <span class="text-[10px] font-normal text-[#8a8a8a]">perangkat</span></p>
                                        <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold uppercase {{ $ssidOnline ? 'border-[#1f5a38] bg-[#14281d] text-[#4cd286]' : 'border-[#5e2b2b] bg-[#2a1717] text-[#f08a8a]' }}">{{ $ssid['opState'] ?? 'unknown' }}</span>
                                        <span class="rounded-full bg-[#2c2c2c] px-2.5 py-0.5 text-[10px] font-medium text-[#bbb]">{{ $ssid['adminState'] ?? '-' }}</span>
                                        <button type="button" data-copy="{{ $ssid['name'] ?? '' }}" class="copy-value rounded-full border border-[#3a3a3a] p-2 text-[#8a8a8a] transition hover:bg-[#2a2a2a] hover:text-[#f2f2f2]" title="Salin SSID">
                                            {!! $copyIcon !!}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- WAN --}}
                <div data-panel="wan" class="info-panel hidden">
                    <h2>WAN configuration</h2>
                    <p class="sub">Informasi koneksi jaringan pelanggan.</p>
                    <dl class="kv">
                        @foreach($wanItems as $item)
                            <div>
                                <dt>{{ $item['label'] }}</dt>
                                <dd class="flex items-center gap-2">
                                    <span>{{ $item['value'] }}</span>
                                    @if($item['value'] !== '-')
                                        <button type="button" data-copy="{{ $item['value'] }}" class="copy-value shrink-0 text-[#8a8a8a] transition hover:text-[#f2f2f2]" title="Salin">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="8" y="8" width="13" height="13" rx="2" stroke-width="1.7"/><path stroke-linecap="round" stroke-width="1.7" d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>
                                        </button>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Pelanggan --}}
                <div data-panel="customer" class="info-panel hidden">
                    <h2>Pelanggan</h2>
                    <p class="sub">Data pelanggan dari deskripsi ONT di OLT.</p>
                    <dl class="kv">
                        <div><dt>Customer ID</dt><dd>{{ $customerId }}</dd></div>
                        <div><dt>Nama</dt><dd>{{ $customerName }}</dd></div>
                        <div><dt>Serial Number</dt><dd>{{ $sn }}</dd></div>
                        <div><dt>Status ONT</dt><dd>{{ strtoupper($state) }}</dd></div>
                        <div><dt>Last Up Time</dt><dd>{{ $ont['lastUpTime'] ?? '-' }}</dd></div>
                        <div><dt>Last Down Cause</dt><dd>{{ $ont['lastDownCause'] ?? '-' }}</dd></div>
                    </dl>
                </div>

                {{-- Request --}}
                <div data-panel="request" class="info-panel hidden">
                    <h2>Informasi request</h2>
                    <p class="sub">Detail eksekusi request ke JANETA.</p>
                    <dl class="kv">
                        <div><dt>Operation</dt><dd>{{ strtoupper(str_replace('.', ' · ', $request->operation)) }}</dd></div>
                        <div><dt>Status</dt><dd class="{{ $isCompleted ? '!text-[#4cd286]' : '!text-[#f08a8a]' }}">{{ strtoupper($request->status) }}</dd></div>
                        <div><dt>Started At</dt><dd>{{ $request->started_at?->format('d-m-Y H:i:s') ?? '-' }}</dd></div>
                        <div><dt>Duration</dt><dd>{{ $request->duration_ms !== null ? number_format($request->duration_ms) . ' ms' : '-' }}</dd></div>
                        <div class="sm:col-span-2"><dt>Request ID</dt><dd class="font-mono text-xs">{{ $request->uuid }}</dd></div>
                    </dl>

                    @if(!empty($displayData))
                        <details class="mt-5">
                            <summary class="cursor-pointer text-sm font-semibold text-[#c4c4c4] hover:text-[#f2f2f2]">Lihat JSON mentah</summary>
                            <div class="mt-4">
                                <div class="mb-3 flex justify-end">
                                    <button type="button" id="copyResult" class="rounded-full border border-[#3a3a3a] px-4 py-1.5 text-xs font-semibold text-[#c4c4c4] transition hover:bg-[#2a2a2a]">Salin JSON</button>
                                </div>
                                <pre id="resultJson" class="max-h-[500px] overflow-auto whitespace-pre-wrap break-words rounded-xl border border-[#2e2e2e] bg-[#151515] p-4 font-mono text-xs leading-relaxed text-[#c4c4c4]">{{ $jsonOutput }}</pre>
                            </div>
                        </details>
                    @endif
                </div>

                {{-- Kontrol (tombol RESET) --}}
                <div data-panel="control" class="hidden">
                    <section
                        id="ontControl"
                        class="info-panel overflow-hidden !p-0"
                        data-url="{{ route('api.ont.control') }}"
                        data-csrf="{{ csrf_token() }}"
                        data-olt="{{ $olt }}"
                        data-frame="{{ $frame }}"
                        data-slot="{{ $slot }}"
                        data-port="{{ $port }}"
                        data-ont-id="{{ $ontId }}"
                        data-control-flag="{{ $controlFlag }}"
                    >
                        <div class="border-b border-[#2e2e2e] p-5 sm:p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2>ONT control</h2>
                                    <p class="sub">Kelola status dan operasional perangkat ONT.</p>
                                </div>
                                <span class="flex items-center gap-1.5 rounded-full border border-[#3a3a3a] bg-[#242424] px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-[#bbb]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#00a34a]"></span>
                                    Remote management
                                </span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-[#8a8a8a]">
                                <span>OLT <strong class="text-[#f2f2f2]">{{ $olt }}</strong></span>
                                <span>F/S/P <strong class="text-[#f2f2f2]">{{ $fsp }}</strong></span>
                                <span>ONT ID <strong class="text-[#f2f2f2]">{{ $ontId }}</strong></span>
                            </div>
                        </div>

                        <div class="grid gap-3 p-5 sm:grid-cols-3 sm:p-6">

                            {{-- RESTART --}}
                            <button type="button" data-ont-action="restart"
                                    class="ont-control-button group flex items-center gap-3 rounded-xl border border-[#5a4a22] bg-[#2a2417] p-4 text-left transition-all duration-200 hover:bg-[#352d1b] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                                <span class="ont-button-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#3a3120] text-[#e0b155]">
                                    <svg class="h-5 w-5 transition-transform duration-300 group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7v5h-5M4 17v-5h5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.64 9a7 7 0 0 1 11.67-2L20 12M4 12l2.69 5a7 7 0 0 0 11.67-2"/>
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="ont-button-label block text-sm font-semibold text-[#e0b155]">Restart ONT</span>
                                    <span class="block text-[11px] text-[#e0b155]/60">Reboot perangkat</span>
                                </span>
                            </button>

                            @if($controlFlag === 'deactivated')
                                <button type="button" data-ont-action="activate"
                                        class="ont-control-button group flex items-center gap-3 rounded-xl border border-[#1f5a38] bg-[#14281d] p-4 text-left transition-all duration-200 hover:bg-[#193324] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                                    <span class="ont-button-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#1b3a28] text-[#4cd286]">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v10"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.2 5.8a8 8 0 1 0 11.6 0"/>
                                        </svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="ont-button-label block text-sm font-semibold text-[#4cd286]">Aktifkan ONT</span>
                                        <span class="block text-[11px] text-[#4cd286]/60">Enable perangkat</span>
                                    </span>
                                </button>
                            @endif

                            @if($controlFlag === 'active')
                                <button type="button" data-ont-action="deactivate"
                                        class="ont-control-button group flex items-center gap-3 rounded-xl border border-[#5e2b2b] bg-[#2a1717] p-4 text-left transition-all duration-200 hover:bg-[#361d1d] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                                    <span class="ont-button-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#3a2020] text-[#f08a8a]">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v8"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.2 5.8a8 8 0 1 0 11.6 0"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4 4 16 16"/>
                                        </svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="ont-button-label block text-sm font-semibold text-[#f08a8a]">Nonaktifkan ONT</span>
                                        <span class="block text-[11px] text-[#f08a8a]/60">Disable perangkat</span>
                                    </span>
                                </button>
                            @endif
                        </div>

                        <div id="ontControlMessage" class="hidden border-t border-[#2e2e2e] px-5 py-4 sm:px-6">
                            <div class="flex items-start gap-2">
                                <span id="ontControlIcon" class="mt-0.5"></span>
                                <div class="min-w-0">
                                    <p id="ontControlTitle" class="text-sm font-semibold"></p>
                                    <p id="ontControlText" class="mt-1 break-words text-xs"></p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>{{-- /popupSource --}}
        </section>

        <div id="hoverTip" class="tip" role="tooltip"></div>

        <div id="popup" class="popup" aria-hidden="true">
            <div id="popupDialog" class="popup-dialog" role="dialog" aria-modal="true" tabindex="-1">
                <button type="button" id="popupClose" class="popup-close" aria-label="Tutup">&times;</button>
                <div id="popupBody" class="popup-body"></div>
            </div>
        </div>

    @elseif(!$isCompleted)

        <div class="rounded-2xl border border-[#5e2b2b] bg-[#2a1717] px-5 py-4">
            <h2 class="text-sm font-semibold text-[#f08a8a]">Request gagal diproses</h2>
            <p class="mt-1 text-sm text-[#f08a8a]/80">
                {{ $displayData['message'] ?? $resultData['message'] ?? 'Terjadi kesalahan saat memproses request.' }}
            </p>
            <p class="mt-3 font-mono text-[11px] text-[#f08a8a]/60">{{ $request->uuid }}</p>
        </div>

    @else

        <div class="rounded-2xl border border-dashed border-[#3a3a3a] bg-[#1c1c1c] px-6 py-12 text-center">
            <p class="text-sm text-[#8a8a8a]">Tidak ada data ONT yang dikembalikan.</p>
        </div>

    @endif

    {{-- ACTIONS --}}
    <div class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('dashboard') }}"
           class="inline-flex h-9 items-center rounded-full bg-[#0b8a3e] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#0da048]">
            Dashboard
        </a>

        <a href="{{ url()->previous() }}"
           class="inline-flex h-9 items-center rounded-full border border-[#3a3a3a] px-5 text-xs font-semibold text-[#d0d0d0] transition-colors hover:bg-[#2a2a2a]">
            Kembali
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // ========================================
            // DIAGRAM INTERAKTIF: tooltip (hover) + popup (klik)
            // ========================================
            const stage = document.getElementById('ontStage');

            if (stage) {
                const tip = document.getElementById('hoverTip');
                const popup = document.getElementById('popup');
                const dialog = document.getElementById('popupDialog');
                const body = document.getElementById('popupBody');
                const source = document.getElementById('popupSource');
                const targets = stage.querySelectorAll('[data-target]');
                let openPanel = null;
                let lastTrigger = null;

                function fillTip(el) {
                    tip.textContent = '';
                    const t = document.createElement('b'); t.textContent = el.dataset.tipTitle || '';
                    const s = document.createElement('span'); s.textContent = el.dataset.tipSub || '';
                    const h = document.createElement('i'); h.textContent = 'Klik untuk detail';
                    tip.append(t, s, h);
                }

                function placeTip(x, y) {
                    const w = tip.offsetWidth, h = tip.offsetHeight;
                    const left = Math.min(Math.max(8, x + 14), window.innerWidth - w - 8);
                    const top = y + 18 + h > window.innerHeight ? y - h - 14 : y + 18;
                    tip.style.left = left + 'px';
                    tip.style.top = Math.max(8, top) + 'px';
                }

                function showTip(el, x, y) {
                    if (popup.classList.contains('open')) return;
                    fillTip(el);
                    placeTip(x, y);
                    tip.classList.add('show');
                }

                function hideTip() { tip.classList.remove('show'); }

                function openPopup(key, trigger) {
                    const panel = source.querySelector('[data-panel="' + key + '"]');
                    if (!panel) return;

                    hideTip();
                    closePopup(false);

                    openPanel = panel;
                    lastTrigger = trigger;
                    panel.classList.remove('hidden');
                    body.appendChild(panel);

                    targets.forEach(t => t.classList.toggle('is-active', t === trigger));
                    popup.classList.add('open');
                    popup.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                    dialog.scrollTop = 0;
                    dialog.focus();
                }

                function closePopup(restoreFocus = true) {
                    if (openPanel) {
                        openPanel.classList.add('hidden');
                        source.appendChild(openPanel);
                        openPanel = null;
                    }
                    popup.classList.remove('open');
                    popup.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    targets.forEach(t => t.classList.remove('is-active'));
                    if (restoreFocus && lastTrigger) lastTrigger.focus({ preventScroll: true });
                }

                targets.forEach(el => {
                    el.addEventListener('mouseenter', (e) => showTip(el, e.clientX, e.clientY));
                    el.addEventListener('mousemove', (e) => { if (tip.classList.contains('show')) placeTip(e.clientX, e.clientY); });
                    el.addEventListener('mouseleave', hideTip);
                    el.addEventListener('focus', () => {
                        const r = el.getBoundingClientRect();
                        showTip(el, r.left + r.width / 2, r.top + r.height / 2);
                    });
                    el.addEventListener('blur', hideTip);
                    el.addEventListener('click', () => openPopup(el.dataset.target, el));
                    el.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            openPopup(el.dataset.target, el);
                        }
                    });
                });

                document.getElementById('popupClose').addEventListener('click', () => closePopup());
                popup.addEventListener('click', (e) => { if (e.target === popup) closePopup(); });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && popup.classList.contains('open')) closePopup();
                });
            }

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

                        if (label) { label.textContent = 'Tersalin'; } else { button.textContent = 'Tersalin'; }
                        button.classList.add('!text-[#4cd286]');

                        setTimeout(() => {
                            if (label) { label.textContent = original; } else { button.textContent = original; }
                            button.classList.remove('!text-[#4cd286]');
                        }, 1500);
                    }
                } catch (error) {
                    console.error('[JANETA] Copy error:', error);
                    if (button) button.textContent = 'Gagal menyalin';
                }
            }

            document.querySelectorAll('.copy-value').forEach(button => {
                button.addEventListener('click', () => copyText(button.dataset.copy, button));
            });

            const copyJsonButton = document.getElementById('copyResult');
            const jsonContent = document.getElementById('resultJson');

            if (copyJsonButton && jsonContent) {
                copyJsonButton.addEventListener('click', () => copyText(jsonContent.textContent, copyJsonButton));
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

            function showControlMessage(type, title, message) {
                const styles = {
                    success: {
                        box: 'bg-[#14281d]', title: 'text-[#4cd286]', text: 'text-[#4cd286]/80',
                        icon: `<svg class="h-4 w-4 text-[#4cd286]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>`,
                    },
                    error: {
                        box: 'bg-[#2a1717]', title: 'text-[#f08a8a]', text: 'text-[#f08a8a]/80',
                        icon: `<svg class="h-4 w-4 text-[#f08a8a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>`,
                    },
                    info: {
                        box: 'bg-[#242424]', title: 'text-[#f2f2f2]', text: 'text-[#a0a0a0]',
                        icon: `<svg class="h-4 w-4 text-[#bbb]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" d="M12 11v5m0-8h.01"/></svg>`,
                    },
                };

                const style = styles[type] || styles.info;

                messageBox.className = `border-t border-[#2e2e2e] px-5 py-4 sm:px-6 ${style.box}`;
                messageBox.classList.remove('hidden');

                messageTitle.className = `text-sm font-semibold ${style.title}`;
                messageText.className = `mt-1 break-words text-xs ${style.text}`;

                messageTitle.textContent = title;
                messageText.textContent = message;
                messageIcon.innerHTML = style.icon;
            }

            function setButtonLoading(button, loading, action) {
                const label = button.querySelector('.ont-button-label');
                const icon = button.querySelector('.ont-button-icon');

                if (loading) {
                    button.dataset.originalLabel = label.textContent.trim();
                    button.dataset.originalIcon = icon.innerHTML;

                    label.textContent = actionConfig[action].loading;

                    icon.innerHTML = `
                    <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z"></path>
                    </svg>
                `;
                } else {
                    label.textContent = button.dataset.originalLabel || label.textContent;
                    icon.innerHTML = button.dataset.originalIcon || icon.innerHTML;
                }

                controlButtons.forEach(item => { item.disabled = loading; });
            }

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
                                errorMessage = Object.values(result.errors).flat().join(' ');
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
                        showControlMessage('error', 'Request gagal', error.message || 'Terjadi kesalahan saat mengirim request.');
                    } finally {
                        setButtonLoading(button, false, action);
                    }
                });
            });
        });
    </script>

</x-layouts.dashboard>
