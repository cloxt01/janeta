<x-layouts.dashboard title="Overview">
    <div class="mb-8"><h1 class="text-3xl font-bold">Overview</h1><p class="mt-1 text-slate-400">Kontrol pusat JANETA.</p></div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach([['OLT', $olts->count()], ['Whitelist Telegram', $whitelistCount], ['Total User', $userCount]] as [$label, $value])
            <div class="jn-stat-card"><div class="text-sm text-slate-500">{{ $label }}</div><div class="mt-2 text-3xl font-bold text-slate-900">{{ $value }}</div></div>
        @endforeach
    </div>
    <section class="jn-panel mt-8"><div class="mb-4 flex items-center justify-between"><h2 class="font-semibold">Status koneksi OLT</h2><a href="{{ route('olts') }}" class="jn-link text-sm">Kelola OLT <i data-lucide="arrow-up-right"></i></a></div>@forelse($olts as $olt)<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 py-3 text-sm"><div><span class="font-medium">{{ $olt->name }}</span><span class="ml-3 text-slate-500">{{ $olt->host }}:{{ $olt->port }}</span></div><span class="font-medium text-slate-700">{{ strtoupper($olt->status) }}{{ $olt->last_checked_at ? ' · '.$olt->last_checked_at->diffForHumans() : '' }}</span></div>@empty<p class="text-sm text-slate-500">Belum ada OLT.</p>@endforelse</section>
    <section class="jn-panel mt-8"><h2 class="mb-4 font-semibold">Whitelist terbaru</h2>@forelse($recentWhitelist as $entry)<div class="flex justify-between border-t border-slate-200 py-3 text-sm"><span>{{ $entry->name ?: 'Tanpa nama' }}</span><span class="text-slate-500">{{ $entry->chat_id }}</span></div>@empty<p class="text-sm text-slate-500">Belum ada data.</p>@endforelse</section>
</x-layouts.dashboard>
