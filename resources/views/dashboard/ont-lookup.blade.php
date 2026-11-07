<x-layouts.dashboard title="Cari ONT">
<div class="mb-6"><p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-500">Operasional</p><h1 class="text-3xl font-bold">Cari Detail ONT</h1><p class="mt-1 text-slate-400">Tarik status, lokasi, serial number, dan informasi jaringan dari OLT.</p></div>
<form method="get" action="{{ route('ont.lookup') }}" class="mb-6 flex max-w-2xl gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><input name="id" value="{{ $id }}" placeholder="Masukkan ID customer" required class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-cyan-400"><button class="jn-button">Cari</button>@if($result)<button name="wifi" value="1" class="jn-button jn-button-secondary">Cek Wi‑Fi</button>@endif</form>
@if($error)<div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">{{ $error }}</div>@endif
@if($result)<div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach([['runState', 'Status ONT'], ['rxPower', 'RX Power'], ['txPower', 'TX Power'], ['oltRxPower', 'OLT RX Power'], ['temp', 'Suhu'], ['voltage', 'Voltage'], ['wanIp', 'WAN IP'], ['wanStatus', 'WAN Status']] as [$key, $label])
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-lg font-bold {{ $key === 'runState' && strtolower($result[$key] ?? '') === 'online' ? 'text-emerald-600' : 'text-slate-800' }}">{{ $result[$key] ?? '-' }}{{ in_array($key, ['rxPower', 'txPower', 'oltRxPower']) && ($result[$key] ?? '-') !== '-' ? ' dBm' : '' }}</p></div>
    @endforeach
</div>
<div class="grid gap-5 md:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 font-semibold text-slate-700">Lokasi ONT</h2><dl class="space-y-3 text-sm">@foreach(['idcust' => 'ID Customer', 'frame' => 'Frame', 'slot' => 'Slot', 'port' => 'Port', 'ontId' => 'ONT ID'] as $key => $label)<div class="flex justify-between border-b border-slate-100 pb-2"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-medium text-slate-800">{{ $result[$key] ?? '-' }}</dd></div>@endforeach</dl></section>
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="mb-4 font-semibold text-slate-700">Spesifikasi</h2><dl class="space-y-3 text-sm">@foreach(['sn' => 'Serial Number', 'brand' => 'Merek', 'type' => 'Tipe', 'wlanType' => 'WLAN Band'] as $key => $label)<div class="flex justify-between gap-4 border-b border-slate-100 pb-2"><dt class="text-slate-500">{{ $label }}</dt><dd class="text-right font-medium text-slate-800">{{ $result[$key] ?? '-' }}</dd></div>@endforeach</dl></section>
</div>@endif
@if($result && request()->boolean('wifi'))
<section class="mt-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between"><div><h2 class="font-semibold text-slate-700">Wi‑Fi / WLAN ONT</h2><p class="mt-1 text-xs text-slate-500">Data SSID diambil langsung dari OLT melalui SSH.</p></div><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ count($result['wlan']['ssids'] ?? []) }} SSID</span></div>
    @if(empty($result['wlan']['ssids']))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Tidak ada SSID yang terbaca. Periksa output command Huawei atau lihat raw response di log aplikasi.</div>
    @else
        <div class="grid gap-3 md:grid-cols-2">@foreach($result['wlan']['ssids'] as $ssid)<div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">SSID {{ $ssid['index'] }}</span><span class="rounded-full bg-emerald-50 px-2 py-1 text-xs text-emerald-700">{{ $ssid['opState'] }}</span></div><p class="mt-3 text-lg font-semibold text-slate-800">{{ $ssid['name'] }}</p><dl class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-500"><div>Admin: <b class="text-slate-700">{{ $ssid['adminState'] }}</b></div><div>Perangkat: <b class="text-slate-700">{{ $ssid['deviceCount'] }}/{{ $ssid['deviceMax'] }}</b></div></dl></div>@endforeach</div>
    @endif
</section>
@endif
</x-layouts.dashboard>
