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
        $lineColor = ['ok' => '#b5d98e', 'warn' => '#f3cf7a', 'bad' => '#f0a3a3', 'na' => '#c9d9e6'][$overall];
        $pillColor = ['ok' => '#b9dd8a', 'warn' => '#f6d58a', 'bad' => '#f4b6b6', 'na' => '#d5e1ec'][$overall];
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
    @endphp

    <style>
        .ont-stage{margin-top:4px;border-radius:14px;background:linear-gradient(180deg,#f3f8fc,#eaf2f9);padding:10px 6px 4px;overflow-x:auto}
        .ont-stage svg{display:block;width:100%;min-width:620px;max-width:880px;height:auto;margin:0 auto;font-family:ui-sans-serif,system-ui,sans-serif}
        .ont-stage .t-label{fill:#334155;font-size:13px;font-weight:600}
        .ont-stage .t-sub{fill:#64748b;font-size:11px}
        .ont-stage [data-target]{cursor:pointer;outline:0}
        .ont-stage .ring{fill:none;stroke:transparent;stroke-width:3;transition:stroke .15s}
        .ont-stage [data-target]:hover .ring,.ont-stage [data-target]:focus-visible .ring,.ont-stage [data-target].is-active .ring{stroke:#2563eb}
        .ont-stage .body-ring{fill:none;stroke:transparent;stroke-width:3;transition:stroke .15s}
        .ont-stage [data-target]:hover .body-ring,.ont-stage [data-target]:focus-visible .body-ring,.ont-stage [data-target].is-active .body-ring{stroke:#1d4ed8}
        .ont-stage .hit{fill:transparent}
        .stage-hint{margin:6px 0 2px;color:#64748b;font-size:12px;text-align:center}

        .tip{position:fixed;z-index:60;max-width:260px;pointer-events:none;border-radius:10px;background:#0f172a;color:#e2e8f0;padding:8px 11px;font-size:12px;line-height:1.45;box-shadow:0 8px 24px rgb(15 23 42 / 28%);opacity:0;transform:translateY(4px);transition:opacity .12s,transform .12s}
        .tip.show{opacity:1;transform:none}
        .tip b{display:block;color:#fff;font-size:12.5px}
        .tip i{display:block;margin-top:3px;font-style:normal;color:#7dd3fc;font-size:11px}
        .popup{position:fixed;inset:0;z-index:70;display:none;align-items:center;justify-content:center;padding:16px;background:rgb(15 23 42 / 55%);backdrop-filter:blur(2px)}
        .popup.open{display:flex}
        .popup-dialog{position:relative;width:100%;max-width:760px;max-height:88vh;overflow:auto;border-radius:18px;background:#fff;padding:24px;box-shadow:0 24px 60px rgb(15 23 42 / 35%);outline:0;animation:pop .18s ease-out}
        .popup-close{position:absolute;top:12px;right:12px;display:grid;place-items:center;width:32px;height:32px;border-radius:9999px;border:0;background:#f1f5f9;color:#475569;font-size:18px;line-height:1;cursor:pointer}
        .popup-close:hover{background:#e2e8f0;color:#0f172a}
        .popup-body .info-panel{margin:0;border:0;box-shadow:none;padding:0;border-radius:0}
        .popup-body .info-panel>h2:first-child,.popup-body>.info-panel h2{padding-right:36px}
        @keyframes pop{from{opacity:0;transform:translateY(8px) scale(.98)}to{opacity:1;transform:none}}
        @media(prefers-reduced-motion:reduce){.popup-dialog{animation:none}.tip{transition:none}}
        .info-panel{margin-top:14px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;padding:20px;box-shadow:0 1px 2px rgb(15 23 42 / 4%)}
        .info-panel h2{font-size:15px;font-weight:700;color:#1e293b}
        .info-panel .sub{margin-top:3px;font-size:12px;color:#64748b}
        .kv{display:grid;gap:14px 28px;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-top:16px}
        .kv dt{font-size:10px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8}
        .kv dd{margin-top:3px;font-size:13.5px;font-weight:600;color:#334155;word-break:break-all}

        /* tabel pengukuran ala alat ukur */
        .ld{--blue:#1d4ed8;--navy:#0f1f4d}
        .ld-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}
        .ld-table{display:grid;grid-template-columns:minmax(96px,.9fr) 1.1fr;gap:2px;align-content:start}
        .ld-l,.ld-v{display:flex;align-items:center;min-height:32px;padding:4px 10px;font-size:12.5px}
        .ld-l{background:var(--blue);color:#fff;font-weight:600}
        .ld-v{justify-content:flex-end;background:#f1f5f9;color:#0f172a;font-weight:700;font-variant-numeric:tabular-nums;text-align:right}
        .ld-ok{background:#4ade80!important;color:#052e16!important}.ld-warn{background:#fbbf24!important;color:#451a03!important}
        .ld-bad{background:#f43f5e!important;color:#fff!important}.ld-na{background:#e2e8f0!important;color:#475569!important}
        .ld-chip{display:inline-flex;align-items:center;gap:6px;border-radius:9999px;padding:4px 12px;font-size:12px;font-weight:800}
        .ld-foot{margin-top:12px;color:#64748b;font-size:11px}
        @media(max-width:760px){.ld-grid{grid-template-columns:1fr}}
    </style>

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

        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-800">{{ $type ?? 'ONT' }} <span class="font-normal text-slate-400">· {{ $customerId }}</span></h2>
                    <p class="mt-1 text-xs text-slate-500">{{ $olt }} · F/S/P {{ $fsp }} · ONT ID {{ $ontId }}</p>
                </div>
                <span class="rounded-md px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider {{ $isOnline ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ strtoupper($state) }}</span>
            </div>

            {{-- ===== DIAGRAM INTERAKTIF ===== --}}
            <div class="ont-stage" id="ontStage">
                <svg viewBox="0 0 800 520" role="group" aria-label="Diagram ONT {{ $sn }}">
                    <defs>
                        <linearGradient id="ontBody" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#6cbcf2"/><stop offset="1" stop-color="#4a9fe0"/>
                        </linearGradient>
                        <linearGradient id="ontBodyOff" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#a9bccd"/><stop offset="1" stop-color="#8ea6bd"/>
                        </linearGradient>
                    </defs>

                    {{-- OLT (kiri atas) → Optik --}}
                    <path d="M150 70 H280 V200" fill="none" stroke="{{ $lineColor }}" stroke-width="2" {!! $overall === 'na' ? 'stroke-dasharray="5 4"' : '' !!}/>
                    <g data-target="optical" tabindex="0" data-tip-title="Optik &amp; Link · {{ $isOnline ? $gText[$overall] : 'ONT offline' }}" data-tip-sub="ONT RX {{ $ontRx !== null ? $fmt($ontRx) . ' dBm' : '-' }} · OLT RX {{ $oltRx !== null ? $fmt($oltRx) . ' dBm' : '-' }}" role="button" aria-label="Info optik dan link">
                        <rect class="hit" x="60" y="30" width="240" height="180"/>
                        <circle cx="120" cy="70" r="30" fill="#6cc24a"/>
                        <circle class="ring" cx="120" cy="70" r="36"/>
                        <g fill="none" stroke="#fff" stroke-width="2" stroke-linejoin="round">
                            <rect x="106" y="55" width="28" height="9" rx="2"/><rect x="106" y="66" width="28" height="9" rx="2"/><rect x="106" y="77" width="28" height="9" rx="2"/>
                        </g>
                        <g fill="#fff"><circle cx="111" cy="59.5" r="1.4"/><circle cx="111" cy="70.5" r="1.4"/><circle cx="111" cy="81.5" r="1.4"/></g>
                        <text x="120" y="122" text-anchor="middle" class="t-label">OLT</text>
                        <text x="120" y="138" text-anchor="middle" class="t-sub">{{ $olt }}</text>
                        <rect x="273" y="128" width="14" height="26" rx="7" fill="{{ $pillColor }}"/><path d="M280 134V148" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                        <text x="296" y="145" class="t-sub">Optik · {{ $gText[$overall] }}</text>
                    </g>

                    {{-- Wi-Fi (kanan atas) --}}
                    <path d="M620 140V200" stroke="#9fcdf0" stroke-width="2" stroke-dasharray="5 4"/>
                    <g data-target="wifi" tabindex="0" data-tip-title="Wi-Fi" data-tip-sub="{{ count($ssids) }} SSID · {{ $wifiDevices }} perangkat terhubung" role="button" aria-label="Info Wi-Fi">
                        <rect class="hit" x="540" y="20" width="160" height="125"/>
                        <text x="620" y="40" text-anchor="middle" class="t-label">Wi-Fi ({{ $wifiDevices }} perangkat)</text>
                        <g fill="none" stroke="{{ $wifiUp ? '#8cc7f1' : '#c3d3e0' }}" stroke-width="3" stroke-linecap="round">
                            <path d="M601.6 81.6A26 26 0 0 0 601.6 118.4"/><path d="M638.4 81.6A26 26 0 0 1 638.4 118.4"/>
                            <path d="M591.7 71.7A40 40 0 0 0 591.7 128.3" opacity=".7"/><path d="M648.3 71.7A40 40 0 0 1 648.3 128.3" opacity=".7"/>
                            <path d="M580 62A54 54 0 0 0 580 138" opacity=".4"/><path d="M660 62A54 54 0 0 1 660 138" opacity=".4"/>
                        </g>
                        <circle cx="620" cy="100" r="13" fill="none" stroke="{{ $wifiUp ? '#4aa3e8' : '#a9bccd' }}" stroke-width="2" stroke-dasharray="3 3"/>
                        <circle cx="620" cy="100" r="7" fill="{{ $wifiUp ? '#4aa3e8' : '#a9bccd' }}"/>
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
                        <rect x="150" y="310" width="34" height="9" rx="3" fill="{{ $isOnline ? '#4a9fe0' : '#8ea6bd' }}"/><rect x="616" y="310" width="34" height="9" rx="3" fill="{{ $isOnline ? '#4a9fe0' : '#8ea6bd' }}"/>

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
                    <path d="M170 319V398" stroke="{{ $wanOk ? '#b5d98e' : '#c9d9e6' }}" stroke-width="2" {!! $wanOk ? '' : 'stroke-dasharray="5 4"' !!}/>
                    <g data-target="wan" tabindex="0" data-tip-title="WAN · {{ $ont['wanStatus'] ?? '-' }}" data-tip-sub="IP {{ $ont['wanIp'] ?? '-' }}" role="button" aria-label="Info WAN">
                        <rect class="hit" x="110" y="395" width="120" height="110"/>
                        @if($wanOk)<rect x="163" y="344" width="14" height="26" rx="7" fill="#b9dd8a"/><path d="M170 350V364" stroke="#fff" stroke-width="2" stroke-linecap="round"/>@endif
                        <circle cx="170" cy="430" r="30" fill="{{ $wanOk ? '#6cc24a' : '#b9cad8' }}"/>
                        <circle class="ring" cx="170" cy="430" r="36"/>
                        <g fill="none" stroke="#fff" stroke-width="2"><circle cx="170" cy="430" r="15"/><ellipse cx="170" cy="430" rx="6.5" ry="15"/><path d="M155 430H185M157 422H183M157 438H183"/></g>
                        <text x="170" y="485" text-anchor="middle" class="t-label">WAN</text>
                        <text x="170" y="502" text-anchor="middle" class="t-sub">{{ $ont['wanIp'] ?? '-' }}</text>
                    </g>

                    {{-- Pelanggan --}}
                    <path d="M400 310V398" stroke="#b5d98e" stroke-width="2"/>
                    <g data-target="customer" tabindex="0" data-tip-title="Pelanggan" data-tip-sub="{{ $customerId }} · {{ $customerName }}" role="button" aria-label="Info pelanggan">
                        <rect class="hit" x="340" y="395" width="120" height="110"/>
                        <circle cx="400" cy="430" r="30" fill="#4aa3e8"/>
                        <circle class="ring" cx="400" cy="430" r="36"/>
                        <g fill="#fff"><circle cx="400" cy="422" r="7"/><path d="M386 445c0-8 6-13 14-13s14 5 14 13z"/></g>
                        <text x="400" y="485" text-anchor="middle" class="t-label">Pelanggan</text>
                        <text x="400" y="502" text-anchor="middle" class="t-sub">{{ \Illuminate\Support\Str::limit($customerId, 22) }}</text>
                    </g>

                    {{-- Request --}}
                    <path d="M630 319V398" stroke="#b5d98e" stroke-width="2"/>
                    <g data-target="request" tabindex="0" data-tip-title="Request" data-tip-sub="{{ strtoupper(str_replace('.', ' · ', $request->operation)) }} · {{ $request->duration_ms !== null ? number_format($request->duration_ms) . ' ms' : '-' }}" role="button" aria-label="Info request">
                        <rect class="hit" x="570" y="395" width="120" height="110"/>
                        <circle cx="630" cy="430" r="30" fill="#6cc24a"/>
                        <circle class="ring" cx="630" cy="430" r="36"/>
                        <g fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round"><rect x="618" y="416" width="24" height="28" rx="3"/><path d="M623 424H637M623 430H637M623 436H631"/></g>
                        <text x="630" y="485" text-anchor="middle" class="t-label">Request</text>
                        <text x="630" y="502" text-anchor="middle" class="t-sub">{{ $request->duration_ms !== null ? number_format($request->duration_ms) . ' ms' : '-' }}</text>
                    </g>
                </svg>
            </div>

            <p class="stage-hint">Arahkan kursor untuk ringkasan, klik bagian gambar untuk melihat detail teknisnya.</p>

            {{-- ===== PANEL INFO ===== --}}
            {{-- Sumber konten popup (dipindah ke modal saat dibuka) --}}
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
                                <img src="{{ $modemImage }}" alt="{{ $type }}" class="h-16 w-16 rounded-lg bg-slate-50 object-contain p-1" onerror="this.style.display='none'">
                            @endif
                            <div>
                                <h2>Informasi Perangkat</h2>
                                <p class="sub">Identitas ONT dan lokasinya pada OLT.</p>
                            </div>
                        </div>
                        <button type="button" data-copy="{{ $sn }}" class="copy-value rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
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
                        <span class="rounded-lg bg-cyan-50 px-2.5 py-1.5 text-xs font-bold text-cyan-700">{{ count($ssids) }} SSID · {{ $wifiDevices }} perangkat</span>
                    </div>

                    @if(empty($ssids))
                        <p class="mt-4 text-sm text-slate-500">Data WLAN tidak tersedia. ONT mungkin tidak mendukung pembacaan WLAN atau sedang offline.</p>
                    @else
                        <div class="mt-4 divide-y divide-slate-100">
                            @foreach($ssids as $ssid)
                                @php
                                    $ssidIndex = (string) ($ssid['index'] ?? '');
                                    $is5G = in_array($ssidIndex, ['5', '6', '7', '8']);
                                    $ssidOnline = strtolower($ssid['opState'] ?? '') === 'up';
                                @endphp
                                <div class="flex flex-col gap-3 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-800">{{ $ssid['name'] ?? 'Unknown SSID' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Index {{ $ssidIndex }} · {{ $is5G ? '5 GHz' : '2.4 GHz' }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                                        <p class="text-sm font-bold text-slate-800">{{ $ssid['deviceCount'] ?? 0 }}<span class="font-normal text-slate-400"> / {{ $ssid['deviceMax'] ?? '-' }}</span> <span class="text-[10px] font-normal text-slate-400">perangkat</span></p>
                                        <span class="rounded-md px-2 py-1 text-[10px] font-bold uppercase {{ $ssidOnline ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $ssid['opState'] ?? 'unknown' }}</span>
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-500">{{ $ssid['adminState'] ?? '-' }}</span>
                                        <button type="button" data-copy="{{ $ssid['name'] ?? '' }}" class="copy-value rounded-lg border border-slate-200 p-2 text-slate-400 transition hover:bg-slate-50 hover:text-cyan-600" title="Salin SSID">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="8" y="8" width="13" height="13" rx="2" stroke-width="1.7"/><path stroke-linecap="round" stroke-width="1.7" d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- WAN --}}
                <div data-panel="wan" class="info-panel hidden">
                    <h2>WAN Configuration</h2>
                    <p class="sub">Informasi koneksi jaringan pelanggan.</p>
                    <dl class="kv">
                        @foreach($wanItems as $item)
                            <div>
                                <dt>{{ $item['label'] }}</dt>
                                <dd class="flex items-center gap-2">
                                    <span>{{ $item['value'] }}</span>
                                    @if($item['value'] !== '-')
                                        <button type="button" data-copy="{{ $item['value'] }}" class="copy-value shrink-0 text-slate-400 transition hover:text-cyan-600" title="Salin">
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
                    <h2>Informasi Request</h2>
                    <p class="sub">Detail eksekusi request ke JANETA.</p>
                    <dl class="kv">
                        <div><dt>Operation</dt><dd class="text-cyan-700">{{ strtoupper(str_replace('.', ' · ', $request->operation)) }}</dd></div>
                        <div><dt>Status</dt><dd class="{{ $isCompleted ? 'text-emerald-600' : 'text-rose-600' }}">{{ strtoupper($request->status) }}</dd></div>
                        <div><dt>Started At</dt><dd>{{ $request->started_at?->format('d-m-Y H:i:s') ?? '-' }}</dd></div>
                        <div><dt>Duration</dt><dd>{{ $request->duration_ms !== null ? number_format($request->duration_ms) . ' ms' : '-' }}</dd></div>
                        <div class="sm:col-span-2"><dt>Request ID</dt><dd class="font-mono text-xs">{{ $request->uuid }}</dd></div>
                    </dl>

                    @if(!empty($displayData))
                        <details class="mt-5">
                            <summary class="cursor-pointer text-sm font-bold text-slate-700 hover:text-cyan-700">Lihat JSON mentah</summary>
                            <div class="mt-4">
                                <div class="mb-3 flex justify-end">
                                    <button type="button" id="copyResult" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Salin JSON</button>
                                </div>
                                <pre id="resultJson" class="max-h-[500px] overflow-auto rounded-xl border border-slate-100 bg-slate-50 p-4 font-mono text-xs leading-relaxed whitespace-pre-wrap break-words text-slate-700">{{ $jsonOutput }}</pre>
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
                        <div class="border-b border-slate-100 p-5 sm:p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2>ONT Control</h2>
                                    <p class="sub">Kelola status dan operasional perangkat ONT.</p>
                                </div>
                                <span class="flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Remote Management
                            </span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                                <span>OLT <strong class="text-slate-800">{{ $olt }}</strong></span>
                                <span>F/S/P <strong class="text-slate-800">{{ $fsp }}</strong></span>
                                <span>ONT ID <strong class="text-slate-800">{{ $ontId }}</strong></span>
                            </div>
                        </div>

                        <div class="grid gap-3 p-5 sm:grid-cols-3 sm:p-6">

                            {{-- RESTART --}}
                            <button type="button" data-ont-action="restart"
                                    class="ont-control-button group flex items-center gap-3 rounded-xl border border-amber-200/80 bg-amber-50/40 p-4 text-left transition-all duration-200 hover:border-amber-400 hover:bg-amber-50 active:scale-[0.98] active:border-amber-500 active:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50">
                            <span class="ont-button-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 transition-all duration-200 group-hover:bg-amber-200 group-hover:text-amber-700">
                                <svg class="h-5 w-5 transition-transform duration-300 group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7v5h-5M4 17v-5h5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.64 9a7 7 0 0 1 11.67-2L20 12M4 12l2.69 5a7 7 0 0 0 11.67-2"/>
                                </svg>
                            </span>
                                <span class="min-w-0 flex-1">
                                <span class="ont-button-label block text-sm font-bold text-amber-800">Restart ONT</span>
                                <span class="block text-[11px] text-amber-700/70">Reboot perangkat</span>
                            </span>
                            </button>

                            @if($controlFlag === 'deactivated')
                                <button type="button" data-ont-action="activate"
                                        class="ont-control-button group flex items-center gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-4 text-left transition-all duration-200 hover:border-emerald-400 hover:bg-emerald-50 active:scale-[0.98] active:border-emerald-500 active:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50">
                                <span class="ont-button-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 transition-all duration-200 group-hover:bg-emerald-200 group-hover:text-emerald-700">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v10"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.2 5.8a8 8 0 1 0 11.6 0"/>
                                    </svg>
                                </span>
                                    <span class="min-w-0 flex-1">
                                    <span class="ont-button-label block text-sm font-bold text-emerald-800">Aktifkan ONT</span>
                                    <span class="block text-[11px] text-emerald-700/70">Enable perangkat</span>
                                </span>
                                </button>
                            @endif

                            @if($controlFlag === 'active')
                                <button type="button" data-ont-action="deactivate"
                                        class="ont-control-button group flex items-center gap-3 rounded-xl border border-rose-200/80 bg-rose-50/40 p-4 text-left transition-all duration-200 hover:border-rose-400 hover:bg-rose-50 active:scale-[0.98] active:border-rose-500 active:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50">
                                <span class="ont-button-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 transition-all duration-200 group-hover:bg-rose-200 group-hover:text-rose-700">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v8"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.2 5.8a8 8 0 1 0 11.6 0"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4 4 16 16"/>
                                    </svg>
                                </span>
                                    <span class="min-w-0 flex-1">
                                    <span class="ont-button-label block text-sm font-bold text-rose-800">Nonaktifkan ONT</span>
                                    <span class="block text-[11px] text-rose-700/70">Disable perangkat</span>
                                </span>
                                </button>
                            @endif
                        </div>

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

        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-5">
            <h2 class="font-bold text-rose-700">Request gagal diproses</h2>
            <p class="mt-1 text-sm text-rose-600">
                {{ $displayData['message'] ?? $resultData['message'] ?? 'Terjadi kesalahan saat memproses request.' }}
            </p>
            <p class="mt-3 font-mono text-[11px] text-rose-500">{{ $request->uuid }}</p>
        </div>

    @else

        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 text-center">
            <p class="text-sm text-slate-500">Tidak ada data ONT yang dikembalikan.</p>
        </div>

    @endif

    {{-- ACTIONS --}}
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('dashboard') }}" class="jn-button px-5 py-2.5 text-sm shadow-sm">Dashboard</a>
        <a href="{{ url()->previous() }}"
           class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Kembali</a>
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
                        button.classList.add('text-emerald-600');

                        setTimeout(() => {
                            if (label) { label.textContent = original; } else { button.textContent = original; }
                            button.classList.remove('text-emerald-600');
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
                        box: 'bg-emerald-50', title: 'text-emerald-700', text: 'text-emerald-600',
                        icon: `<svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>`,
                    },
                    error: {
                        box: 'bg-rose-50', title: 'text-rose-700', text: 'text-rose-600',
                        icon: `<svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>`,
                    },
                    info: {
                        box: 'bg-cyan-50', title: 'text-cyan-700', text: 'text-cyan-600',
                        icon: `<svg class="h-4 w-4 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" d="M12 11v5m0-8h.01"/></svg>`,
                    },
                };

                const style = styles[type] || styles.info;

                messageBox.className = `border-t border-slate-100 px-5 py-4 sm:px-6 ${style.box}`;
                messageBox.classList.remove('hidden');

                messageTitle.className = `text-sm font-bold ${style.title}`;
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
