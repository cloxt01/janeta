<x-layouts.dashboard title="OLT Activity">

    <style>
        .oa-badge{display:inline-flex;align-items:center;gap:6px;border:1px solid #3a3a3a;border-radius:999px;background:#242424;padding:3px 10px;color:#aaa;font-size:11px;font-weight:500;text-transform:capitalize}
        .oa-dot{display:inline-block;width:6px;height:6px;border-radius:50%;background:#666}
        .oa-badge.pending .oa-dot{background:#8a8a8a}
        .oa-badge.running{border-color:#5a4a22;background:#2a2417;color:#e0b155}
        .oa-badge.running .oa-dot{background:#e0b155;animation:oa-pulse 1.2s infinite}
        .oa-badge.completed{border-color:#1f5a38;background:#14281d;color:#4cd286}
        .oa-badge.completed .oa-dot{background:#00a34a}
        .oa-badge.failed{border-color:#5e2b2b;background:#2a1717;color:#f08a8a}
        .oa-badge.failed .oa-dot{background:#ef4444}
        .oa-source{display:inline-flex;border-radius:999px;background:#2c2c2c;padding:2px 9px;color:#bbb;font-size:11px;font-weight:500;text-transform:capitalize}
        .oa-source.telegram{background:#1c2a3a;color:#7fb8ee}
        @keyframes oa-pulse{50%{opacity:.3}}
    </style>

    <div x-data="oltActivity(@js($initial))">

        {{-- PAGE HEADER --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
                    Activity · OLT
                </div>
                <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
                    OLT <span class="text-[#8a8a8a]">activity.</span>
                </h1>
                <p class="mt-2.5 max-w-xl text-xs text-[#a0a0a0]">
                    Pantau perintah OLT secara realtime: waktu mulai, durasi, status, pengguna, dan chat ID Telegram.
                </p>
            </div>

            <span class="inline-flex items-center gap-2 rounded-full border border-[#3a3a3a] px-3.5 py-1.5 text-xs text-[#d0d0d0]">
            <span class="h-2 w-2 rounded-full" :class="live ? 'bg-[#00a34a]' : 'bg-[#666]'"></span>
            <span x-text="live ? 'Realtime terhubung' : 'Menghubungkan…'"></span>
        </span>
        </div>

        {{-- STAT STRIP --}}
        <div class="mb-5 grid grid-cols-2 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] md:grid-cols-5">
            <div class="border-b border-r border-[#2e2e2e] px-5 py-4 md:border-b-0">
                <div class="text-[11px] text-[#8a8a8a]">Total</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]" x-text="Object.keys(rows).length"></div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">aktivitas dimuat</div>
            </div>
            <div class="border-b border-[#2e2e2e] px-5 py-4 md:border-b-0 md:border-r">
                <div class="text-[11px] text-[#8a8a8a]">Running / antrean</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#e0b155]" x-text="count('running') + ' / ' + count('pending')"></div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">sedang diproses</div>
            </div>
            <div class="border-b border-r border-[#2e2e2e] px-5 py-4 md:border-b-0">
                <div class="text-[11px] text-[#8a8a8a]">Sukses</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#4cd286]" x-text="count('completed')"></div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">selesai normal</div>
            </div>
            <div class="border-b border-[#2e2e2e] px-5 py-4 md:border-b-0 md:border-r">
                <div class="text-[11px] text-[#8a8a8a]">Gagal</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f08a8a]" x-text="count('failed')"></div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">perlu diperiksa</div>
            </div>
            <div class="col-span-2 px-5 py-4 md:col-span-1">
                <div class="text-[11px] text-[#8a8a8a]">Rata-rata durasi</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]" x-text="avg()"></div>
                <div class="mt-1 text-[11px] text-[#8a8a8a]">per perintah</div>
            </div>
        </div>

        {{-- ACTIVITY CARD --}}
        <div class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

            {{-- TOOLBAR --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2e2e2e] px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-[#f2f2f2]">
                        Aktivitas terbaru
                        <span class="ml-2 rounded-full bg-[#2c2c2c] px-2 py-0.5 text-[10px] font-medium text-[#aaa]" x-text="list.length + ' entri'"></span>
                    </h2>
                    <p class="mt-0.5 text-[11px] text-[#8a8a8a]">
                        Data awal dari database, pembaruan lewat channel <span class="font-mono">olt-activity</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <input type="text" x-model="q" placeholder="Cari user, OLT, target, command…"
                           class="h-9 w-64 rounded-full border border-[#3a3a3a] bg-[#1c1c1c] px-4 text-xs text-[#e5e5e5] outline-none placeholder:text-[#777] focus:border-[#5a5a5a]">

                    <select x-model="fStatus"
                            class="h-9 rounded-full border border-[#3a3a3a] bg-[#1c1c1c] px-3 text-xs text-[#d0d0d0] outline-none focus:border-[#5a5a5a]">
                        <option value="">Semua status</option>
                        <option value="pending">Antrean</option>
                        <option value="running">Running</option>
                        <option value="completed">Sukses</option>
                        <option value="failed">Gagal</option>
                    </select>

                    <select x-model="fSource"
                            class="h-9 rounded-full border border-[#3a3a3a] bg-[#1c1c1c] px-3 text-xs text-[#d0d0d0] outline-none focus:border-[#5a5a5a]">
                        <option value="">Semua sumber</option>
                        <option value="telegram">Telegram</option>
                        <option value="dashboard">Dashboard</option>
                    </select>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="border-b border-[#2e2e2e] text-[10px] font-semibold uppercase tracking-[0.1em] text-[#8a8a8a]">
                    <tr>
                        <th class="px-5 py-3.5">Mulai</th>
                        <th class="px-5 py-3.5">Selesai</th>
                        <th class="px-5 py-3.5">Durasi</th>
                        <th class="px-5 py-3.5">Command</th>
                        <th class="px-5 py-3.5">OLT</th>
                        <th class="px-5 py-3.5">Target</th>
                        <th class="px-5 py-3.5">User</th>
                        <th class="px-5 py-3.5">Sumber</th>
                        <th class="px-5 py-3.5">Status</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-[#272727]">
                    <template x-for="r in list" :key="r.request_id">
                        <tr class="align-top transition-colors hover:bg-[#232323]">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[11px] text-[#c4c4c4]" x-text="fmt(r.started_at || r.queued_at)"></td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[11px] text-[#8a8a8a]" x-text="fmt(r.finished_at)"></td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[11px] text-[#c4c4c4]" x-text="dur(r)"></td>
                            <td class="px-5 py-3.5">
                                <div class="text-[13px] font-semibold text-[#f2f2f2]" x-text="r.command"></div>
                                <div class="mt-0.5 font-mono text-[11px] text-[#8a8a8a]" x-text="r.operation"></div>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-[#c4c4c4]" x-text="r.olt"></td>
                            <td class="px-5 py-3.5 font-mono text-[11px] text-[#c4c4c4]" x-text="r.target"></td>
                            <td class="px-5 py-3.5">
                                <div class="text-xs text-[#e5e5e5]" x-text="r.user?.name || '-'"></div>
                                <template x-if="r.user?.id">
                                    <div class="mt-0.5 font-mono text-[11px] text-[#8a8a8a]" x-text="'ID ' + r.user.id"></div>
                                </template>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="oa-source" :class="r.source" x-text="r.source"></span>
                                <template x-if="r.chat_id">
                                    <div class="mt-1 font-mono text-[11px] text-[#8a8a8a]" x-text="'chat ' + r.chat_id"></div>
                                </template>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="oa-badge" :class="r.status"><i class="oa-dot"></i><span x-text="label(r.status)"></span></span>
                                <template x-if="r.error">
                                    <div class="mt-1.5 max-w-[260px] text-[11px] leading-snug text-[#f08a8a]" x-text="r.error"></div>
                                </template>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>

                <div class="px-5 py-14 text-center" x-show="list.length === 0" x-cloak>
                    <div class="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-full border border-[#3a3a3a] bg-[#242424]">
                        <i data-lucide="activity" class="h-5 w-5 text-[#777]"></i>
                    </div>
                    <p class="text-sm font-medium text-[#d0d0d0]">Belum ada aktivitas yang cocok</p>
                    <p class="mt-1 text-xs text-[#8a8a8a]">Ubah kata kunci atau filter untuk melihat aktivitas lain.</p>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                function oltActivity(initial) {
                    return {
                        rows: Object.fromEntries(initial.map(r => [r.request_id, r])),
                        now: Date.now(), q: '', fStatus: '', fSource: '', live: false,

                        init() {
                            setInterval(() => this.now = Date.now(), 1000);

                            if (!window.Echo) return;

                            // Status koneksi nyata dari echo.js
                            this.live = (window.reverbState?.() ?? 'connected') === 'connected';
                            window.addEventListener('reverb:state', e => {
                                this.live = e.detail === 'connected';
                            });

                            window.Echo.private('olt-activity')
                                .listen('.OltActivityUpdated', e => {
                                    this.rows[e.request_id] = { ...(this.rows[e.request_id] || {}), ...e };
                                });
                        },

                        get list() {
                            const q = this.q.toLowerCase();
                            return Object.values(this.rows)
                                .filter(r => (!this.fStatus || r.status === this.fStatus)
                                    && (!this.fSource || r.source === this.fSource)
                                    && (!q || JSON.stringify([r.user?.name, r.olt, r.target, r.chat_id, r.command, r.operation]).toLowerCase().includes(q)))
                                .sort((a, b) => new Date(b.queued_at || b.started_at) - new Date(a.queued_at || a.started_at))
                                .slice(0, 200);
                        },

                        count(s) { return Object.values(this.rows).filter(r => r.status === s).length; },
                        label(s) { return { pending: 'antrean', running: 'running', completed: 'sukses', failed: 'gagal' }[s] || s; },

                        avg() {
                            const d = Object.values(this.rows).filter(r => r.duration_ms != null).map(r => r.duration_ms);
                            return d.length ? (d.reduce((a, b) => a + b) / d.length / 1000).toFixed(1) + ' s' : '-';
                        },

                        dur(r) {
                            if (r.duration_ms != null) return (r.duration_ms / 1000).toFixed(1) + ' s';
                            if (r.started_at && !r.finished_at) return Math.max(0, (this.now - new Date(r.started_at)) / 1000).toFixed(0) + ' s…';
                            return '-';
                        },

                        fmt(t) {
                            return t ? new Date(t).toLocaleString('id-ID', { hour12: false, day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '-';
                        },
                    };
                }
            </script>
        @endpush

    </div>

</x-layouts.dashboard>
