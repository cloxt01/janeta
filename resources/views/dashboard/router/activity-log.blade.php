
<x-layouts.dashboard title="Mikrotik Activity Logs">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Mikrotik Activity Logs</h1>
            <p class="mt-1 text-slate-400">
                Monitoring aktivitas dan perubahan konfigurasi MikroTik secara realtime.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                <span id="reverb-indicator"
                      class="h-2 w-2 rounded-full bg-slate-400"></span>
                <span id="reverb-status" class="text-slate-500">
                    Connecting...
                </span>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500">
                Total: <strong id="log-count" class="text-slate-900">{{ $logs->total() }}</strong>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
            <div>
                <h2 class="font-semibold text-slate-800">Activity History</h2>
                <p class="text-xs text-slate-500">
                    Live activity stream from MikroTik routers
                </p>
            </div>

            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600">
                Realtime Monitoring
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">

                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-4">Waktu</th>
                    <th class="p-4">Router</th>
                    <th class="p-4">Event</th>
                    <th class="p-4">Topics</th>
                    <th class="p-4">User</th>
                    <th class="p-4">Action</th>
                    <th class="p-4">Message</th>
                </tr>
                </thead>

                <tbody id="activity-log-body" class="divide-y divide-slate-100">

                @forelse($logs as $log)

                    <tr data-log-id="{{ $log->id }}"
                        class="hover:bg-slate-50 transition-colors">

                        <td class="whitespace-nowrap p-4 text-slate-500">
                            <div class="font-medium text-slate-700">
                                {{ $log->router_date
                                    ? \Carbon\Carbon::parse($log->router_date)->format('d-m-Y')
                                    : '-' }}
                            </div>

                            <div class="text-xs text-slate-500">
                                {{ $log->router_time ?? '--:--:--' }}
                            </div>

                            @if($log->router_timezone)
                                <div class="text-[10px] text-slate-400">
                                    {{ $log->router_timezone }}
                                </div>
                            @endif
                        </td>

                        <td class="whitespace-nowrap p-4 font-semibold text-slate-800">
                            {{ $log->router?->name ?? 'Unknown Router' }}
                        </td>

                        <td class="p-4">
                                <span class="inline-flex rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                    {{ $log->event_type }}
                                </span>
                        </td>

                        <td class="p-4 text-slate-500">
                            {{ $log->topics ?? '-' }}
                        </td>

                        <td class="p-4 text-slate-600">
                            {{ $log->router_user ?? '-' }}
                        </td>

                        <td class="p-4 font-medium text-slate-700">
                            {{ $log->action ?? '-' }}
                        </td>

                        <td class="max-w-md p-4 text-slate-500">
                            <div class="max-w-md truncate"
                                 title="{{ $log->message }}">
                                {{ $log->message ?? '-' }}
                            </div>
                        </td>

                    </tr>

                @empty

                    <tr id="empty-log-row">
                        <td colspan="7" class="p-10 text-center text-slate-500">
                            Belum ada aktivitas MikroTik.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-4">
        <div class="text-sm text-slate-500">
            Menampilkan
            <span class="font-semibold text-slate-700">{{ $logs->firstItem() ?? 0 }}</span>
            sampai
            <span class="font-semibold text-slate-700">{{ $logs->lastItem() ?? 0 }}</span>
            dari
            <span class="font-semibold text-slate-700">{{ $logs->total() }}</span>
            aktivitas
        </div>

        <div>
            {{ $logs->onEachSide(1)->links('pagination::tailwind') }}
        </div>
    </div>

</x-layouts.dashboard>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.getElementById('activity-log-body');
        const indicator = document.getElementById('reverb-indicator');
        const status = document.getElementById('reverb-status');
        const count = document.getElementById('log-count');

        function setConnectionState(connected) {
            indicator.className = connected
                ? 'h-2 w-2 rounded-full bg-emerald-500'
                : 'h-2 w-2 rounded-full bg-red-500';

            status.textContent = connected
                ? 'Live Connected'
                : 'Disconnected';

            status.className = connected
                ? 'text-emerald-600'
                : 'text-red-500';
        }

        function createCell(value, className = '') {
            const td = document.createElement('td');
            td.className = 'p-4 ' + className;
            td.textContent = value || '-';
            return td;
        }

        // Format tanggal dan jam dari MikroTik
        function formatRouterDate(date, time) {
            if (!date && !time) return '-';

            let formattedDate = '-';

            if (date) {
                const parts = date.split('-');

                if (parts.length === 3) {
                    formattedDate = `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
            }

            return `${formattedDate} ${time || '--:--:--'}`;
        }

        function addActivity(log) {
            if (!log || !log.id) return;

            // Hindari duplikasi
            if (body.querySelector(`[data-log-id="${log.id}"]`)) {
                return;
            }

            const emptyRow = document.getElementById('empty-log-row');
            if (emptyRow) emptyRow.remove();

            const row = document.createElement('tr');

            row.dataset.logId = log.id;
            row.className =
                'bg-blue-50/70 hover:bg-slate-50 transition-colors';

            // Waktu RouterOS
            row.appendChild(createCell(
                formatRouterDate(log.router_date, log.router_time),
                'whitespace-nowrap text-slate-500'
            ));

            // Nama router
            row.appendChild(createCell(
                log.router_name,
                'whitespace-nowrap font-semibold text-slate-800'
            ));

            // Event
            const eventCell = document.createElement('td');
            eventCell.className = 'p-4';

            const badge = document.createElement('span');
            badge.className =
                'inline-flex rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700';

            badge.textContent = log.event_type || '-';

            eventCell.appendChild(badge);
            row.appendChild(eventCell);

            // Topics, user, action
            row.appendChild(createCell(log.topics, 'text-slate-500'));
            row.appendChild(createCell(log.router_user, 'text-slate-600'));
            row.appendChild(createCell(log.action, 'font-medium text-slate-700'));

            // Message
            const messageCell = createCell(
                log.message,
                'max-w-md text-slate-500'
            );

            messageCell.classList.add('truncate');
            messageCell.title = log.message || '';

            row.appendChild(messageCell);

            body.prepend(row);

            // Batasi jumlah baris realtime
            while (body.querySelectorAll('tr[data-log-id]').length > 25) {
                body.querySelector('tr[data-log-id]:last-child')?.remove();
            }

            count.textContent = Number(count.textContent || 0) + 1;

            setTimeout(() => {
                row.classList.remove('bg-blue-50/70');
            }, 2000);
        }

        function connectReverb() {
            if (!window.Echo) {
                setConnectionState(false);
                console.error('[JANETA] Laravel Echo belum tersedia.');
                return;
            }

            const connection = window.Echo.connector.pusher.connection;

            connection.bind('connected', () => {
                setConnectionState(true);
            });

            connection.bind('disconnected', () => {
                setConnectionState(false);
            });

            connection.bind('error', () => {
                setConnectionState(false);
            });

            setConnectionState(connection.state === 'connected');

            window.Echo
                .private('mikrotik.activity')
                .listen('.mikrotik.activity.logged', (event) => {
                    if (!event.activity) return;

                    addActivity(event.activity);

                    console.log(
                        '[JANETA] New MikroTik activity:',
                        event.activity
                    );
                });
        }

        connectReverb();
    });
</script>
