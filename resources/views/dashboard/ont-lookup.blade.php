<x-layouts.dashboard title="Cari ONT">

    {{-- PAGE HEADER --}}
    <div class="mb-6">
        <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
            Workspace · Cari ONT
        </div>

        <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
            Pencarian <span class="text-[#8a8a8a]">ONT.</span>
        </h1>

        <p class="mt-2.5 text-xs text-[#a0a0a0]">
            Tarik status, daya optik, WAN, dan informasi perangkat langsung dari OLT
        </p>
    </div>

    {{-- SEARCH --}}
    <form id="ontSearchForm"
          data-api-url="{{ route('api.ont.lookup') }}"
          data-control-url="{{ route('api.ont.control') }}"
          data-processing-url="{{ route('ont.ont.processing', ['uuid' => '__UUID__']) }}"
          data-csrf="{{ csrf_token() }}"
          data-modem-url="{{ asset('images/modem') }}"
          class="mb-6 flex flex-col gap-2 sm:flex-row">

        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-[#777]">
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
                class="h-11 w-full rounded-full border border-[#3a3a3a] bg-[#1c1c1c] pl-11 pr-5 text-sm text-[#e5e5e5] outline-none transition placeholder:text-[#777] focus:border-[#5a5a5a]"
            >
        </div>

        <button id="searchButton" type="submit"
                class="h-11 rounded-full bg-[#0b8a3e] px-7 text-sm font-semibold text-white transition-colors hover:bg-[#0da048] disabled:cursor-not-allowed disabled:opacity-60">
            <span id="searchButtonText">Cari Perangkat</span>
        </button>
    </form>

    {{-- ERROR --}}
    <div id="ontError"
         role="alert"
         class="mb-6 hidden rounded-2xl border border-[#5e2b2b] bg-[#2a1717] px-5 py-3.5 text-sm text-[#f08a8a]">
    </div>

    {{-- RESULT --}}
    <div id="ontResult" class="hidden">

        {{-- Version warning --}}
        <div id="versionWarning"
             class="mb-6 hidden rounded-2xl border border-[#5a4a22] bg-[#2a2417] px-5 py-3.5 text-sm text-[#e0b155]">
        </div>

        {{-- METRICS --}}
        <div class="mb-5 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#2e2e2e] lg:grid-cols-4">

            <div class="bg-[#1c1c1c] px-5 py-4">
                <p class="text-[11px] text-[#8a8a8a]">Status ONT</p>

                <div class="mt-2 flex items-center gap-2.5">
                    <span id="ontStatusDot" class="h-2.5 w-2.5 rounded-full bg-[#666]"></span>
                    <span id="ontRunState" class="text-xl font-semibold capitalize tracking-tight text-[#f2f2f2]">-</span>
                </div>
            </div>

            @foreach([
                'rxPower' => 'RX Power (ONT)',
                'txPower' => 'TX Power (ONT)',
                'oltRxPower' => 'OLT RX Power',
                'temp' => 'Suhu perangkat',
                'voltage' => 'Voltase',
                'wanIp' => 'WAN IP',
                'lastInfo' => 'Informasi terakhir',
            ] as $key => $label)
                <div class="bg-[#1c1c1c] px-5 py-4">
                    <p class="text-[11px] text-[#8a8a8a]">{{ $label }}</p>

                    <p id="ont-{{ $key }}"
                       class="mt-2 truncate text-xl font-semibold tracking-tight text-[#f2f2f2]">-</p>
                </div>
            @endforeach
        </div>

        {{-- ONT CONTROL --}}
        <section id="ontActionSection"
                 class="mb-5 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2e2e2e] px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-[#f2f2f2]">Kontrol perangkat ONT</h2>
                    <p class="mt-0.5 text-[11px] text-[#8a8a8a]">
                        Restart, aktifkan, atau nonaktifkan ONT melalui antrean OLT
                    </p>
                </div>

                <span id="ontControlFlag"
                      class="w-fit rounded-full border border-[#3a3a3a] bg-[#242424] px-3 py-1 text-[11px] font-medium text-[#c4c4c4]">
                    Status administrasi: -
                </span>
            </div>

            <div class="flex flex-wrap gap-2 px-5 py-4">
                <button type="button"
                        id="ontRestartButton"
                        data-action="restart"
                        class="h-9 rounded-full border border-[#5a4a22] bg-[#2a2417] px-5 text-xs font-semibold text-[#e0b155] transition-colors hover:bg-[#352d1b] disabled:cursor-not-allowed disabled:opacity-50">
                    Restart ONT
                </button>

                <button type="button"
                        id="ontActivateButton"
                        data-action="activate"
                        class="hidden h-9 rounded-full bg-[#0b8a3e] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#0da048] disabled:cursor-not-allowed disabled:opacity-50">
                    Aktifkan ONT
                </button>

                <button type="button"
                        id="ontDeactivateButton"
                        data-action="deactivate"
                        class="hidden h-9 rounded-full border border-[#5e2b2b] bg-[#2a1717] px-5 text-xs font-semibold text-[#f08a8a] transition-colors hover:bg-[#361d1d] disabled:cursor-not-allowed disabled:opacity-50">
                    Nonaktifkan ONT
                </button>
            </div>
        </section>

        {{-- DETAIL --}}
        <div class="grid gap-5 md:grid-cols-2">

            <section class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">
                <div class="flex items-center justify-between border-b border-[#2e2e2e] px-5 py-4">
                    <h2 class="text-sm font-semibold text-[#f2f2f2]">Lokasi &amp; topologi</h2>

                    <span class="text-[11px] text-[#8a8a8a]">
                        OLT: <b id="ont-olt" class="font-semibold text-[#e5e5e5]">-</b>
                    </span>
                </div>

                <dl class="divide-y divide-[#272727] text-sm">
                    @foreach([
                        'idcust' => 'ID Customer',
                        'frame' => 'Frame',
                        'slot' => 'Slot',
                        'port' => 'Port',
                        'ontId' => 'ONT ID',
                    ] as $key => $label)
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-[#8a8a8a]">{{ $label }}</dt>
                            <dd id="ont-{{ $key }}" class="text-[13px] font-semibold text-[#f2f2f2]">-</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">
                <div class="flex items-center justify-between border-b border-[#2e2e2e] px-5 py-4">
                    <h2 class="text-sm font-semibold text-[#f2f2f2]">Spesifikasi perangkat</h2>
                    <span class="text-[11px] text-[#8a8a8a]">Hardware info</span>
                </div>

                {{-- Gambar modem --}}
                <div id="modemImageWrap"
                     class="mx-5 mt-4 hidden rounded-xl border border-[#2e2e2e] bg-[#242424] p-3">

                    <img id="modemImage"
                         src=""
                         alt="Gambar modem ONT"
                         class="mx-auto h-20 w-auto max-w-[100px] object-contain">
                </div>

                <dl class="mt-2 divide-y divide-[#272727] text-sm">
                    @foreach([
                        'sn' => 'Serial Number',
                        'brand' => 'Merek',
                        'type' => 'Tipe',
                        'wlanType' => 'WLAN Band',
                    ] as $key => $label)
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <dt class="text-xs text-[#8a8a8a]">{{ $label }}</dt>

                            <dd id="ont-{{ $key }}"
                                class="max-w-[240px] truncate text-right text-[13px] font-semibold text-[#f2f2f2]">-</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        {{-- WLAN --}}
        <section id="wifiSection"
                 class="mt-5 hidden overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

            <div class="flex items-center justify-between border-b border-[#2e2e2e] px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-[#f2f2f2]">Wi-Fi / WLAN ONT</h2>
                    <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Informasi SSID dari OLT</p>
                </div>

                <span id="wifiCount"
                      class="rounded-full border border-[#3a3a3a] bg-[#242424] px-3 py-1 text-[11px] font-medium text-[#c4c4c4]">
                    0 SSID
                </span>
            </div>

            <div id="wifiList" class="grid gap-3 p-5 md:grid-cols-2"></div>
        </section>
    </div>

    {{-- EMPTY STATE --}}
    <div id="ontEmpty"
         class="rounded-2xl border border-dashed border-[#3a3a3a] bg-[#1c1c1c] px-6 py-14 text-center">

        <div class="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-full border border-[#3a3a3a] bg-[#242424] text-[#777]">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.8"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <h3 class="text-sm font-medium text-[#d0d0d0]">Cari perangkat ONT</h3>

        <p class="mt-1 text-xs text-[#8a8a8a]">
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

                actionButtons.forEach(btn => {
                    btn.disabled = false;
                });

                hideModemImage();
            }

            function setSearchLoading(loading) {
                button.disabled = loading;
                buttonText.textContent = loading
                    ? 'Mengirim request...'
                    : 'Cari Perangkat';
            }

            function showError(message) {
                errorBox.textContent =
                    message || 'Terjadi kesalahan yang tidak diketahui.';

                errorBox.classList.remove('hidden');
            }

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

            function getRequestId(body) {
                return (
                    body?.data?.request_id ??
                    body?.request_id ??
                    body?.data?.uuid ??
                    body?.uuid ??
                    null
                );
            }

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

                setText('ont-rxPower', data.rxPower != null ? `${data.rxPower} dBm` : '-');
                setText('ont-txPower', data.txPower != null ? `${data.txPower} dBm` : '-');
                setText('ont-oltRxPower', data.oltRxPower != null ? `${data.oltRxPower} dBm` : '-');

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
                    `h-2.5 w-2.5 rounded-full ${
                        isOnline
                            ? 'bg-[#00a34a] animate-pulse'
                            : 'bg-red-500'
                    }`;

                document.getElementById('ontRunState').className =
                    `text-xl font-semibold capitalize tracking-tight ${
                        isOnline
                            ? 'text-[#4cd286]'
                            : 'text-[#f08a8a]'
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
                <div class="rounded-xl border border-[#2e2e2e] bg-[#202020] p-4">

                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-[#8a8a8a]">
                            SSID ${escapeHtml(ssid.index)}
                        </span>

                        <span class="rounded-full border border-[#1f5a38] bg-[#14281d] px-2.5 py-0.5 text-[11px] font-medium capitalize text-[#4cd286]">
                            ${escapeHtml(ssid.opState)}
                        </span>
                    </div>

                    <p class="mt-2 text-sm font-semibold text-[#f2f2f2]">
                        ${escapeHtml(ssid.name)}
                    </p>

                    <div class="mt-3 flex items-center justify-between border-t border-[#2e2e2e] pt-3 text-[11px] text-[#8a8a8a]">
                        <span>
                            Admin state:
                            <strong class="font-semibold text-[#d0d0d0]">${escapeHtml(ssid.adminState)}</strong>
                        </span>

                        <span>
                            Perangkat:
                            <strong class="font-semibold text-[#d0d0d0]">${escapeHtml(ssid.deviceCount)} / ${escapeHtml(ssid.deviceMax)}</strong>
                        </span>
                    </div>

                </div>
            `).join('');
            }

            function setActionButtonsDisabled(disabled) {
                actionButtons.forEach(btn => {
                    btn.disabled = disabled;
                });
            }

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

                    redirectToProcessing(requestId);

                } catch (error) {
                    console.error('[JANETA] ONT control error:', error);

                    showError(
                        error.message || 'Gagal mengirim perintah ONT.'
                    );

                    setActionButtonsDisabled(false);
                }
            }

            actionButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    submitOntAction(btn.dataset.action);
                });
            });

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

            if (input.value.trim()) {
                form.requestSubmit();
            }

        });
    </script>

</x-layouts.dashboard>
