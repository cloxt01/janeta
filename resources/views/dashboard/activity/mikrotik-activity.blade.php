<x-layouts.dashboard title="MikroTik Activity Logs">

    {{-- PAGE HEADER --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">

        <div>
            <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
                Activity · MikroTik
            </div>

            <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
                MikroTik <span class="text-[#8a8a8a]">activity.</span>
            </h1>

            <p class="mt-2.5 text-xs text-[#a0a0a0]">
                Live activity stream from your MikroTik routers
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border border-[#3a3a3a] px-3.5 py-1.5 text-xs text-[#d0d0d0]">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#00a34a] opacity-40"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-[#00a34a]"></span>
                </span>
                Realtime monitoring
            </span>
        </div>
    </div>

    {{-- STAT STRIP --}}
    <div class="mb-5 grid grid-cols-2 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] md:grid-cols-4">

        <div class="border-b border-[#2e2e2e] px-5 py-4 md:border-b-0 md:border-r">
            <div class="text-[11px] text-[#8a8a8a]">Total events</div>
            <div id="log-count" class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">
                {{ $logs->total() }}
            </div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">recorded from all routers</div>
        </div>

        <div class="border-b border-[#2e2e2e] px-5 py-4 md:border-b-0 md:border-r">
            <div class="text-[11px] text-[#8a8a8a]">Connection</div>
            <div class="mt-2 flex items-center gap-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">
                <span id="reverb-indicator" class="h-2.5 w-2.5 rounded-full bg-[#888]"></span>
                <span id="reverb-status" class="text-base text-[#999]">Connecting...</span>
            </div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">Reverb websocket</div>
        </div>

        <div class="border-r border-[#2e2e2e] px-5 py-4">
            <div class="text-[11px] text-[#8a8a8a]">On this page</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">
                {{ $logs->count() }}
            </div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">events shown</div>
        </div>

        <div class="px-5 py-4">
            <div class="text-[11px] text-[#8a8a8a]">Page</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">
                {{ $logs->currentPage() }}
                <span class="text-base font-medium text-[#8a8a8a]">/ {{ $logs->lastPage() }}</span>
            </div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">newest first</div>
        </div>

    </div>

    {{-- ACTIVITY CARD --}}
    <div class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

        {{-- CARD HEADER --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2e2e2e] px-5 py-4">

            <div>
                <h2 class="text-sm font-semibold text-[#f2f2f2]">Activity history</h2>
                <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Billing, configuration and network events</p>
            </div>

            <span class="text-[11px] font-medium text-[#00a34a]">Live</span>

        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto">

            <table class="w-full min-w-[1100px] text-left text-sm">

                <thead class="border-b border-[#2e2e2e] text-[10px] font-semibold uppercase tracking-[0.1em] text-[#8a8a8a]">
                <tr>
                    <th class="px-5 py-3.5">Waktu</th>
                    <th class="px-5 py-3.5">Router</th>
                    <th class="px-5 py-3.5">Event</th>
                    <th class="px-5 py-3.5">Topics</th>
                    <th class="px-5 py-3.5">User</th>
                    <th class="px-5 py-3.5">Action</th>
                    <th class="px-5 py-3.5">Message</th>
                </tr>
                </thead>

                <tbody id="activity-log-body" class="divide-y divide-[#272727]">

                @forelse($logs as $log)

                    @php
                        $routerName = $log->router?->name ?? 'Unknown Router';
                        $initials = strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $routerName), 0, 2)) ?: '--';
                    @endphp

                    <tr data-log-id="{{ $log->id }}"
                        class="transition-colors hover:bg-[#232323]">

                        {{-- TIME --}}
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <div class="text-[13px] font-semibold text-[#f2f2f2]">
                                {{ $log->router_date
                                    ? \Carbon\Carbon::parse($log->router_date)->format('d-m-Y')
                                    : '-' }}
                            </div>

                            <div class="mt-0.5 text-[11px] text-[#8a8a8a]">
                                {{ $log->router_time ?? '--:--:--' }}
                                @if($log->router_timezone)
                                    · {{ $log->router_timezone }}
                                @endif
                            </div>
                        </td>

                        {{-- ROUTER --}}
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-[#2c2c2c] text-[10px] font-semibold text-[#bbb]">
                                    {{ $initials }}
                                </span>
                                <span class="text-[13px] font-semibold text-[#f2f2f2]">{{ $routerName }}</span>
                            </div>
                        </td>

                        {{-- EVENT --}}
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center rounded-full border border-[#3a3a3a] bg-[#242424] px-2.5 py-1 text-[11px] font-medium text-[#d0d0d0]">
                                {{ $log->event_type }}
                            </span>
                        </td>

                        {{-- TOPICS --}}
                        <td class="px-5 py-3.5 text-xs text-[#8a8a8a]">
                            {{ $log->topics ?? '-' }}
                        </td>

                        {{-- USER --}}
                        <td class="px-5 py-3.5 text-xs text-[#c4c4c4]">
                            {{ $log->router_user ?? '-' }}
                        </td>

                        {{-- ACTION --}}
                        <td class="px-5 py-3.5 text-[13px] font-semibold text-[#f2f2f2]">
                            {{ $log->action ?? '-' }}
                        </td>

                        {{-- MESSAGE --}}
                        <td class="max-w-md px-5 py-3.5 text-xs text-[#8a8a8a]">
                            <div class="max-w-md truncate" title="{{ $log->message }}">
                                {{ $log->message ?? '-' }}
                            </div>
                        </td>

                    </tr>

                @empty

                    <tr id="empty-log-row">
                        <td colspan="7" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="mb-3 grid h-11 w-11 place-items-center rounded-full border border-[#3a3a3a] bg-[#242424]">
                                    <i data-lucide="activity" class="h-5 w-5 text-[#777]"></i>
                                </div>

                                <p class="text-sm font-medium text-[#d0d0d0]">
                                    Belum ada aktivitas MikroTik
                                </p>

                                <p class="mt-1 text-xs text-[#8a8a8a]">
                                    Aktivitas router akan muncul di sini.
                                </p>
                            </div>
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        {{-- TABLE FOOTER --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#2e2e2e] px-5 py-4">

            <div class="text-xs text-[#8a8a8a]">
                Menampilkan
                <span class="font-semibold text-[#f2f2f2]">{{ $logs->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-semibold text-[#f2f2f2]">{{ $logs->lastItem() ?? 0 }}</span>
                dari
                <span class="font-semibold text-[#f2f2f2]">{{ $logs->total() }}</span>
                aktivitas
            </div>

            <div class="jn-pagination">
                {{ $logs->onEachSide(1)->links('pagination::tailwind') }}
            </div>

        </div>

    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const body = document.getElementById('activity-log-body');
                const indicator = document.getElementById('reverb-indicator');
                const status = document.getElementById('reverb-status');
                const count = document.getElementById('log-count');

                function setConnectionState(connected) {

                    indicator.className = connected
                        ? 'h-2.5 w-2.5 rounded-full bg-[#00a34a]'
                        : 'h-2.5 w-2.5 rounded-full bg-red-500';

                    status.textContent = connected ? 'Live' : 'Disconnected';

                    status.className = connected
                        ? 'text-base text-[#00a34a]'
                        : 'text-base text-red-400';
                }

                function el(tag, className = '', text = null) {
                    const node = document.createElement(tag);
                    node.className = className;
                    if (text !== null) node.textContent = text;
                    return node;
                }

                function cell(className = '') {
                    return el('td', 'px-5 py-3.5 ' + className);
                }

                function textCell(value, className = '') {
                    const td = cell(className);
                    td.textContent = value || '-';
                    return td;
                }

                function formatDate(date) {
                    if (!date) return '-';
                    const parts = date.split('-');
                    return parts.length === 3
                        ? `${parts[2]}-${parts[1]}-${parts[0]}`
                        : date;
                }

                function initials(name) {
                    const clean = (name || '').replace(/[^A-Za-z0-9]/g, '');
                    return clean.slice(0, 2).toUpperCase() || '--';
                }

                function addActivity(log) {

                    if (!log || !log.id) return;

                    // Prevent duplicate records
                    if (body.querySelector(`[data-log-id="${log.id}"]`)) return;

                    document.getElementById('empty-log-row')?.remove();

                    const row = el('tr', 'bg-[#12331f] transition-colors duration-1000 hover:bg-[#232323]');
                    row.dataset.logId = log.id;

                    // TIME
                    const timeCell = cell('whitespace-nowrap');
                    timeCell.appendChild(el('div', 'text-[13px] font-semibold text-[#f2f2f2]', formatDate(log.router_date)));

                    let sub = log.router_time || '--:--:--';
                    if (log.router_timezone) sub += ' · ' + log.router_timezone;
                    timeCell.appendChild(el('div', 'mt-0.5 text-[11px] text-[#8a8a8a]', sub));
                    row.appendChild(timeCell);

                    // ROUTER
                    const routerName = log.router_name || 'Unknown Router';
                    const routerCell = cell('whitespace-nowrap');
                    const wrap = el('div', 'flex items-center gap-3');
                    wrap.appendChild(el('span', 'grid h-7 w-7 place-items-center rounded-full bg-[#2c2c2c] text-[10px] font-semibold text-[#bbb]', initials(routerName)));
                    wrap.appendChild(el('span', 'text-[13px] font-semibold text-[#f2f2f2]', routerName));
                    routerCell.appendChild(wrap);
                    row.appendChild(routerCell);

                    // EVENT
                    const eventCell = cell();
                    eventCell.appendChild(el(
                        'span',
                        'inline-flex items-center rounded-full border border-[#3a3a3a] bg-[#242424] px-2.5 py-1 text-[11px] font-medium text-[#d0d0d0]',
                        log.event_type || '-'
                    ));
                    row.appendChild(eventCell);

                    // TOPICS, USER, ACTION
                    row.appendChild(textCell(log.topics, 'text-xs text-[#8a8a8a]'));
                    row.appendChild(textCell(log.router_user, 'text-xs text-[#c4c4c4]'));
                    row.appendChild(textCell(log.action, 'text-[13px] font-semibold text-[#f2f2f2]'));

                    // MESSAGE
                    const messageCell = cell('max-w-md text-xs text-[#8a8a8a]');
                    const message = el('div', 'max-w-md truncate', log.message || '-');
                    message.title = log.message || '';
                    messageCell.appendChild(message);
                    row.appendChild(messageCell);

                    body.prepend(row);

                    // Limit realtime rows
                    while (body.querySelectorAll('tr[data-log-id]').length > 25) {
                        body.querySelector('tr[data-log-id]:last-child')?.remove();
                    }

                    count.textContent = Number(count.textContent || 0) + 1;

                    setTimeout(() => {
                        row.classList.remove('bg-[#12331f]');
                    }, 2500);
                }

                function connectReverb() {

                    if (!window.Echo) {
                        setConnectionState(false);
                        console.error('[JANETA] Laravel Echo belum tersedia.');
                        return;
                    }

                    const connection = window.Echo.connector.pusher.connection;

                    connection.bind('connected', () => setConnectionState(true));
                    connection.bind('disconnected', () => setConnectionState(false));
                    connection.bind('error', () => setConnectionState(false));

                    setConnectionState(connection.state === 'connected');

                    window.Echo
                        .private('mikrotik.activity')
                        .listen('.mikrotik.activity.logged', (event) => {

                            if (!event.activity) return;

                            addActivity(event.activity);

                            console.log('[JANETA] New MikroTik activity:', event.activity);
                        });
                }

                connectReverb();

            });
        </script>
    @endpush

</x-layouts.dashboard>


