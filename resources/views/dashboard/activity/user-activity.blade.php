<x-layouts.dashboard title="User Activity Log">

    {{-- PAGE HEADER --}}
    <div class="mb-6">
        <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
            Activity · Users
        </div>
        <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
            User <span class="text-[#8a8a8a]">activity.</span>
        </h1>
        <p class="mt-2.5 text-xs text-[#a0a0a0]">Riwayat aktivitas pengguna dashboard</p>
    </div>

    {{-- STAT STRIP --}}
    <div class="mb-5 grid grid-cols-3 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">
        <div class="border-r border-[#2e2e2e] px-5 py-4">
            <div class="text-[11px] text-[#8a8a8a]">Total aktivitas</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $logs->total() }}</div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">tercatat di dashboard</div>
        </div>
        <div class="border-r border-[#2e2e2e] px-5 py-4">
            <div class="text-[11px] text-[#8a8a8a]">Di halaman ini</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $logs->count() }}</div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">aktivitas ditampilkan</div>
        </div>
        <div class="px-5 py-4">
            <div class="text-[11px] text-[#8a8a8a]">Halaman</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">
                {{ $logs->currentPage() }}
                <span class="text-base font-medium text-[#8a8a8a]">/ {{ $logs->lastPage() }}</span>
            </div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">terbaru lebih dulu</div>
        </div>
    </div>

    {{-- ACTIVITY CARD --}}
    <div class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

        <div class="border-b border-[#2e2e2e] px-5 py-4">
            <h2 class="text-sm font-semibold text-[#f2f2f2]">Activity history</h2>
            <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Login, perubahan data, dan aksi pengguna lainnya</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left text-sm">

                <thead class="border-b border-[#2e2e2e] text-[10px] font-semibold uppercase tracking-[0.1em] text-[#8a8a8a]">
                <tr>
                    <th class="px-5 py-3.5">Waktu</th>
                    <th class="px-5 py-3.5">User</th>
                    <th class="px-5 py-3.5">Aksi</th>
                    <th class="px-5 py-3.5">Keterangan</th>
                    <th class="px-5 py-3.5">IP</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-[#272727]">

                @forelse($logs as $log)

                    @php
                        $name = $log->user?->name ?? 'System';
                        $initials = strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 2)) ?: '--';
                    @endphp

                    <tr class="transition-colors hover:bg-[#232323]">

                        <td class="whitespace-nowrap px-5 py-3.5">
                            <div class="text-[13px] font-semibold text-[#f2f2f2]">
                                {{ $log->created_at->format('d-m-Y') }}
                            </div>
                            <div class="mt-0.5 text-[11px] text-[#8a8a8a]">
                                {{ $log->created_at->format('H:i:s') }}
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-[#2c2c2c] text-[10px] font-semibold text-[#bbb]">
                                    {{ $initials }}
                                </span>
                                <span class="text-[13px] font-semibold text-[#f2f2f2]">{{ $name }}</span>
                            </div>
                        </td>

                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center rounded-full border border-[#3a3a3a] bg-[#242424] px-2.5 py-1 text-[11px] font-medium text-[#d0d0d0]">
                                {{ $log->action }}
                            </span>
                        </td>

                        <td class="max-w-md px-5 py-3.5 text-xs text-[#8a8a8a]">
                            <div class="max-w-md truncate" title="{{ $log->description }}">
                                {{ $log->description ?? '-' }}
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-5 py-3.5 font-mono text-[11px] text-[#8a8a8a]">
                            {{ $log->ip_address ?? '-' }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="mb-3 grid h-11 w-11 place-items-center rounded-full border border-[#3a3a3a] bg-[#242424]">
                                    <i data-lucide="shield-check" class="h-5 w-5 text-[#777]"></i>
                                </div>
                                <p class="text-sm font-medium text-[#d0d0d0]">Belum ada aktivitas</p>
                                <p class="mt-1 text-xs text-[#8a8a8a]">Aktivitas pengguna akan muncul di sini.</p>
                            </div>
                        </td>
                    </tr>

                @endforelse

                </tbody>
            </table>
        </div>

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

</x-layouts.dashboard>
