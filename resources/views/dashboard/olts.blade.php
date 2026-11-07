<x-layouts.dashboard title="OLT Connection">
<div class="mb-7 flex flex-wrap items-end justify-between gap-4">
    <div><p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-500">Infrastructure</p><h1 class="text-3xl font-bold tracking-tight">Manage OLT</h1><p class="mt-2 text-slate-400">Kelola koneksi, status, dan akses OLT dari satu tempat.</p></div>
    <form method="post" action="{{ route('olts.test-all') }}">@csrf<button class="jn-button jn-button-secondary">Cek semua koneksi</button></form>
</div>

<section class="mb-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4"><h2 class="font-semibold text-slate-700">Tambah OLT</h2><p class="mt-1 text-xs text-slate-500">Pilih SSH untuk operasi CLI atau SNMP untuk monitoring. Pada SNMP, username diisi community SNMP (umumnya v2c).</p></div>
    <form method="post" action="{{ route('olts.store') }}" class="grid gap-3 md:grid-cols-6">@csrf
        <input name="name" placeholder="Nama (DUTA)" required class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-cyan-400">
        <select name="connection_type" required class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-cyan-400"><option value="ssh">SSH / CLI</option><option value="snmp">SNMP</option></select>
        <select name="transport_type" required class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-cyan-400"><option value="tcp">TCP</option><option value="udp">UDP</option></select>
        <input name="host" placeholder="IP / hostname" required class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-cyan-400">
        <input id="new-olt-port" name="port" type="number" value="22" min="1" max="65535" required class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-cyan-400">
        <input name="username" placeholder="SSH username / SNMP community" required class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-cyan-400">
        <input name="password" type="password" placeholder="SSH password / kosong untuk SNMP" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-cyan-400">
        <button class="jn-button md:col-span-6">Tambah OLT</button>
    </form>
</section>
@foreach($olts as $olt)
<form id="update-olt-{{ $olt->id }}" method="post" action="{{ route('olts.update', $olt) }}">@csrf @method('PUT')</form>
<form id="test-olt-{{ $olt->id }}" method="post" action="{{ route('olts.test', $olt) }}">@csrf</form>
<form id="delete-olt-{{ $olt->id }}" method="post" action="{{ route('olts.destroy', $olt) }}">@csrf @method('DELETE')</form>
@endforeach

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-700">Daftar OLT</h2><p class="mt-1 text-xs text-slate-500">Perubahan aktif/nonaktif akan disimpan saat tombol Simpan ditekan.</p></div>
    <div class="overflow-x-auto"><table class="w-full min-w-[980px] text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">OLT</th><th class="px-5 py-4">Koneksi</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($olts as $olt)
        <tr class="align-top transition hover:bg-slate-50">
            <td class="space-y-2 px-5 py-4"><input form="update-olt-{{ $olt->id }}" name="name" value="{{ $olt->name }}" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700 outline-none focus:border-cyan-400"><input form="update-olt-{{ $olt->id }}" name="host" value="{{ $olt->host }}" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-600 outline-none focus:border-cyan-400"></td>
            <td class="space-y-2 px-5 py-4"><select form="update-olt-{{ $olt->id }}" name="connection_type" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-600 outline-none focus:border-cyan-400"><option value="ssh" @selected($olt->connection_type === 'ssh')>SSH / CLI</option><option value="snmp" @selected($olt->connection_type === 'snmp')>SNMP</option></select><div class="flex gap-2"><select form="update-olt-{{ $olt->id }}" name="transport_type" required class="w-24 rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-600 outline-none focus:border-cyan-400"><option value="tcp" @selected($olt->transport_type === 'tcp')>TCP</option><option value="udp" @selected($olt->transport_type === 'udp')>UDP</option></select><input form="update-olt-{{ $olt->id }}" name="port" type="number" min="1" max="65535" value="{{ $olt->port }}" required class="w-24 rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-600 outline-none focus:border-cyan-400"><input form="update-olt-{{ $olt->id }}" name="username" value="{{ $olt->username }}" required class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-600 outline-none focus:border-cyan-400"></div><input form="update-olt-{{ $olt->id }}" name="password" type="password" placeholder="Kosongkan jika tidak diubah" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-600 outline-none focus:border-cyan-400"></td>
            <td class="px-5 py-4"><label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm {{ $olt->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}"><input form="update-olt-{{ $olt->id }}" type="checkbox" name="is_active" value="1" @checked($olt->is_active) class="h-4 w-4 accent-emerald-600"> {{ $olt->is_active ? 'Aktif' : 'Nonaktif' }}</label><div class="mt-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $olt->status === 'online' ? 'bg-emerald-50 text-emerald-700' : ($olt->status === 'offline' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-500') }}">{{ strtoupper($olt->status) }}</span></div>@if($olt->last_checked_at)<div class="mt-2 text-xs text-slate-500">{{ $olt->last_checked_at->diffForHumans() }}</div>@endif @if($olt->last_error)<div class="mt-2 max-w-xs text-xs text-rose-600">{{ $olt->last_error }}</div>@endif</td>
            <td class="px-5 py-4"><div class="flex justify-end gap-2"><button form="update-olt-{{ $olt->id }}" class="jn-button jn-button-secondary">Simpan</button><button form="test-olt-{{ $olt->id }}" class="jn-button jn-button-secondary">Test</button><button form="delete-olt-{{ $olt->id }}" class="jn-button jn-button-danger" onclick="return confirm('Hapus OLT ini?')">Hapus</button></div></td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Belum ada OLT. Tambahkan perangkat menggunakan form di atas.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
<div class="mt-5">{{ $olts->links() }}</div>
<script>
document.querySelectorAll('select[name="connection_type"]').forEach((select) => {
    select.addEventListener('change', (event) => {
        const container = event.target.closest('form') || event.target.closest('td');
        const port = container?.querySelector('input[name="port"]');
        const transport = container?.querySelector('select[name="transport_type"]');
        if (port) {
            port.value = event.target.value === 'snmp' ? 161 : 22;
        }
        if (transport) {
            transport.value = event.target.value === 'snmp' ? 'udp' : 'tcp';
        }
    });
});
</script>
</x-layouts.dashboard>
