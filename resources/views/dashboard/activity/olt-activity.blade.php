<x-layouts.dashboard title="OLT Activity">
<style>
    .act-page .eyebrow{color:#64748b;font-size:10px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}
    .act-toolbar,.act-device,.act-stat{border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgb(15 23 42 / 4%)}
    .act-toolbar{padding:16px}
    .act-toolbar label{color:#64748b;font-size:11px;font-weight:600}
    .act-toolbar select,.act-toolbar input{margin-top:7px;width:100%;border:1px solid #cbd5e1;border-radius:7px;background:#fff;padding:9px 11px;color:#1e293b;font-size:13px;outline:0}
    .act-toolbar select:focus,.act-toolbar input:focus{border-color:#64748b;box-shadow:0 0 0 3px rgb(100 116 139 / 12%)}
    .act-device{padding:20px}
    .act-device-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;border-bottom:1px solid #e2e8f0;padding-bottom:18px}
    .act-device-title{display:flex;align-items:center;gap:11px;color:#0f172a;font-size:17px;font-weight:650}
    .act-device-icon{display:grid;width:36px;height:36px;place-items:center;border:1px solid #cbd5e1;border-radius:9px;background:#f8fafc;color:#334155}
    .act-device-icon svg{width:18px;height:18px}
    .act-device-meta{margin-top:5px;color:#64748b;font-size:12px}
    .act-live{display:inline-flex;align-items:center;gap:6px;color:#64748b;font-size:11px}
    .act-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin:18px 0}
    .act-stat{padding:14px 15px;box-shadow:none}
    .act-stat-label{color:#64748b;font-size:11px}
    .act-stat-value{margin-top:5px;color:#0f172a;font-size:21px;font-weight:650;line-height:1}
    .act-stat.online .act-stat-value{color:#16805b}
    .act-stat.offline .act-stat-value{color:#a16207}
    .act-stat.los .act-stat-value{color:#b45353}
    .act-stat-note{margin-top:6px;color:#94a3b8;font-size:10px}
    .act-table-wrap{overflow-x:auto;border:1px solid #e2e8f0;border-radius:10px}
    .act-table{width:100%;border-collapse:collapse;font-size:12px}
    .act-table th{background:#f8fafc;color:#64748b;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;text-align:left;padding:10px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
    .act-table td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:top}
    .act-table tr:last-child td{border-bottom:0}
    .act-table tbody tr:hover{background:#f8fafc}
    .act-sub{margin-top:2px;color:#94a3b8;font-size:10px}
    .act-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px}
    .act-dot{display:inline-block;width:6px;height:6px;border-radius:50%;background:#cbd5e1}
    .act-badge{display:inline-flex;align-items:center;gap:6px;border:1px solid #e2e8f0;border-radius:7px;background:#f8fafc;padding:3px 8px;color:#64748b;font-size:10px;font-weight:600;text-transform:capitalize}
    .act-badge.pending .act-dot{background:#94a3b8}
    .act-badge.running{border-color:#eadbbd;background:#fdfaf3;color:#a16207}.act-badge.running .act-dot{background:#c28a2c;animation:act-pulse 1.2s infinite}
    .act-badge.completed{border-color:#c9e5d8;background:#f5fbf7;color:#287a5b}.act-badge.completed .act-dot{background:#16805b}
    .act-badge.failed{border-color:#e5caca;background:#fdf8f8;color:#9f4b4b}.act-badge.failed .act-dot{background:#b45353}
    .act-source{display:inline-flex;align-items:center;gap:5px;border-radius:6px;background:#f1f5f9;padding:2px 7px;color:#475569;font-size:10px;font-weight:600}
    .act-source.telegram{background:#eff6ff;color:#2563eb}
    .act-err{margin-top:5px;max-width:260px;color:#9f4b4b;font-size:10px;line-height:1.4}
    .act-empty{padding:40px 0;text-align:center;color:#94a3b8;font-size:12px}
    @keyframes act-pulse{50%{opacity:.3}}
    @media(max-width:900px){.act-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.act-device-head{flex-direction:column}}
</style>

<div class="act-page" x-data="oltActivity(@js($initial))">
    <div class="mb-6">
        <p class="eyebrow mb-2">Infrastructure</p>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">OLT Activity</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau perintah OLT secara realtime: kapan mulai, durasi, selesai, siapa yang menjalankan, dan chat ID Telegram.</p>
    </div>

    <div class="act-toolbar mb-5 grid gap-3 md:grid-cols-4">
        <label class="md:col-span-2">Cari
            <input type="text" x-model="q" placeholder="User, OLT, target, command, chat ID…">
        </label>
        <label>Status
            <select x-model="fStatus">
                <option value="">Semua status</option>
                <option value="pending">Antrean</option>
                <option value="running">Running</option>
                <option value="completed">Sukses</option>
                <option value="failed">Gagal</option>
            </select>
        </label>
        <label>Sumber
            <select x-model="fSource">
                <option value="">Semua sumber</option>
                <option value="telegram">Telegram</option>
                <option value="dashboard">Dashboard</option>
            </select>
        </label>
    </div>

    <section class="act-device">
        <div class="act-device-head">
            <div>
                <div class="act-device-title">
                    <span class="act-device-icon"><i data-lucide="activity"></i></span>
                    Aktivitas Terbaru
                    <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-500" x-text="list.length + ' entri'"></span>
                </div>
                <p class="act-device-meta">Data awal dari database, pembaruan lewat broadcast channel <span class="act-mono">olt-activity</span>.</p>
            </div>
            <div class="act-live"><i class="act-dot" :style="live ? 'background:#16805b' : 'background:#cbd5e1'"></i><span x-text="live ? 'Realtime terhubung' : 'Menghubungkan…'"></span></div>
        </div>

        <div class="act-stats">
            <div class="act-stat"><div class="act-stat-label">Total</div><div class="act-stat-value" x-text="Object.keys(rows).length"></div><div class="act-stat-note">aktivitas dimuat</div></div>
            <div class="act-stat offline"><div class="act-stat-label">Running / Antrean</div><div class="act-stat-value" x-text="count('running') + ' / ' + count('pending')"></div><div class="act-stat-note">sedang diproses</div></div>
            <div class="act-stat online"><div class="act-stat-label">Sukses</div><div class="act-stat-value" x-text="count('completed')"></div><div class="act-stat-note">selesai normal</div></div>
            <div class="act-stat los"><div class="act-stat-label">Gagal</div><div class="act-stat-value" x-text="count('failed')"></div><div class="act-stat-note">perlu diperiksa</div></div>
            <div class="act-stat"><div class="act-stat-label">Rata-rata durasi</div><div class="act-stat-value" x-text="avg()"></div><div class="act-stat-note">per perintah</div></div>
        </div>

        <div class="act-table-wrap">
            <table class="act-table">
                <thead>
                    <tr>
                        <th>Mulai</th><th>Selesai</th><th>Durasi</th><th>Command</th><th>OLT</th>
                        <th>Target</th><th>User</th><th>Sumber</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in list" :key="r.request_id">
                        <tr>
                            <td class="act-mono" x-text="fmt(r.started_at || r.queued_at)"></td>
                            <td class="act-mono" x-text="fmt(r.finished_at)"></td>
                            <td class="act-mono" x-text="dur(r)"></td>
                            <td>
                                <div class="font-semibold text-slate-800" x-text="r.command"></div>
                                <div class="act-sub act-mono" x-text="r.operation"></div>
                            </td>
                            <td x-text="r.olt"></td>
                            <td class="act-mono" x-text="r.target"></td>
                            <td>
                                <div x-text="r.user?.name || '-'"></div>
                                <template x-if="r.user?.id"><div class="act-sub act-mono" x-text="'ID ' + r.user.id"></div></template>
                            </td>
                            <td>
                                <span class="act-source" :class="r.source" x-text="r.source"></span>
                                <template x-if="r.chat_id"><div class="act-sub act-mono" x-text="'chat ' + r.chat_id"></div></template>
                            </td>
                            <td>
                                <span class="act-badge" :class="r.status"><i class="act-dot"></i><span x-text="label(r.status)"></span></span>
                                <template x-if="r.error"><div class="act-err" x-text="r.error"></div></template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="act-empty" x-show="list.length === 0">Belum ada aktivitas yang cocok.</div>
        </div>

        <div class="mt-4 flex flex-wrap gap-4 text-[11px] text-slate-500">
            <span class="inline-flex items-center gap-1.5"><i class="act-dot" style="background:#94a3b8"></i>Antrean</span>
            <span class="inline-flex items-center gap-1.5"><i class="act-dot" style="background:#c28a2c"></i>Running</span>
            <span class="inline-flex items-center gap-1.5"><i class="act-dot" style="background:#16805b"></i>Sukses</span>
            <span class="inline-flex items-center gap-1.5"><i class="act-dot" style="background:#b45353"></i>Gagal</span>
        </div>
    </section>
</div>


<script>
    function oltActivity(initial) {
        return {
            rows: Object.fromEntries(initial.map(r => [r.request_id, r])),
            now: Date.now(), q: '', fStatus: '', fSource: '', live: false,
            init() {
                setInterval(() => this.now = Date.now(), 1000);
                if (!window.Echo) return;
                const ch = window.Echo.private('olt-activity');
                ch.listen('.OltActivityUpdated', e => {
                    this.rows[e.request_id] = { ...(this.rows[e.request_id] || {}), ...e };
                });
                this.live = true;
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
            fmt(t) { return t ? new Date(t).toLocaleString('id-ID', { hour12: false, day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '-'; },
        };
    }
</script>
</x-layouts.dashboard>