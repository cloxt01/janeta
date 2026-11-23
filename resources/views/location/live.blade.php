<x-layouts.dashboard title="Live Location Tracking">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">

    <style>
        .location-page { color: #0f172a; }
        .location-page .location-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; }
        .location-page .location-muted { color: #64748b; }
        .location-page .location-faint { color: #94a3b8; }
        .location-page .location-table { width: 100%; border-collapse: collapse; }
        .location-page .location-table thead { background: #f8fafc; }
        .location-page .location-table th {
            padding: 14px 18px; color: #64748b; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .04em; text-align: left; white-space: nowrap;
        }
        .location-page .location-table td {
            padding: 14px 18px; border-top: 1px solid #f1f5f9; color: #334155; font-size: 12px;
        }
        .location-page .location-table tbody tr { cursor: pointer; }
        .location-page .location-table tbody tr:hover { background: #f8fafc; }
        .location-page .location-badge {
            display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px;
            border-radius: 20px; font-size: 10px; font-weight: 700; white-space: nowrap;
        }
        .location-page .badge-online { color: #047857; background: #d1fae5; }
        .location-page .badge-offline { color: #64748b; background: #f1f5f9; }
        .location-page .dot { width: 7px; height: 7px; border-radius: 50%; }
        .location-page .location-action {
            display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px;
            border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a;
            font-size: 11px; font-weight: 600; transition: .2s;
        }
        .location-page .location-action:hover { background: #f1f5f9; border-color: #10b981; color: #047857; }
        #map { height: 420px; border-radius: 14px; z-index: 0; }
    </style>

    <div class="location-page space-y-6">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="text-emerald-600 text-xs font-semibold mb-2">LOCATION MONITORING</div>
                <h1 class="text-2xl font-bold">Live Location Tracking</h1>
                <p class="location-muted text-sm mt-1">Pantau lokasi pengguna JANETA secara langsung.</p>
            </div>

            <div class="flex items-center gap-2 text-xs location-muted">
                <span id="connDot" class="dot" style="background:#94a3b8"></span>
                <span id="connText">Menghubungkan...</span>
            </div>
        </div>

        {{-- STATISTIK --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="location-card p-5">
                <span class="text-xs location-muted">Total Pengguna</span>
                <div class="text-3xl font-bold mt-3">{{ $totalUsers }}</div>
                <p class="text-xs location-faint mt-1">Pengguna terdaftar</p>
            </div>

            <div class="location-card p-5">
                <span class="text-xs location-muted">Online</span>
                <div id="onlineUsers" class="text-3xl font-bold mt-3 text-emerald-600">0</div>
                <p class="text-xs location-faint mt-1">Update dalam 2 menit terakhir</p>
            </div>

            <div class="location-card p-5">
                <span class="text-xs location-muted">Offline</span>
                <div id="offlineUsers" class="text-3xl font-bold mt-3">0</div>
                <p class="text-xs location-faint mt-1">Belum mengirim lokasi terbaru</p>
            </div>
        </div>

        {{-- PETA --}}
        <div class="location-card p-2">
            <div id="map"></div>
        </div>

        {{-- TABEL --}}
        <div class="location-card overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Daftar Lokasi Pengguna</h2>
                    <p class="text-xs location-muted mt-1">Klik baris untuk memusatkan peta ke pengguna.</p>
                </div>
                <span id="lastEvent" class="text-xs location-muted">Menunggu data...</span>
            </div>

            <div class="overflow-x-auto">
                <table class="location-table">
                    <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Akurasi</th>
                        <th>Status</th>
                        <th>Terakhir Update</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody id="locationTable">
                    <tr>
                        <td colspan="7" class="text-center py-10 location-muted">Memuat data lokasi...</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            const DATA_URL = @json(route('location.live.data'));
            const HISTORY_URL = @json(route('location.history', ['user' => '__ID__']));
            const ONLINE_WINDOW_MS = 2 * 60 * 1000;

            const locations = new Map();   // user_id -> data lokasi
            const markers = new Map();     // user_id -> marker Leaflet
            let clockOffset = 0;           // server - browser (ms)

            const table = document.getElementById('locationTable');

            // ---------- Peta ----------
            const map = L.map('map').setView([-2.5, 118], 5); // tengah Indonesia
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            let firstFit = true;

            // ---------- Util ----------
            function esc(v) {
                return String(v ?? '').replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                })[c]);
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
                const id = String(userId);
                locations.delete(id);

                const m = markers.get(id);
                if (m) {
                    map.removeLayer(m);
                    markers.delete(id);
                }
            }

            // ---------- Render ----------
            function renderMarkers() {
                locations.forEach((u, id) => {
                    const online = isOnline(u);
                    const style = {
                        radius: 9,
                        color: '#fff',
                        weight: 2,
                        fillColor: online ? '#10b981' : '#94a3b8',
                        fillOpacity: 1,
                    };

                    let m = markers.get(id);

                    if (!m) {
                        m = L.circleMarker([u.latitude, u.longitude], style).addTo(map);
                        m.bindTooltip(esc(u.name), {direction: 'top'});
                        markers.set(id, m);

                        if (firstFit) {
                            map.setView([u.latitude, u.longitude], 15);
                            firstFit = false;
                        }
                    } else {
                        m.setLatLng([u.latitude, u.longitude]);
                        m.setStyle(style);
                        m.setTooltipContent(esc(u.name));
                    }
                });
            }

            function renderTable() {
                const list = Array.from(locations.values())
                    .sort((a, b) => new Date(b.tracked_at) - new Date(a.tracked_at));

                const online = list.filter(isOnline).length;
                document.getElementById('onlineUsers').textContent = online;
                document.getElementById('offlineUsers').textContent = list.length - online;

                if (!list.length) {
                    table.innerHTML = `
                        <tr><td colspan="7" class="text-center py-12 location-muted">
                            Belum ada pengguna yang mengaktifkan tracking.
                        </td></tr>`;
                    return;
                }

                table.innerHTML = list.map(u => {
                    const on = isOnline(u);
                    const histUrl = HISTORY_URL.replace('__ID__', encodeURIComponent(u.user_id));

                    return `
                        <tr data-id="${esc(u.user_id)}">
                            <td>
                                <div class="font-semibold">${esc(u.name)}</div>
                                <div class="text-xs location-faint mt-1">${esc(u.email)}</div>
                            </td>
                            <td class="font-mono">${esc(Number(u.latitude).toFixed(6))}</td>
                            <td class="font-mono">${esc(Number(u.longitude).toFixed(6))}</td>
                            <td>${u.accuracy != null ? esc(Math.round(u.accuracy)) + ' m' : '-'}</td>
                            <td>
                                <span class="location-badge ${on ? 'badge-online' : 'badge-offline'}">
                                    <span class="dot" style="background:${on ? '#10b981' : '#94a3b8'}"></span>
                                    ${on ? 'ONLINE' : 'OFFLINE'}
                                </span>
                            </td>
                            <td>${esc(fmtTime(u.tracked_at))}</td>
                            <td>
                                <a href="${histUrl}" class="location-action">Riwayat</a>
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
                if (u) map.setView([u.latitude, u.longitude], 17);
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
                        <tr><td colspan="7" class="text-center py-10 text-rose-600">${esc(err.message)}</td></tr>`;
                }
            }

            // ---------- Reverb ----------
            function setConn(ok, text) {
                document.getElementById('connDot').style.background = ok ? '#10b981' : '#f43f5e';
                document.getElementById('connText').textContent = text;
            }

            function listen() {
                if (!window.Echo) {
                    setConn(false, 'Echo belum diinisialisasi');
                    return;
                }

                // Status koneksi WebSocket
                const conn = window.Echo.connector?.pusher?.connection;
                if (conn) {
                    conn.bind('connected', () => setConn(true, 'Realtime terhubung'));
                    conn.bind('disconnected', () => setConn(false, 'Terputus, mencoba lagi...'));
                    conn.bind('error', () => setConn(false, 'Koneksi bermasalah'));
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
