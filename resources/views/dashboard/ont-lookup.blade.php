
<x-layouts.dashboard title="Cari ONT">

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

    {{-- SEARCH --}}
    <form id="ontSearchForm"
          data-api-url="{{ route('api.ont.lookup') }}"
          data-control-url="{{ route('api.ont.control') }}"
          data-processing-url="{{ route('portal.ont.processing', ['uuid' => '__UUID__']) }}"
          data-csrf="{{ csrf_token() }}"
          data-modem-url="{{ asset('images/modem') }}"
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

    {{-- ERROR --}}
    <div id="ontError"
         role="alert"
         class="hidden mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
    </div>

    {{-- RESULT --}}
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

        {{-- ONT CONTROL --}}
        <section id="ontActionSection"
                 class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">

            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-bold text-slate-800">Kontrol Perangkat ONT</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Restart, aktifkan, atau nonaktifkan ONT melalui antrean OLT.
                    </p>
                </div>

                <span id="ontControlFlag"
                      class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    Status administrasi: -
                </span>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button"
                        id="ontRestartButton"
                        data-action="restart"
                        class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">
                    ↻ Restart ONT
                </button>

                <button type="button"
                        id="ontActivateButton"
                        data-action="activate"
                        class="hidden rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Aktifkan ONT
                </button>

                <button type="button"
                        id="ontDeactivateButton"
                        data-action="deactivate"
                        class="hidden rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Nonaktifkan ONT
                </button>
            </div>
        </section>

        {{-- DETAIL --}}
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

                {{-- Gambar modem --}}
                <div id="modemImageWrap"
                     class="hidden mb-4 rounded-xl border border-slate-100 bg-slate-50/60 p-3">

                    <img id="modemImage"
                         src=""
                         alt="Gambar modem ONT"
                         class="mx-auto h-21 w-auto max-w-[100px] object-contain">
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

    {{-- EMPTY STATE --}}
    <div id="ontEmpty"
         class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">

        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.8"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <h3 class="font-bold text-slate-800">Cari Perangkat ONT</h3>

        <p class="mt-1 text-sm text-slate-500">
            Masukkan ID Customer untuk mengambil informasi perangkat dari OLT.
        </p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {

        const form = document.getElementById('ontSearchForm');
        const input = document.getElementById('customerId');
        const button = document.getElementById('searchButton');
        const buttonText = document.getElementById('searchButtonText');

        const errorBox = document.getElementById('ontError');
        const resultBox = document.getElementById('ontResult');
        const emptyBox = document.getElementById('ontEmpty');

        const modemWrap = document.getElementById('modemImageWrap');
        const modemImg = document.getElementById('modemImage');

        const actionSection = document.getElementById('ontActionSection');
        const actionFlag = document.getElementById('ontControlFlag');

        const actionButtons = [
            document.getElementById('ontRestartButton'),
            document.getElementById('ontActivateButton'),
            document.getElementById('ontDeactivateButton'),
        ];

        let currentOntData = null;
        let requestSequence = 0;

        /*
         * Helper: escape HTML.
         */
        const escapeHtml = (value) => String(value ?? '-').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));

        /*
         * Helper: set text.
         */
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
         * Reset tampilan.
         */
        function resetView() {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';

            resultBox.classList.add('hidden');
            emptyBox.classList.add('hidden');

            document.getElementById('versionWarning').classList.add('hidden');
            document.getElementById('wifiSection').classList.add('hidden');

            actionSection.classList.add('hidden');
            actionFlag.textContent = 'Status administrasi: -';

            currentOntData = null;

            actionButtons.forEach(button => {
                button.disabled = false;
                button.classList.remove('opacity-60');
            });

            hideModemImage();
        }

        /*
         * Tombol lookup.
         */
        function setSearchLoading(loading) {
            button.disabled = loading;
            buttonText.textContent = loading
                ? 'Mengirim request...'
                : 'Cari Perangkat';
        }

        /*
         * Error.
         */
        function showError(message) {
            errorBox.textContent =
                message || 'Terjadi kesalahan yang tidak diketahui.';

            errorBox.classList.remove('hidden');
        }

        /*
         * Redirect ke halaman processing universal.
         */
        function redirectToProcessing(requestId) {
            if (!requestId) {
                throw new Error('Requests ID tidak ditemukan.');
            }

            const template = form.dataset.processingUrl;

            if (!template) {
                throw new Error('URL halaman processing belum dikonfigurasi.');
            }

            const url = template.replace(
                '__UUID__',
                encodeURIComponent(String(requestId))
            );

            window.location.assign(url);
        }

        /*
         * Ambil request ID dari beberapa kemungkinan bentuk response.
         */
        function getRequestId(body) {
            return (
                body?.data?.request_id ??
                body?.request_id ??
                body?.data?.uuid ??
                body?.uuid ??
                null
            );
        }

        /*
         * Gambar modem.
         */
        function hideModemImage() {
            modemWrap.classList.add('hidden');

            modemImg.onload = null;
            modemImg.onerror = null;
            modemImg.removeAttribute('src');
        }

        function renderModemImage(type) {
            hideModemImage();

            const model = String(type ?? '').trim();

            if (!/^[A-Za-z0-9._-]{2,40}$/.test(model) || model === '-') {
                return;
            }

            const base = form.dataset.modemUrl;
            let triedFallback = false;

            modemImg.onload = () => {
                modemWrap.classList.remove('hidden');
                modemImg.alt = `Modem ${model}`;
            };

            modemImg.onerror = () => {
                if (!triedFallback) {
                    triedFallback = true;
                    modemImg.src = `${base}/default.png`;
                    return;
                }

                hideModemImage();
            };

            modemImg.src = `${base}/${encodeURIComponent(model)}.png`;
        }

        /*
         * Render hasil lookup.
         */
        function renderResult(data) {
            if (!data || typeof data !== 'object' || Array.isArray(data)) {
                throw new Error('Response OLT tidak memiliki data yang valid.');
            }

            resultBox.classList.remove('hidden');
            emptyBox.classList.add('hidden');

            const isOnline =
                String(data.runState || '').toLowerCase() === 'online';

            currentOntData = data;

            const controlFlag = String(data.controlFlag || '')
                .trim()
                .toLowerCase();

            const isDeactive = [
                'deactive',
                'deactivated',
                'inactive',
                'disabled'
            ].includes(controlFlag);

            const isActive = [
                'active',
                'activated',
                'enabled'
            ].includes(controlFlag);

            actionSection.classList.remove('hidden');

            actionFlag.textContent =
                `Status administrasi: ${data.controlFlag || 'Tidak tersedia'}`;

            document.getElementById('ontActivateButton')
                .classList.toggle('hidden', !isDeactive);

            document.getElementById('ontDeactivateButton')
                .classList.toggle('hidden', !isActive);

            setText('ontRunState', data.runState);

            setText(
                'ont-rxPower',
                data.rxPower != null ? `${data.rxPower} dBm` : '-'
            );

            setText(
                'ont-txPower',
                data.txPower != null ? `${data.txPower} dBm` : '-'
            );

            setText(
                'ont-oltRxPower',
                data.oltRxPower != null ? `${data.oltRxPower} dBm` : '-'
            );

            setText('ont-temp', data.temp);
            setText('ont-voltage', data.voltage);
            setText('ont-wanIp', data.wanIp);

            setText(
                'ont-lastInfo',
                isOnline ? data.lastUpTime : data.lastDownCause
            );

            setText('ont-olt', data.olt);

            [
                'idcust',
                'frame',
                'slot',
                'port',
                'ontId',
                'sn',
                'brand',
                'type',
                'wlanType'
            ].forEach(key => {
                setText(`ont-${key}`, data[key]);
            });

            document.getElementById('ontStatusDot').className =
                `h-3 w-3 rounded-full ${
                    isOnline
                        ? 'bg-emerald-500 animate-pulse'
                        : 'bg-rose-500'
                }`;

            document.getElementById('ontRunState').className =
                `text-xl font-bold capitalize ${
                    isOnline
                        ? 'text-emerald-600'
                        : 'text-rose-600'
                }`;

            const warning = document.getElementById('versionWarning');

            if (data.versionAvailable === false) {
                warning.textContent =
                    'Data spesifikasi terbatas: ' +
                    (data.versionReason || 'Informasi tidak tersedia.');

                warning.classList.remove('hidden');
            } else {
                warning.classList.add('hidden');
            }

            renderModemImage(data.type);
            renderWifi(data.wlan);
        }

        /*
         * Render WLAN.
         */
        function renderWifi(wlan) {
            const section = document.getElementById('wifiSection');
            const list = document.getElementById('wifiList');

            const ssids = Array.isArray(wlan?.ssids)
                ? wlan.ssids
                : [];

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

        /*
         * Disable tombol control.
         */
        function setActionButtonsDisabled(disabled) {
            actionButtons.forEach(button => {
                button.disabled = disabled;
            });
        }

        /*
         * Requests control ONT.
         */
        async function submitOntAction(action) {
            if (!currentOntData) {
                showError('Cari data ONT terlebih dahulu.');
                return;
            }

            const labels = {
                restart: 'RESTART',
                activate: 'AKTIFKAN',
                deactivate: 'NONAKTIFKAN',
            };

            const coordinate =
                `${currentOntData.olt || '-'} ` +
                `${currentOntData.frame}/${currentOntData.slot}/${currentOntData.port}:` +
                `${currentOntData.ontId}`;

            const confirmMessage = action === 'deactivate'
                ? `Yakin ingin NONAKTIFKAN ONT ${coordinate}?\nPelanggan akan kehilangan koneksi.`
                : `Yakin ingin ${labels[action]} ONT ${coordinate}?`;

            if (!window.confirm(confirmMessage)) {
                return;
            }

            const controlUrl = form.dataset.controlUrl;

            if (!controlUrl) {
                showError('URL API kontrol ONT belum dikonfigurasi.');
                return;
            }

            const payload = {
                action,
                olt: currentOntData.olt,
                frame: String(currentOntData.frame ?? ''),
                slot: String(currentOntData.slot ?? ''),
                port: String(currentOntData.port ?? ''),
                ont_id: String(currentOntData.ontId ?? ''),
            };

            if (
                !payload.olt ||
                !/^\d+$/.test(payload.frame) ||
                !/^\d+$/.test(payload.slot) ||
                !/^\d+$/.test(payload.port) ||
                !/^\d+$/.test(payload.ont_id)
            ) {
                showError('Koordinat ONT tidak lengkap atau tidak valid.');
                return;
            }

            setActionButtonsDisabled(true);

            try {
                const response = await fetch(controlUrl, {
                    method: 'POST',
                    credentials: 'same-origin',

                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': form.dataset.csrf || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },

                    body: JSON.stringify(payload),
                });

                const contentType =
                    response.headers.get('content-type') || '';

                if (!contentType.includes('application/json')) {
                    const raw = await response.text();

                    console.error(
                        '[JANETA] Control non-JSON response:',
                        raw.substring(0, 1000)
                    );

                    throw new Error(
                        `API kontrol tidak mengembalikan JSON (HTTP ${response.status}).`
                    );
                }

                const body = await response.json();

                if (!response.ok) {
                    const validationMessage = body.errors
                        ? Object.values(body.errors).flat().join(' ')
                        : '';

                    throw new Error(
                        validationMessage ||
                        body.message ||
                        `Kontrol ONT gagal dengan HTTP ${response.status}.`
                    );
                }

                // Requests diterima queue.
                if (response.status !== 202) {
                    throw new Error(
                        body.message ||
                        `Response API tidak sesuai. HTTP ${response.status}.`
                    );
                }

                const requestId = getRequestId(body);

                if (!requestId) {
                    throw new Error(
                        body.message ||
                        'Requests ID kontrol tidak ditemukan.'
                    );
                }

                // Semua proses dilanjutkan di halaman processing.
                redirectToProcessing(requestId);

            } catch (error) {
                console.error('[JANETA] ONT control error:', error);

                showError(
                    error.message || 'Gagal mengirim perintah ONT.'
                );

                setActionButtonsDisabled(false);
            }
        }

        /*
         * Event tombol control.
         */
        actionButtons.forEach(button => {
            button.addEventListener('click', () => {
                submitOntAction(button.dataset.action);
            });
        });

        /*
         * SUBMIT LOOKUP
         */
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const id = input.value.trim();

            if (!id) {
                input.focus();
                return;
            }

            const currentSequence = ++requestSequence;

            resetView();
            errorBox.classList.add('hidden');

            setSearchLoading(true);

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

                if (currentSequence !== requestSequence) {
                    return;
                }

                const contentType =
                    response.headers.get('content-type') || '';

                if (!contentType.includes('application/json')) {
                    const raw = await response.text();

                    console.error(
                        '[JANETA] Non-JSON response:',
                        raw.substring(0, 1000)
                    );

                    throw new Error(
                        `API tidak mengembalikan JSON. HTTP ${response.status}. Periksa autentikasi dan middleware.`
                    );
                }

                const body = await response.json();

                console.log('[JANETA] API response:', body);

                if (!response.ok) {
                    throw new Error(
                        body.message ||
                        `Request gagal dengan HTTP ${response.status}.`
                    );
                }

                // Lookup harus membuat queue request dan mengembalikan 202.
                if (response.status !== 202) {
                    throw new Error(
                        body.message ||
                        `Response lookup tidak sesuai. HTTP ${response.status}.`
                    );
                }

                const requestId = getRequestId(body);

                if (!requestId) {
                    throw new Error(
                        body.message ||
                        'Requests ID lookup tidak ditemukan.'
                    );
                }

                // Redirect ke processing universal.
                redirectToProcessing(requestId);

            } catch (error) {
                if (currentSequence !== requestSequence) {
                    return;
                }

                console.error('[JANETA] Lookup error:', error);

                let message = error.message;

                if (
                    error.name === 'TypeError' &&
                    message === 'Failed to fetch'
                ) {
                    message =
                        'Tidak dapat menghubungi API. Periksa koneksi server dan autentikasi.';
                }

                showError(message);
                setSearchLoading(false);

            }
        });

        /*
         * Auto lookup jika URL dibuka dengan ?id=...
         */
        if (input.value.trim()) {
            form.requestSubmit();
        }

    });
    </script>

</x-layouts.dashboard>
