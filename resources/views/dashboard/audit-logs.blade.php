<x-layouts.dashboard title="Audit Log">
<div class="mb-6"><h1 class="text-3xl font-bold">Audit Log</h1><p class="mt-1 text-slate-400">Riwayat aktivitas pengguna dashboard.</p></div>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white"><table class="w-full text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-slate-500"><tr><th class="p-4">Waktu</th><th class="p-4">User</th><th class="p-4">Aksi</th><th class="p-4">Keterangan</th><th class="p-4">IP</th></tr></thead><tbody>
@forelse($logs as $log)<tr class="border-b border-slate-100"><td class="p-4">{{ $log->created_at->format('d-m-Y H:i') }}</td><td class="p-4">{{ $log->user?->name ?? 'System' }}</td><td class="p-4 font-medium">{{ $log->action }}</td><td class="p-4 text-slate-500">{{ $log->description }}</td><td class="p-4 text-slate-500">{{ $log->ip_address }}</td></tr>@empty<tr><td colspan="5" class="p-6 text-center text-slate-500">Belum ada aktivitas.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.dashboard>
