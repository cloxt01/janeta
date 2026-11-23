<x-layouts.dashboard title="Riwayat Lokasi">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">

    <style>
        .history-page { color: #0f172a; }
        .history-page .h-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; }
        .history-page .h-muted { color: #64748b; }
        .history-page .h-faint { color: #94a3b8; }
        .history-page .h-table { width: 100%; border-collapse: collapse; }
        .history-page .h-table thead { background: #f8fafc; }
        .history-page .h-table th {
            padding: 14px 18px; color: #64748b; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .04em; text-align: left; white-space: nowrap;
        }
        .history-page .h-table td {
            padding: 12px 18px; border-top: 1px solid #f1f5f9; color: #334155; font-size: 12px;
        }
        .history-page .h-table tbody tr { cursor: pointer; }
        .history-page .h-table tbody tr:hover { background: #f8fafc; }
        .history-page .h-input {
            padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px;
            font-size: 12px; color: #0f172a; background: #fff;
        }
        .history-page .h-btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;
            border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a;
            font-size: 12px; font-weight: 600; background: #fff; transition: .2s;
        }
        .history-page .h-btn:hover { background: #f1f5f9; border-color: #10b981; color: #047857; }
        .history-page .h-btn-primary { background: #059669; border-color: #059669; color: #fff; }
        .history-page .h-btn-primary:hover { background: #047857; border-color: #047857; color: #fff; }
        #historyMap { height: 420px; border-radius: 14px; z-index: 0; }
    </style>

    <div class="history-page space-y-6">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="text-emerald-600 text-xs font-semibold mb-2">LOCATION MONITORING</div>
                <h1 class="text-2xl font-bold">Riwayat Lokasi</h1>
                <p class="h-muted text-sm mt-1">
                    {{ $user->name }} &middot; {{ $user->email }}
                </p>
            </div>

            <a href="{{ route('location.live') }}" class="h-btn">&larr; Kembali ke Live</a>
        </div>

        {{-- FILTER --}}
        <form method="GET" class="h-card p-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs h-muted mb-1" for="from">Dari tanggal</label>
                <input type="date" id="from" name="from" value="{{ request('from') }}" class="h-input">
            </div>

            <div>
                <label class="block text-xs h-muted mb-1" for="to">Sampai tanggal</label>
                <input type="date" id="to" name="to" value="{{ request('to') }}" class="h-input">
            </div>

            <button type="submit" class="h-btn h-btn-primary">Terapkan</button>

            @if(request()->filled('from') || request()->filled('to'))
                <a href="{{ route('location.history', $user) }}" class="h-btn">Reset</a>
            @endif

            <div class="ml-auto text-xs h-muted">
                {{ $histories->total() }} titik tercatat
            </div>
        </form>

        {{-- PETA --}}
        <div class="h-card p-2">
            <div id="historyMap"></div>
            <p class="text-xs h-faint px-3 py-2">
                Peta menampilkan jalur untuk titik pada halaman ini (maks. 100 titik).
                Hijau = titik awal, merah = titik akhir.
            </p>
        </div>

        {{-- TABEL --}}
        <div class="h-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="h-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Waktu</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Akurasi</th>
                        <th>Peta</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($histories as $i => $h)
                        <tr data-index="{{ $i }}">
                            <td class="h-faint">{{ $histories->firstItem() + $i }}</td>
                            <td>{{ $h->recorded_at->timezone(config('app.timezone'))->format('d M Y H:i:s') }}</td>
                            <td class="font-mono">{{ number_format($h->latitude, 6) }}</td>
                            <td class="font-mono">{{ number_format($h->longitude, 6) }}</td>
                            <td>{{ $h->accuracy !== null ? round($h->accuracy) . ' m' : '-' }}</td>
                            <td>
                                <a href="https://www.google.com/maps?q={{ $h->latitude }},{{ $h->longitude }}"
                                   target="_blank" rel="noopener" class="text-emerald-600 hover:underline">
                                    Buka
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 h-muted">
                                Belum ada riwayat lokasi untuk pengguna ini.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($histories->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $histories->links() }}
                </div>
            @endif
        </div>
    </div>

    @php
        $mapPoints = $histories->getCollection()->map(function ($h) {
            return [
                'lat' => $h->latitude,
                'lng' => $h->longitude,
                'time' => $h->recorded_at->timezone(config('app.timezone'))->format('d M Y H:i:s'),
            ];
        })->values()->all();
    @endphp

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            const points = @json($mapPoints);

            const map = L.map('historyMap').setView([-2.5, 118], 5);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            const markers = [];

            if (points.length) {
                const latlngs = points.map(p => [p.lat, p.lng]);

                L.polyline(latlngs, {color: '#059669', weight: 4, opacity: .8}).addTo(map);

                points.forEach((p, i) => {
                    const first = i === 0;
                    const last = i === points.length - 1;

                    const m = L.circleMarker([p.lat, p.lng], {
                        radius: first || last ? 9 : 4,
                        color: '#fff',
                        weight: 2,
                        fillColor: first ? '#10b981' : last ? '#f43f5e' : '#0ea5e9',
                        fillOpacity: 1,
                    }).addTo(map);

                    m.bindTooltip(p.time, {direction: 'top'});
                    markers.push(m);
                });

                map.fitBounds(latlngs, {padding: [30, 30], maxZoom: 17});
            }

            // Klik baris tabel -> fokus ke titik di peta.
            document.querySelectorAll('tr[data-index]').forEach(row => {
                row.addEventListener('click', (e) => {
                    if (e.target.closest('a')) return;

                    const p = points[Number(row.dataset.index)];
                    if (!p) return;

                    map.setView([p.lat, p.lng], 18);
                    markers[Number(row.dataset.index)]?.openTooltip();
                });
            });
        </script>
    @endpush

</x-layouts.dashboard>
