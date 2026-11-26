<x-layouts.dashboard title="Live Location Tracking">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/maplibre-gl@4.7.1/dist/maplibre-gl.css">

    <style>
        /* Peta */
        #map { height: 440px; background: #1a1a1a; }

        /* Kontrol, atribusi, dan popup MapLibre versi gelap */
        .maplibregl-ctrl-group { background: #1c1c1c !important; border: 1px solid #3a3a3a; box-shadow: none !important; border-radius: 10px !important; overflow: hidden; }
        .maplibregl-ctrl-group button { border-color: #2e2e2e !important; }
        .maplibregl-ctrl-group button:hover { background: #2a2a2a !important; }
        .maplibregl-ctrl-group button .maplibregl-ctrl-icon { filter: invert(1) brightness(.85); }
        .maplibregl-ctrl-attrib { background: rgba(28,28,28,.8) !important; color: #8a8a8a !important; font-size: 10px; }
        .maplibregl-ctrl-attrib a { color: #a0a0a0 !important; }
        .maplibregl-ctrl-attrib-button { filter: invert(1); }
        .maplibregl-popup-content { background: #111; color: #f2f2f2; border: 1px solid #3a3a3a; border-radius: 8px; box-shadow: 0 6px 18px rgb(0 0 0 / 50%); font-size: 12px; font-weight: 600; padding: 5px 10px; }
        .maplibregl-popup-anchor-bottom .maplibregl-popup-tip { border-top-color: #3a3a3a; }
        .maplibregl-popup-anchor-top .maplibregl-popup-tip { border-bottom-color: #3a3a3a; }
    </style>

    <div class="location-page">

        {{-- PAGE HEADER --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
                    Network · Live location
                </div>
                <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
                    Live <span class="text-[#8a8a8a]">location.</span>
                </h1>
                <p class="mt-2.5 text-xs text-[#a0a0a0]">
                    Pantau lokasi pengguna JANETA secara langsung
                </p>
            </div>

            <span class="inline-flex items-center gap-2 rounded-full border border-[#3a3a3a] px-3.5 py-1.5 text-xs text-[#d0d0d0]">
                <span id="connDot" class="h-2 w-2 rounded-full" style="background:#666"></span>
                <span id="connText">Menghubungkan...</span>
            </span>
        </div>

        {{-- STAT STRIP --}}
        <div class="mb-5 grid grid-cols-1 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] sm:grid-cols-3">
            <div class="border-b border-[#2e2e2e] px-5 py-4 sm:border-b-0 sm:border-r">
                <div class="text-[11px] text-[#8a8a8a]">Total pengguna</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $totalUsers }}</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">pengguna terdaftar</div>
            </div>

            <div class="border-b border-[#2e2e2e] px-5 py-4 sm:border-b-0 sm:border-r">
                <div class="text-[11px] text-[#8a8a8a]">Online</div>
                <div id="onlineUsers" class="mt-2 text-2xl font-semibold tracking-tight text-[#4cd286]">0</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">update dalam 2 menit terakhir</div>
            </div>

            <div class="px-5 py-4">
                <div class="text-[11px] text-[#8a8a8a]">Offline</div>
                <div id="offlineUsers" class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">0</div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">belum mengirim lokasi terbaru</div>
            </div>
        </div>

        {{-- PETA --}}
        <div class="mb-5 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">
            <div id="map"></div>
        </div>

        {{-- TABEL --}}
        <div class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2e2e2e] px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-[#f2f2f2]">Daftar lokasi pengguna</h2>
                    <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Klik baris untuk memusatkan peta ke pengguna</p>
                </div>

                <span id="lastEvent" class="text-[11px] text-[#8a8a8a]">Menunggu data...</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-[#2e2e2e] text-[10px] font-semibold uppercase tracking-[0.1em] text-[#8a8a8a]">
                    <tr>
                        <th class="px-5 py-3.5">Pengguna</th>
                        <th class="px-5 py-3.5">Latitude</th>
                        <th class="px-5 py-3.5">Longitude</th>
                        <th class="px-5 py-3.5">Akurasi</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Terakhir update</th>
                        <th class="px-5 py-3.5">Aksi</th>
                    </tr>
                    </thead>

                    <tbody id="locationTable" class="divide-y divide-[#272727]">
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-xs text-[#8a8a8a]">Memuat data lokasi...</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
        <script>
            const DATA_URL = @json(route('location.live.data'));
            const HISTORY_URL = @json(route('location.history', ['user' => '__ID__']));
            const ONLINE_WINDOW_MS = 2 * 60 * 1000;

            // Ganti URL ini bila ingin memakai penyedia tile lain (MapTiler, Stadia, OpenFreeMap, dll).
            const MAP_STYLE_URL = 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json';

            const locations = new Map();   // user_id -> data lokasi
            let clockOffset = 0;           // server - browser (ms)
            let mapReady = false;
            let firstFit = true;

            const table = document.getElementById('locationTable');

            // ---------- Peta (MapLibre GL) ----------
            const map = new maplibregl.Map({
                container: 'map',
                style: MAP_STYLE_URL,
                center: [118, -2.5],   // [lng, lat] tengah Indonesia
                zoom: 3.6,
                attributionControl: {compact: true},
            });

            map.addControl(new maplibregl.NavigationControl({showCompass: false}), 'top-right');

            const hoverPopup = new maplibregl.Popup({
                closeButton: false,
                closeOnClick: false,
                offset: 14,
            });

            map.on('load', () => {
                map.addSource('users', {
                    type: 'geojson',
                    data: {type: 'FeatureCollection', features: []},
                });

                // Lingkaran halo untuk pengguna online
                map.addLayer({
                    id: 'users-halo',
                    type: 'circle',
                    source: 'users',
                    filter: ['==', ['get', 'online'], true],
                    paint: {
                        'circle-radius': 18,
                        'circle-color': '#00a34a',
                        'circle-opacity': 0.18,
                    },
                });

                map.addLayer({
                    id: 'users-dot',
                    type: 'circle',
                    source: 'users',
                    paint: {
                        'circle-radius': 8,
                        'circle-color': ['case', ['get', 'online'], '#00a34a', '#6b7078'],
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#ffffff',
                    },
                });

                map.on('mouseenter', 'users-dot', (e) => {
                    map.getCanvas().style.cursor = 'pointer';
                    const f = e.features && e.features[0];
                    if (!f) return;
                    hoverPopup
                        .setLngLat(f.geometry.coordinates)
                        .setText(f.properties.name || '-')
                        .addTo(map);
                });

                map.on('mouseleave', 'users-dot', () => {
                    map.getCanvas().style.cursor = '';
                    hoverPopup.remove();
                });

                mapReady = true;
                render();
            });

            // ---------- Util ----------
            function esc(v) {
                return String(v ?? '').replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                })[c]);
            }

            function initials(name) {
                const clean = String(name ?? '').replace(/[^A-Za-z0-9]/g, '');
                return clean.slice(0, 2).toUpperCase() || '--';
            }

            // Pakai jam server (bukan jam browser) supaya status akurat.
            function serverNow() {
                return Date.now() + clockOffset;
            }

            function isOnline(u) {
                const t = new Date(u.tracked_at).getTime();
                return Number.isFinite(t) && serverNow() - t < ONLINE_WINDOW_MS;
            }

            function fmtTime(iso) {
                const d = new Date(iso);
                return Number.isFinite(d.getTime()) ? d.toLocaleString('id-ID') : '-';
            }

            function validCoords(u) {
                const lat = Number(u.latitude);
                const lng = Number(u.longitude);
                return Number.isFinite(lat) && Number.isFinite(lng);
            }

            // ---------- State ----------
            function upsert(u) {
                if (!u || u.user_id == null) return;

                const id = String(u.user_id);
                const old = locations.get(id);

                // Jangan biarkan data lama menimpa data yang lebih baru.
                if (old && new Date(u.tracked_at) < new Date(old.tracked_at)) return;

                locations.set(id, {...old, ...u});
            }

            function remove(userId) {
                locations.delete(String(userId));
            }

            // ---------- Render ----------
            function renderMarkers() {
                if (!mapReady) return;

                const features = [];

                locations.forEach((u, id) => {
                    if (!validCoords(u)) return;

                    features.push({
                        type: 'Feature',
                        geometry: {
                            type: 'Point',
                            coordinates: [Number(u.longitude), Number(u.latitude)],
                        },
                        properties: {
                            id,
                            name: u.name ?? '-',
                            online: isOnline(u),
                        },
                    });
                });

                map.getSource('users').setData({type: 'FeatureCollection', features});

                if (firstFit && features.length) {
                    map.jumpTo({center: features[0].geometry.coordinates, zoom: 15});
                    firstFit = false;
                }
            }

            function renderTable() {
                const list = Array.from(locations.values())
                    .sort((a, b) => new Date(b.tracked_at) - new Date(a.tracked_at));

                const online = list.filter(isOnline).length;
                document.getElementById('onlineUsers').textContent = online;
                document.getElementById('offlineUsers').textContent = list.length - online;

                if (!list.length) {
                    table.innerHTML = `
                        <tr><td colspan="7" class="px-5 py-14 text-center">
                            <div class="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-full border border-[#3a3a3a] bg-[#242424] text-[#777]">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5" stroke-width="1.8"/></svg>
                            </div>
                            <p class="text-sm font-medium text-[#d0d0d0]">Belum ada lokasi</p>
                            <p class="mt-1 text-xs text-[#8a8a8a]">Belum ada pengguna yang mengaktifkan tracking.</p>
                        </td></tr>`;
                    return;
                }

                table.innerHTML = list.map(u => {
                    const on = isOnline(u);
                    const histUrl = HISTORY_URL.replace('__ID__', encodeURIComponent(u.user_id));

                    const badge = on
                        ? 'border-[#1f5a38] bg-[#14281d] text-[#4cd286]'
                        : 'border-[#3a3a3a] bg-[#242424] text-[#8a8a8a]';

                    return `
                        <tr data-id="${esc(u.user_id)}" class="cursor-pointer transition-colors hover:bg-[#232323]">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#2c2c2c] text-[10px] font-semibold text-[#bbb]">${esc(initials(u.name))}</span>
                                    <div class="min-w-0">
                                        <div class="text-[13px] font-semibold text-[#f2f2f2]">${esc(u.name)}</div>
                                        <div class="mt-0.5 text-[11px] text-[#8a8a8a]">${esc(u.email)}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-[11px] text-[#c4c4c4]">${esc(Number(u.latitude).toFixed(6))}</td>
                            <td class="px-5 py-3.5 font-mono text-[11px] text-[#c4c4c4]">${esc(Number(u.longitude).toFixed(6))}</td>
                            <td class="px-5 py-3.5 text-xs text-[#c4c4c4]">${u.accuracy != null ? esc(Math.round(u.accuracy)) + ' m' : '-'}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-semibold ${badge}">
                                    <span class="h-1.5 w-1.5 rounded-full" style="background:${on ? '#00a34a' : '#6b7078'}"></span>
                                    ${on ? 'ONLINE' : 'OFFLINE'}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-xs text-[#8a8a8a]">${esc(fmtTime(u.tracked_at))}</td>
                            <td class="px-5 py-3.5">
                                <a href="${histUrl}" class="inline-flex h-8 items-center rounded-full border border-[#3a3a3a] px-4 text-[11px] font-semibold text-[#d0d0d0] transition-colors hover:bg-[#2a2a2a] hover:text-white">Riwayat</a>
                            </td>
                        </tr>`;
                }).join('');
            }

            function render() {
                renderMarkers();
                renderTable();
            }

            // Klik baris -> fokus peta (kecuali klik tombol Riwayat).
            table.addEventListener('click', (e) => {
                if (e.target.closest('a')) return;

                const row = e.target.closest('tr[data-id]');
                const u = row && locations.get(row.dataset.id);
                if (u && validCoords(u)) {
                    map.flyTo({center: [Number(u.longitude), Number(u.latitude)], zoom: 17, essential: true});
                }
            });

            // ---------- Snapshot awal (SEKALI) ----------
            async function loadSnapshot() {
                try {
                    const res = await fetch(DATA_URL, {
                        headers: {Accept: 'application/json'},
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('Gagal mengambil data lokasi.');

                    const json = await res.json();

                    if (json.server_time) {
                        clockOffset = new Date(json.server_time).getTime() - Date.now();
                    }

                    (json.data ?? []).forEach(upsert);
                    render();

                    document.getElementById('lastEvent').textContent =
                        'Snapshot: ' + new Date().toLocaleTimeString('id-ID');
                } catch (err) {
                    console.error(err);
                    table.innerHTML = `
                        <tr><td colspan="7" class="px-5 py-12 text-center text-xs text-[#f08a8a]">${esc(err.message)}</td></tr>`;
                }
            }

            // ---------- Reverb ----------
            function setConn(ok, text) {
                document.getElementById('connDot').style.background = ok ? '#00a34a' : '#ef4444';
                document.getElementById('connText').textContent = text;
            }

            function listen() {
                if (!window.Echo) {
                    setConn(false, 'Echo belum diinisialisasi');
                    return;
                }

                // Status koneksi WebSocket: baca state saat ini lalu ikuti perubahannya.
                const conn = window.Echo.connector?.pusher?.connection;
                if (conn) {
                    const sync = () => {
                        const s = conn.state;
                        if (s === 'connected') setConn(true, 'Realtime terhubung');
                        else if (s === 'connecting' || s === 'initialized') setConn(false, 'Menghubungkan...');
                        else if (s === 'unavailable') setConn(false, 'Terputus, mencoba lagi...');
                        else setConn(false, 'Koneksi bermasalah');
                    };

                    ['state_change', 'connected', 'disconnected', 'unavailable', 'failed', 'error']
                        .forEach(evt => conn.bind(evt, sync));

                    sync();
                }

                window.Echo.private('location-monitoring')
                    .listen('.location.updated', (data) => {
                        upsert(data);
                        render();
                        document.getElementById('lastEvent').textContent =
                            'Live: ' + new Date().toLocaleTimeString('id-ID');
                    })
                    .listen('.location.stopped', (data) => {
                        remove(data.user_id);
                        render();
                    });
            }

            listen();
            loadSnapshot();

            // Hanya hitung ulang status ONLINE/OFFLINE di browser (tanpa request ke server).
            setInterval(render, 15000);
        </script>
    @endpush

</x-layouts.dashboard>
