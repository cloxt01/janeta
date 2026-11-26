<x-layouts.dashboard title="Telegram Whitelist">

    @php
        $totalEntries = $entries->total();
        $pageEntries = $entries->count();
        $adminEntries = $entries->getCollection()
            ->filter(fn ($e) => $e->isAdmin())
            ->count();

        $field = 'h-9 w-full rounded-full border border-[#2e2e2e] bg-[#202020] px-4 text-xs text-[#e5e5e5] outline-none transition placeholder:text-[#777] focus:border-[#5a5a5a]';
    @endphp

    {{-- PAGE HEADER --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
                Configuration · Telegram
            </div>

            <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
                Telegram <span class="text-[#8a8a8a]">whitelist.</span>
            </h1>

            <p class="mt-2.5 max-w-xl text-xs text-[#a0a0a0]">
                {{ $totalEntries }} chat terdaftar · kelola akses pengguna dan hak administrator bot Telegram JANETA
            </p>
        </div>

        <button type="button" id="toggleAddWhitelist"
                class="h-9 rounded-full bg-[#0b8a3e] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#0da048]">
            Tambah whitelist
        </button>
    </div>

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
        <div class="mb-5 flex items-center gap-2 rounded-2xl border border-[#1f5a38] bg-[#14281d] px-5 py-3 text-xs text-[#4cd286]">
            <i data-lucide="check-circle" class="h-4 w-4"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 flex items-center gap-2 rounded-2xl border border-[#5e2b2b] bg-[#2a1717] px-5 py-3 text-xs text-[#f08a8a]">
            <i data-lucide="alert-circle" class="h-4 w-4"></i>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-2xl border border-[#5e2b2b] bg-[#2a1717] px-5 py-3 text-xs text-[#f08a8a]">
            <div class="mb-2 flex items-center gap-2 font-semibold">
                <i data-lucide="alert-circle" class="h-4 w-4"></i>
                Terjadi kesalahan
            </div>

            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- STAT STRIP --}}
    <div class="mb-5 grid grid-cols-1 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] sm:grid-cols-3">
        <div class="border-b border-[#2e2e2e] px-5 py-4 sm:border-b-0 sm:border-r">
            <div class="text-[11px] text-[#8a8a8a]">Total whitelist</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $totalEntries }}</div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">chat terdaftar</div>
        </div>

        <div class="border-b border-[#2e2e2e] px-5 py-4 sm:border-b-0 sm:border-r">
            <div class="text-[11px] text-[#8a8a8a]">Administrator</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $adminEntries }}</div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">admin pada halaman ini</div>
        </div>

        <div class="px-5 py-4">
            <div class="text-[11px] text-[#8a8a8a]">Data ditampilkan</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $pageEntries }}</div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">dari {{ $totalEntries }} data</div>
        </div>
    </div>

    {{-- ADD WHITELIST PANEL --}}
    <div id="addWhitelistPanel"
         class="{{ $errors->any() ? '' : 'hidden' }} mb-5 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

        <div class="border-b border-[#2e2e2e] px-5 py-4">
            <h2 class="text-sm font-semibold text-[#f2f2f2]">Tambah whitelist Telegram</h2>
            <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Chat ID yang terdaftar dapat berinteraksi dengan bot JANETA</p>
        </div>

        <form method="POST" action="{{ route('telegram.whitelist.store') }}"
              class="grid grid-cols-1 gap-4 p-5 md:grid-cols-12">
            @csrf

            <div class="md:col-span-4">
                <label class="mb-1.5 block text-[11px] text-[#8a8a8a]">Chat ID <span class="text-[#f08a8a]">*</span></label>
                <input type="text" name="chat_id" value="{{ old('chat_id') }}" placeholder="Contoh: 123456789" required class="{{ $field }}">
            </div>

            <div class="md:col-span-4">
                <label class="mb-1.5 block text-[11px] text-[#8a8a8a]">Nama / Grup</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: NOC Team" class="{{ $field }}">
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-[11px] text-[#8a8a8a]">Hak akses <span class="text-[#f08a8a]">*</span></label>
                <select name="role" required class="{{ $field }}">
                    <option value="user" @selected(old('role', 'user') === 'user')>User</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                </select>
            </div>

            <div class="flex items-end md:col-span-2">
                <button type="submit"
                        class="h-9 w-full rounded-full bg-[#0b8a3e] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#0da048]">
                    Tambah
                </button>
            </div>
        </form>
    </div>

    {{-- WHITELIST TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2e2e2e] px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-[#f2f2f2]">Registered whitelist</h2>
                <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Ubah data langsung pada baris, lalu klik Simpan</p>
            </div>

            <span class="rounded-full border border-[#3a3a3a] bg-[#242424] px-3 py-1 text-[11px] font-medium text-[#c4c4c4]">
                {{ $totalEntries }} chat
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">

                <thead class="border-b border-[#2e2e2e] text-[10px] font-semibold uppercase tracking-[0.1em] text-[#8a8a8a]">
                <tr>
                    <th class="px-5 py-3.5">Chat ID</th>
                    <th class="px-5 py-3.5">Nama / Grup</th>
                    <th class="px-5 py-3.5">Hak akses</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-[#272727]">

                @forelse($entries as $entry)

                    <tr class="transition-colors hover:bg-[#232323]">

                        {{-- CHAT ID --}}
                        <td class="min-w-[220px] px-5 py-3">
                            <input form="update-{{ $entry->id }}" type="text" name="chat_id"
                                   value="{{ $entry->chat_id }}" required class="{{ $field }} font-mono">
                        </td>

                        {{-- NAME --}}
                        <td class="min-w-[240px] px-5 py-3">
                            <input form="update-{{ $entry->id }}" type="text" name="name"
                                   value="{{ $entry->name }}" placeholder="Belum ada nama" class="{{ $field }}">
                        </td>

                        {{-- ROLE --}}
                        <td class="min-w-[200px] px-5 py-3">
                            <div class="flex items-center gap-3">

                                @if($entry->isAdmin())
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full border border-[#1f5a38] bg-[#14281d] px-2.5 py-1 text-[10px] font-semibold text-[#4cd286]">
                                        <i data-lucide="shield-check" class="h-3 w-3"></i>
                                        Admin
                                    </span>
                                @else
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full border border-[#3a3a3a] bg-[#242424] px-2.5 py-1 text-[10px] font-semibold text-[#a0a0a0]">
                                        <i data-lucide="user" class="h-3 w-3"></i>
                                        User
                                    </span>
                                @endif

                                <select form="update-{{ $entry->id }}" name="role" class="{{ $field }}">
                                    <option value="user" @selected(! $entry->isAdmin())>User</option>
                                    <option value="admin" @selected($entry->isAdmin())>Admin</option>
                                </select>

                            </div>
                        </td>

                        {{-- ACTIONS --}}
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap items-center justify-end gap-2">

                                <form id="update-{{ $entry->id }}" method="POST"
                                      action="{{ route('telegram.whitelist.update', $entry) }}">
                                    @csrf
                                    @method('PUT')
                                </form>

                                <button type="submit" form="update-{{ $entry->id }}"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-full bg-[#0b8a3e] px-4 text-[11px] font-semibold text-white transition-colors hover:bg-[#0da048]">
                                    <i data-lucide="save" class="h-3.5 w-3.5"></i>
                                    Simpan
                                </button>

                                <form method="POST" action="{{ route('telegram.whitelist.destroy', $entry) }}"
                                      onsubmit="return confirm('Yakin ingin menghapus whitelist ini?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="inline-flex h-8 items-center gap-1.5 rounded-full border border-[#5e2b2b] bg-[#2a1717] px-4 text-[11px] font-semibold text-[#f08a8a] transition-colors hover:bg-[#361d1d]">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        Hapus
                                    </button>
                                </form>

                            </div>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="mb-3 grid h-11 w-11 place-items-center rounded-full border border-[#3a3a3a] bg-[#242424] text-[#777]">
                                    <i data-lucide="inbox" class="h-5 w-5"></i>
                                </div>
                                <p class="text-sm font-medium text-[#d0d0d0]">Belum ada whitelist</p>
                                <p class="mt-1 text-xs text-[#8a8a8a]">
                                    Tambahkan Chat ID Telegram untuk mulai mengelola akses bot.
                                </p>
                            </div>
                        </td>
                    </tr>

                @endforelse

                </tbody>
            </table>
        </div>

        {{-- FOOTER --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#2e2e2e] px-5 py-4">
            <div class="text-xs text-[#8a8a8a]">
                Menampilkan
                <span class="font-semibold text-[#f2f2f2]">{{ $entries->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-semibold text-[#f2f2f2]">{{ $entries->lastItem() ?? 0 }}</span>
                dari
                <span class="font-semibold text-[#f2f2f2]">{{ $totalEntries }}</span>
                data
            </div>

            @if($entries->hasPages())
                <div class="jn-pagination">
                    {{ $entries->onEachSide(1)->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
    </div>

    <script>
        // Panel tambah whitelist
        document.getElementById('toggleAddWhitelist').addEventListener('click', function () {
            document.getElementById('addWhitelistPanel').classList.toggle('hidden');
        });
    </script>

</x-layouts.dashboard>
