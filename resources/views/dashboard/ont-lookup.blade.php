<x-layouts.dashboard title="Cari ONT">

    <style>
        /* ===== Pipeline minimalis: Anda -> JANETA -> OLT ===== */
        .jn-flow {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(100, 116, 139, 0.15);
        }

        .jn-node {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            width: 76px;
            flex: none;
        }

        /* Lingkaran kecil di atas garis */
        .jn-node-icon {
            position: relative;
            box-sizing: border-box;
            width: 14px;
            height: 14px;
            border-radius: 9999px;
            border: 2px solid #d0d7de;
            background: #fff;
            transition: background-color .25s, border-color .25s;
        }

        .jn-node-label {
            max-width: 76px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 11px;
            font-weight: 600;
            color: #8c959f;
            transition: color .25s;
        }

        /* Berjalan: ring berputar ala GitHub Actions */
        .jn-node[data-state="active"] .jn-node-icon {
            border-color: #d0d7de;
            border-top-color: #0891b2;
            animation: jn-spin .8s linear infinite;
        }
        .jn-node[data-state="active"] .jn-node-label,
        .jn-node[data-state="done"] .jn-node-label { color: #1f2328; }

        /* Selesai: lingkaran hijau + centang */
        .jn-node[data-state="done"] .jn-node-icon {
            border-color: #1a7f37;
            background: #1a7f37;
        }
        .jn-node[data-state="done"] .jn-node-icon::after {
            content: "";
            position: absolute;
            left: 3px;
            top: 0;
            width: 3px;
            height: 6px;
            border: solid #fff;
            border-width: 0 1.5px 1.5px 0;
            transform: rotate(45deg);
        }

        /* Gagal: lingkaran merah + strip */
        .jn-node[data-state="error"] .jn-node-icon {
            border-color: #cf222e;
            background: #cf222e;
        }
        .jn-node[data-state="error"] .jn-node-icon::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 50%;
            width: 6px;
            height: 1.5px;
            background: #fff;
            transform: translate(-50%, -50%);
        }
        .jn-node[data-state="error"] .jn-node-label { color: #cf222e; }

        /* Garis penghubung (sejajar tengah lingkaran) */
        .jn-link {
            flex: 1;
            min-width: 48px;
            max-width: 140px;
            height: 2px;
            margin-top: 6px;
            border-radius: 1px;
            background: #d0d7de;
            transition: background-color .25s;
        }

        .jn-link[data-state="active"] {
            background: linear-gradient(90deg, #d0d7de 0%, #0891b2 50%, #d0d7de 100%);
            background-size: 200% 100%;
            animation: jn-shimmer 1.2s linear infinite;
        }

        .jn-link[data-state="return"] {
            background: linear-gradient(90deg, #1a7f37 0%, #86d99b 50%, #1a7f37 100%);
            background-size: 200% 100%;
            animation: jn-shimmer-back 1.2s linear infinite;
        }

        .jn-link[data-state="done"]  { background: #1a7f37; }
        .jn-link[data-state="error"] { background: #cf222e; }

        @keyframes jn-spin { to { transform: rotate(360deg); } }
        @keyframes jn-shimmer { from { background-position: 100% 0; } to { background-position: -100% 0; } }
        @keyframes jn-shimmer-back { from { background-position: -100% 0; } to { background-position: 100% 0; } }

        @media (prefers-reduced-motion: reduce) {
            .jn-node[data-state="active"] .jn-node-icon,
            .jn-link[data-state="active"],
            .jn-link[data-state="return"] { animation: none; }
        }
    </style>

    <div class="mb-8">
        <span class="mb-2 inline-block rounded-md bg-cyan-50 px-2.5 py-1 text-xs font-bold uppercase tracking-widest text-cyan-600">
            Operasional Jaringan
        </span>

        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Pencarian & Detail ONT
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Tarik status, daya optik, WAN, dan informasi perangkat langsung dari OLT.
        </p>
    </div>

    {{-- Search (tanpa action/method: kalau script gagal, form tidak navigasi ke mana-mana) --}}
    <form id="ontSearchForm"
          data-api-url="{{ route('api.ont.lookup') }}"
          class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </span>

            <input
                id="customerId"
                name="id"
                value="{{ request('id') }}"
                placeholder="Masukkan ID Customer (contoh: 1JN2024...)"
                required
                autocomplete="off"
                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 pl-11 pr-4 text-sm font-medium text-slate-800 outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
            >
        </div>

        <button id="searchButton" type="submit" class="jn-button px-6 py-3 shadow-sm">
            <span id="searchButtonText">Cari Perangkat</span>
        </button>
    </form>

    {{-- Realtime status + pipeline animasi --}}
    <div id="ontStatus" class="hidden mb-6 rounded-xl border p-4 text-sm">
        <div class="flex items-center gap-3">
            <span id="statusIndicator" class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>

            <div class="min-w-0 flex-1">
                <p id="statusTitle" class="font-bold"></p>
                <p id="statusMessage" class="mt-0.5 break-words text-xs opacity-75"></p>
            </div>

            <span id="statusDuration" class="text-xs font-semibold"></span>
        </div>

        <div class="jn-flow" aria-hidden="true">
            <div id="flowUser" class="jn-node" data-state="idle">
                <span class="jn-node-icon"></span>
                <span class="jn-node-label">Anda</span>
            </div>

            <div id="flowLink1" class="jn-link" data-state="idle"></div>

            <div id="flowJaneta" class="jn-node" data-state="idle">
                <span class="jn-node-icon"></span>
                <span class="jn-node-label">JANETA</span>
            </div>

            <div id="flowLink2" class="jn-link" data-state="idle"></div>

            <div id="flowOlt" class="jn-node" data-state="idle">
                <span class="jn-node-icon"></span>
                <span id="flowOltLabel" class="jn-node-label">OLT</span>
            </div>
        </div>
    </div>

    {{-- Error --}}
    <div id="ontError"
         role="alert"
         class="hidden mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
    </div>

    {{-- Result --}}
    <div id="ontResult" class="hidden">

        {{-- Version warning --}}
        <div id="versionWarning"
             class="hidden mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        </div>

        {{-- Metrics --}}
        <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Status ONT
                </p>

                <div class="mt-3 flex items-center gap-2.5">
                    <span id="ontStatusDot" class="h-3 w-3 rounded-full bg-slate-400"></span>
                    <span id="ontRunState" class="text-xl font-bold text-slate-800">-</span>
                </div>
            </div>

            @foreach([
                'rxPower' => 'RX Power (ONT)',
                'txPower' => 'TX Power (ONT)',
                'oltRxPower' => 'OLT RX Power',
                'temp' => 'Suhu Perangkat',
                'voltage' => 'Voltase',
                'wanIp' => 'WAN IP',
                'lastInfo' => 'Informasi Terakhir',
            ] as $key => $label)
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        {{ $label }}
                    </p>

                    <p id="ont-{{ $key }}"
                       class="mt-3 truncate text-xl font-bold text-slate-800">-</p>
                </div>
            @endforeach
        </div>

        {{-- Detail --}}
        <div class="grid gap-6 md:grid-cols-2">

            <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-bold text-slate-800">Lokasi & Topologi</h2>

                    <span class="text-xs text-slate-400">
                        OLT: <b id="ont-olt" class="text-slate-700">-</b>
                    </span>
                </div>

                <dl class="space-y-3 text-sm">
                    @foreach([
                        'idcust' => 'ID Customer',
                        'frame' => 'Frame',
                        'slot' => 'Slot',
                        'port' => 'Port',
                        'ontId' => 'ONT ID',
                    ] as $key => $label)
                        <div class="flex items-center justify-between py-1">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd id="ont-{{ $key }}" class="font-semibold text-slate-800">-</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-bold text-slate-800">Spesifikasi Perangkat</h2>
                    <span class="text-xs text-slate-400">Hardware Info</span>
                </div>

                <dl class="space-y-3 text-sm">
                    @foreach([
                        'sn' => 'Serial Number',
                        'brand' => 'Merek',
                        'type' => 'Tipe',
                        'wlanType' => 'WLAN Band',
                    ] as $key => $label)
                        <div class="flex items-center justify-between gap-4 py-1">
                            <dt class="text-slate-500">{{ $label }}</dt>

                            <dd id="ont-{{ $key }}"
                                class="max-w-[240px] truncate text-right font-semibold text-slate-800">-</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        {{-- WLAN --}}
        <section id="wifiSection"
                 class="mt-6 hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">

            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Wi-Fi / WLAN ONT</h2>

                    <p class="mt-0.5 text-xs text-slate-400">
                        Informasi SSID dari OLT.
                    </p>
                </div>

                <span id="wifiCount"
                      class="rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600">
                    0 SSID
                </span>
            </div>

            <div id="wifiList" class="grid gap-4 md:grid-cols-2"></div>
        </section>
    </div>

    {{-- Empty state --}}
    <div id="ontEmpty"
         class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">

        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <h3 class="font-bold text-slate-800">Cari Perangkat ONT</h3>

        <p class="mt-1 text-sm text-slate-500">
            Masukkan ID Customer untuk mengambil informasi perangkat dari OLT.
        </p>
    </div>

    {{-- Script di DALAM komponen (tanpa @push) supaya pasti dirender --}}
    <script>
    console.log('[JANETA] ONT page script loaded');

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('ontSearchForm');
        const input = document.getElementById('customerId');
        const button = document.getElementById('searchButton');
        const buttonText = document.getElementById('searchButtonText');

        const statusBox = document.getElementById('ontStatus');
        const statusTitle = document.getElementById('statusTitle');
        const statusMessage = document.getElementById('statusMessage');
        const statusIndicator = document.getElementById('statusIndicator');
        const statusDuration = document.getElementById('statusDuration');

        const errorBox = document.getElementById('ontError');
        const resultBox = document.getElementById('ontResult');
        const emptyBox = document.getElementById('ontEmpty');

        const flow = {
            user: document.getElementById('flowUser'),
            link1: document.getElementById('flowLink1'),
            janeta: document.getElementById('flowJaneta'),
            link2: document.getElementById('flowLink2'),
            olt: document.getElementById('flowOlt'),
            oltLabel: document.getElementById('flowOltLabel'),
        };

        let activeRequestId = null;
        let startedAt = null;
        let durationTimer = null;
        let requestTimeout = null;
        let requestSequence = 0;
        let flowTimer = null;
        let channel = null;

        const pendingEvents = new Map();
        const MAX_PENDING_EVENTS = 30;

        const escapeHtml = (value) => String(value ?? '-').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));

        const setText = (id, value) => {
            const element = document.getElementById(id);

            if (element) {
                element.textContent =
                    value === null || value === undefined || value === ''
                        ? '-'
                        : String(value);
            }
        };

        /*
         * Animasi pipeline: User -> JANETA -> OLT
         * Urutan argumen: user, link1, janeta, link2, olt
         * state node : idle | active | done | error
         * state link : idle | active (titik maju) | return (titik balik) | done | error
         */
        function paintFlow(user, link1, janeta, link2, olt) {
            flow.user.dataset.state = user;
            flow.link1.dataset.state = link1;
            flow.janeta.dataset.state = janeta;
            flow.link2.dataset.state = link2;
            flow.olt.dataset.state = olt;
        }

        function setFlow(stage, failedAt = 'olt') {
            clearTimeout(flowTimer);

            if (stage === 'idle') {
                paintFlow('idle', 'idle', 'idle', 'idle', 'idle');
                return;
            }

            if (stage === 'queued') {
                // Anda -> JANETA
                paintFlow('done', 'active', 'active', 'idle', 'idle');
                return;
            }

            if (stage === 'completed') {
                // Data kembali OLT -> JANETA -> Anda, lalu diam hijau
                paintFlow('done', 'return', 'done', 'return', 'done');

                flowTimer = setTimeout(() => {
                    paintFlow('done', 'done', 'done', 'done', 'done');
                }, 2500);

                return;
            }

            if (stage === 'failed') {
                if (failedAt === 'janeta') {
                    paintFlow('done', 'error', 'error', 'idle', 'idle');
                } else {
                    paintFlow('done', 'done', 'done', 'error', 'error');
                }

                return;
            }

            // processing: JANETA -> OLT
            paintFlow('done', 'done', 'done', 'active', 'active');
        }

        function setStatus(status, message = '', failedAt = 'olt') {
            statusBox.classList.remove('hidden');

            const styles = {
                queued: {
                    title: 'Request masuk antrean',
                    indicator: 'bg-amber-500 animate-pulse',
                    box: 'border-amber-200 bg-amber-50 text-amber-800'
                },
                processing: {
                    title: 'Sedang mengambil data dari OLT',
                    indicator: 'bg-cyan-500 animate-pulse',
                    box: 'border-cyan-200 bg-cyan-50 text-cyan-800'
                },
                completed: {
                    title: 'Pencarian selesai',
                    indicator: 'bg-emerald-500',
                    box: 'border-emerald-200 bg-emerald-50 text-emerald-800'
                },
                failed: {
                    title: 'Pencarian gagal',
                    indicator: 'bg-rose-500',
                    box: 'border-rose-200 bg-rose-50 text-rose-800'
                }
            };

            const style = styles[status] || styles.processing;

            statusTitle.textContent = style.title;
            statusMessage.textContent = message;

            statusIndicator.className = `h-2.5 w-2.5 rounded-full ${style.indicator}`;
            statusBox.className = `mb-6 rounded-xl border p-4 text-sm ${style.box}`;

            setFlow(styles[status] ? status : 'processing', failedAt);
        }

        function resetView() {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';

            resultBox.classList.add('hidden');
            emptyBox.classList.add('hidden');

            document.getElementById('versionWarning').classList.add('hidden');
            document.getElementById('wifiSection').classList.add('hidden');

            flow.oltLabel.textContent = 'OLT';
            flow.oltLabel.removeAttribute('title');
        }

        function resetTimers() {
            if (durationTimer) {
                clearInterval(durationTimer);
                durationTimer = null;
            }

            if (requestTimeout) {
                clearTimeout(requestTimeout);
                requestTimeout = null;
            }
        }

        function finishRequest() {
            resetTimers();

            button.disabled = false;
            buttonText.textContent = 'Cari Perangkat';
        }

        function showError(message) {
            errorBox.textContent = message || 'Terjadi kesalahan yang tidak diketahui.';
            errorBox.classList.remove('hidden');
        }

        function renderResult(data) {
            if (!data || typeof data !== 'object' || Array.isArray(data)) {
                throw new Error('Response OLT tidak memiliki data yang valid.');
            }

            resultBox.classList.remove('hidden');
            emptyBox.classList.add('hidden');

            const isOnline = String(data.runState || '').toLowerCase() === 'online';

            setText('ontRunState', data.runState);

            setText('ont-rxPower', data.rxPower != null ? `${data.rxPower} dBm` : '-');
            setText('ont-txPower', data.txPower != null ? `${data.txPower} dBm` : '-');
            setText('ont-oltRxPower', data.oltRxPower != null ? `${data.oltRxPower} dBm` : '-');

            setText('ont-temp', data.temp);
            setText('ont-voltage', data.voltage);
            setText('ont-wanIp', data.wanIp);

            setText('ont-lastInfo', isOnline ? data.lastUpTime : data.lastDownCause);

            setText('ont-olt', data.olt);

            if (data.olt) {
                flow.oltLabel.textContent = String(data.olt);
                flow.oltLabel.title = String(data.olt);
            }

            [
                'idcust', 'frame', 'slot', 'port', 'ontId',
                'sn', 'brand', 'type', 'wlanType'
            ].forEach(key => setText(`ont-${key}`, data[key]));

            document.getElementById('ontStatusDot').className =
                `h-3 w-3 rounded-full ${isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'}`;

            document.getElementById('ontRunState').className =
                `text-xl font-bold capitalize ${isOnline ? 'text-emerald-600' : 'text-rose-600'}`;

            const warning = document.getElementById('versionWarning');

            if (data.versionAvailable === false) {
                warning.textContent =
                    'Data spesifikasi terbatas: ' +
                    (data.versionReason || 'Informasi tidak tersedia.');

                warning.classList.remove('hidden');
            } else {
                warning.classList.add('hidden');
            }

            renderWifi(data.wlan);
        }

        function renderWifi(wlan) {
            const section = document.getElementById('wifiSection');
            const list = document.getElementById('wifiList');

            const ssids = Array.isArray(wlan?.ssids) ? wlan.ssids : [];

            if (!ssids.length) {
                section.classList.add('hidden');
                list.innerHTML = '';
                setText('wifiCount', '0 SSID');
                return;
            }

            section.classList.remove('hidden');
            setText('wifiCount', `${ssids.length} SSID`);

            list.innerHTML = ssids.map(ssid => `
                <div class="rounded-xl border border-slate-200/70 bg-slate-50/50 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            SSID ${escapeHtml(ssid.index)}
                        </span>

                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase text-emerald-700">
                            ${escapeHtml(ssid.opState)}
                        </span>
                    </div>

                    <p class="mt-2 text-base font-bold text-slate-800">
                        ${escapeHtml(ssid.name)}
                    </p>

                    <div class="mt-3 flex items-center justify-between border-t border-slate-200/60 pt-3 text-xs text-slate-500">
                        <span>
                            Admin State:
                            <strong>${escapeHtml(ssid.adminState)}</strong>
                        </span>

                        <span>
                            Perangkat:
                            <strong>${escapeHtml(ssid.deviceCount)} / ${escapeHtml(ssid.deviceMax)}</strong>
                        </span>
                    </div>
                </div>
            `).join('');
        }

        function handleEvent(event) {
            if (!event || !activeRequestId) return;

            if (String(event.request_id) !== String(activeRequestId)) {
                return;
            }

            const status = event.status;

            console.log('[JANETA] Reverb event:', event);

            if (status === 'queued') {
                setStatus('queued', event.message || 'Menunggu worker tersedia...');
                return;
            }

            if (status === 'running' || status === 'processing') {
                setStatus('processing', event.message || 'Sedang menjalankan perintah SSH ke OLT...');
                return;
            }

            if (status === 'completed') {
                try {
                    const result =
                        event.result?.data ??
                        event.result?.result ??
                        event.result;

                    renderResult(result);

                    setStatus('completed', event.message || 'Data ONT berhasil diterima.');
                } catch (error) {
                    console.error('[JANETA] Render error:', error);

                    showError(error.message);
                    setStatus('failed', error.message, 'janeta');
                }

                finishRequest();
                return;
            }

            if (status === 'failed') {
                showError(event.error || event.message || 'Operasi OLT gagal.');

                setStatus('failed', event.message || 'Terjadi kesalahan saat mengakses OLT.', 'olt');

                finishRequest();
            }
        }

        /*
         * 1) PASANG LISTENER SUBMIT DULU.
         */
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const id = input.value.trim();

            if (!id) {
                input.focus();
                return;
            }

            if (!window.Echo || !channel) {
                showError('Koneksi Reverb belum tersedia. Silakan refresh halaman.');
                return;
            }

            const currentSequence = ++requestSequence;

            resetTimers();
            resetView();

            activeRequestId = null;
            pendingEvents.clear();

            startedAt = Date.now();

            button.disabled = true;
            buttonText.textContent = 'Memasukkan antrean...';

            statusDuration.textContent = '0s';

            setStatus('queued', 'Mengirim request ke JANETA...');

            durationTimer = setInterval(() => {
                if (startedAt) {
                    statusDuration.textContent =
                        `${Math.floor((Date.now() - startedAt) / 1000)}s`;
                }
            }, 1000);

            // Timeout hanya untuk request HTTP, bukan proses queue.
            requestTimeout = setTimeout(() => {
                if (currentSequence !== requestSequence) return;

                showError('API tidak merespons dalam 30 detik.');
                setStatus('failed', 'Waktu tunggu API habis.', 'janeta');

                finishRequest();
            }, 30000);

            try {
                const apiUrl = form.dataset.apiUrl;

                if (!apiUrl) {
                    throw new Error('URL API lookup belum dikonfigurasi.');
                }

                const url = new URL(apiUrl, window.location.origin);

                url.searchParams.set('id', id);
                url.searchParams.set('wifi', '1');

                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (currentSequence !== requestSequence) return;

                const contentType = response.headers.get('content-type') || '';

                if (!contentType.includes('application/json')) {
                    const raw = await response.text();

                    console.error('[JANETA] Non-JSON response:', raw.substring(0, 1000));

                    throw new Error(
                        `API tidak mengembalikan JSON. HTTP ${response.status}. ` +
                        'Periksa autentikasi dan middleware.'
                    );
                }

                const body = await response.json();

                console.log('[JANETA] API response:', body);

                if (!response.ok) {
                    throw new Error(
                        body.message || `Request gagal dengan HTTP ${response.status}.`
                    );
                }

                const responseData = body.data ?? body;

                if (!responseData.request_id) {
                    throw new Error(
                        body.message || 'Request ID tidak ditemukan dalam response API.'
                    );
                }

                activeRequestId = String(responseData.request_id);

                // Respons 202 tampil di halaman yang sama.
                setStatus(
                    'queued',
                    `${body.message || 'Request masuk antrean.'} ` +
                    `(Request ID: ${activeRequestId}, operasi: ${responseData.operation || '-'})`
                );

                // Proses event yang tiba sebelum response HTTP diterima.
                if (pendingEvents.has(activeRequestId)) {
                    const earlyEvent = pendingEvents.get(activeRequestId);

                    pendingEvents.delete(activeRequestId);

                    handleEvent(earlyEvent);
                }

                // Jangan timpa label tombol kalau event early sudah menyelesaikan request.
                if (!button.disabled) return;

                buttonText.textContent = 'Sedang diproses...';

                if (requestTimeout) {
                    clearTimeout(requestTimeout);
                    requestTimeout = null;
                }

            } catch (error) {
                if (currentSequence !== requestSequence) return;

                console.error('[JANETA] Lookup error:', error);

                let message = error.message;

                if (error.name === 'TypeError' && message === 'Failed to fetch') {
                    message = 'Tidak dapat menghubungi API. Periksa koneksi server dan autentikasi.';
                }

                showError(message);
                setStatus('failed', message, 'janeta');

                finishRequest();
            }
        });

        /*
         * 2) BARU SUBSCRIBE REVERB, dibungkus try/catch.
         */
        try {
            channel = window.Echo
                ? window.Echo.private('users.{{ auth()->id() }}')
                : null;

            if (!channel) {
                console.warn('[JANETA] Laravel Echo belum tersedia.');
            } else {
                channel.listen('.OltServiceRequestUpdated', event => {
                    if (!event?.request_id) return;

                    const eventId = String(event.request_id);

                    if (!activeRequestId) {
                        pendingEvents.set(eventId, event);

                        if (pendingEvents.size > MAX_PENDING_EVENTS) {
                            pendingEvents.delete(pendingEvents.keys().next().value);
                        }

                        return;
                    }

                    handleEvent(event);
                });
            }
        } catch (error) {
            console.error('[JANETA] Gagal subscribe Echo:', error);
        }

        /*
         * 3) Terakhir: jika halaman dibuka dengan ?id=..., otomatis jalankan lookup.
         */
        if (input.value.trim()) {
            form.requestSubmit();
        }
    });
    </script>
</x-layouts.dashboard>