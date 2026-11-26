<x-layouts.dashboard title="User & Admin">

    @php
        $totalUsers = $users->total();
        $pageUsers = $users->count();
        $totalRoles = $roles->count();

        $field = 'h-9 w-full rounded-full border border-[#2e2e2e] bg-[#202020] px-4 text-xs text-[#e5e5e5] outline-none transition placeholder:text-[#777] focus:border-[#5a5a5a]';
    @endphp

    {{-- PAGE HEADER --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <div class="mb-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]">
                Management · Pengguna
            </div>

            <h1 class="text-[26px] font-semibold leading-none tracking-tight text-[#f2f2f2]">
                Dashboard <span class="text-[#8a8a8a]">users.</span>
            </h1>

            <p class="mt-2.5 max-w-xl text-xs text-[#a0a0a0]">
                {{ $totalUsers }} akun terdaftar · kelola akun, hak akses, dan administrator panel JANETA
            </p>
        </div>

        <button type="button" id="toggleAddUser"
                class="h-9 rounded-full bg-[#0b8a3e] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#0da048]">
            Tambah pengguna
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
            <div class="text-[11px] text-[#8a8a8a]">Total users</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $totalUsers }}</div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">akun terdaftar</div>
        </div>

        <div class="border-b border-[#2e2e2e] px-5 py-4 sm:border-b-0 sm:border-r">
            <div class="text-[11px] text-[#8a8a8a]">Data ditampilkan</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $pageUsers }}</div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">dari {{ $totalUsers }} akun</div>
        </div>

        <div class="px-5 py-4">
            <div class="text-[11px] text-[#8a8a8a]">Role tersedia</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-[#f2f2f2]">{{ $totalRoles }}</div>
            <div class="mt-1 text-[11px] text-[#8a8a8a]">dapat ditetapkan ke pengguna</div>
        </div>
    </div>

    {{-- ADD USER PANEL --}}
    <div id="addUserPanel"
         class="{{ $errors->any() ? '' : 'hidden' }} mb-5 overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

        <div class="border-b border-[#2e2e2e] px-5 py-4">
            <h2 class="text-sm font-semibold text-[#f2f2f2]">Tambah pengguna dashboard</h2>
            <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Akun baru dapat langsung masuk ke panel JANETA</p>
        </div>

        <form method="POST" action="{{ route('users.store') }}"
              class="grid grid-cols-1 gap-4 p-5 md:grid-cols-12">
            @csrf

            <div class="md:col-span-3">
                <label class="mb-1.5 block text-[11px] text-[#8a8a8a]">Nama <span class="text-[#f08a8a]">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama pengguna" required class="{{ $field }}">
            </div>

            <div class="md:col-span-3">
                <label class="mb-1.5 block text-[11px] text-[#8a8a8a]">Email <span class="text-[#f08a8a]">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="user@example.com" required class="{{ $field }}">
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-[11px] text-[#8a8a8a]">Password <span class="text-[#f08a8a]">*</span></label>
                <input type="password" name="password" placeholder="Minimal 8 karakter" required class="{{ $field }}">
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-[11px] text-[#8a8a8a]">Role <span class="text-[#f08a8a]">*</span></label>
                <select name="role_id" required class="{{ $field }}">
                    <option value="">Pilih role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                            {{ $role->display_name ?: $role->name }}
                        </option>
                    @endforeach
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

    {{-- USER TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c]">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2e2e2e] px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-[#f2f2f2]">Registered users</h2>
                <p class="mt-0.5 text-[11px] text-[#8a8a8a]">Ubah data langsung pada baris, lalu klik Simpan</p>
            </div>

            <span class="rounded-full border border-[#3a3a3a] bg-[#242424] px-3 py-1 text-[11px] font-medium text-[#c4c4c4]">
                {{ $totalUsers }} akun
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">

                <thead class="border-b border-[#2e2e2e] text-[10px] font-semibold uppercase tracking-[0.1em] text-[#8a8a8a]">
                <tr>
                    <th class="px-5 py-3.5">Nama</th>
                    <th class="px-5 py-3.5">Email</th>
                    <th class="px-5 py-3.5">Role &amp; akses</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-[#272727]">

                @forelse($users as $user)

                    @php
                        $initials = strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $user->name), 0, 2)) ?: '--';
                    @endphp

                    <tr class="transition-colors hover:bg-[#232323]">

                        {{-- NAME --}}
                        <td class="min-w-[220px] px-5 py-3">
                            <div class="flex items-center gap-3">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#2c2c2c] text-[10px] font-semibold text-[#bbb]">
                                    {{ $initials }}
                                </span>

                                <input form="update-user-{{ $user->id }}" type="text" name="name"
                                       value="{{ $user->name }}" required class="{{ $field }}">
                            </div>
                        </td>

                        {{-- EMAIL --}}
                        <td class="min-w-[240px] px-5 py-3">
                            <input form="update-user-{{ $user->id }}" type="email" name="email"
                                   value="{{ $user->email }}" required class="{{ $field }}">
                        </td>

                        {{-- ROLE --}}
                        <td class="min-w-[200px] px-5 py-3">
                            <select form="update-user-{{ $user->id }}" name="role_id" required class="{{ $field }}">
                                <option value="">Pilih role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected($user->roles->contains('id', $role->id))>
                                        {{ $role->display_name ?: $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- ACTIONS --}}
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap items-center justify-end gap-2">

                                <form id="update-user-{{ $user->id }}" method="POST"
                                      action="{{ route('users.update', $user) }}">
                                    @csrf
                                    @method('PUT')
                                </form>

                                <button type="submit" form="update-user-{{ $user->id }}"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-full bg-[#0b8a3e] px-4 text-[11px] font-semibold text-white transition-colors hover:bg-[#0da048]">
                                    <i data-lucide="save" class="h-3.5 w-3.5"></i>
                                    Simpan
                                </button>

                                <button type="button"
                                        onclick="openResetPassword('{{ $user->id }}', @js($user->name), @js($user->email))"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-full border border-[#3a3a3a] px-4 text-[11px] font-semibold text-[#d0d0d0] transition-colors hover:bg-[#2a2a2a]">
                                    <i data-lucide="key-round" class="h-3.5 w-3.5"></i>
                                    Reset password
                                </button>

                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                      onsubmit="return confirm('Yakin ingin menghapus akun {{ addslashes($user->name) }}?')">
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
                                    <i data-lucide="users" class="h-5 w-5"></i>
                                </div>
                                <p class="text-sm font-medium text-[#d0d0d0]">Belum ada pengguna</p>
                                <p class="mt-1 text-xs text-[#8a8a8a]">
                                    Tambahkan akun untuk mulai mengelola akses dashboard JANETA.
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
                <span class="font-semibold text-[#f2f2f2]">{{ $users->firstItem() ?? 0 }}</span>
                sampai
                <span class="font-semibold text-[#f2f2f2]">{{ $users->lastItem() ?? 0 }}</span>
                dari
                <span class="font-semibold text-[#f2f2f2]">{{ $totalUsers }}</span>
                akun
            </div>

            @if($users->hasPages())
                <div class="jn-pagination">
                    {{ $users->onEachSide(1)->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
    </div>

    {{-- RESET PASSWORD MODAL --}}
    <div id="resetPasswordModal"
         class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/65 p-4 backdrop-blur-sm">

        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-[#2e2e2e] bg-[#1c1c1c] shadow-2xl">

            <div class="flex items-center justify-between border-b border-[#2e2e2e] px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-9 w-9 place-items-center rounded-full bg-[#2c2c2c] text-[#bbb]">
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-[#f2f2f2]">Reset password</h3>
                        <p class="text-[11px] text-[#8a8a8a]">Konfirmasi pengaturan ulang password</p>
                    </div>
                </div>

                <button type="button" onclick="closeResetPassword()"
                        class="grid h-8 w-8 place-items-center rounded-full text-[#8a8a8a] transition hover:bg-[#2a2a2a] hover:text-white">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="space-y-4 p-5">

                <div class="rounded-xl border border-[#2e2e2e] bg-[#202020] p-4">
                    <p class="text-[11px] text-[#8a8a8a]">Informasi akun</p>
                    <p id="resetUserName" class="mt-1.5 text-sm font-semibold text-[#f2f2f2]"></p>
                    <p id="resetUserEmail" class="mt-0.5 text-xs text-[#8a8a8a]"></p>
                </div>

                <div>
                    <p class="text-sm font-medium text-[#e5e5e5]">
                        Apakah Anda yakin ingin mereset password user ini?
                    </p>

                    <p class="mt-2 text-xs leading-relaxed text-[#8a8a8a]">
                        Password akun akan dikembalikan ke password default:
                    </p>

                    <div class="mt-3 flex items-center justify-between rounded-xl border border-[#2e2e2e] bg-[#202020] px-4 py-3">
                        <span class="text-xs text-[#8a8a8a]">Password default</span>
                        <span class="font-mono text-sm font-bold tracking-wider text-[#f2f2f2]">12345678</span>
                    </div>
                </div>

            </div>

            <div class="flex justify-end gap-2 border-t border-[#2e2e2e] px-5 py-4">

                <button type="button" onclick="closeResetPassword()"
                        class="h-9 rounded-full border border-[#3a3a3a] px-5 text-xs font-semibold text-[#d0d0d0] transition-colors hover:bg-[#2a2a2a]">
                    Batal
                </button>

                <form id="resetPasswordForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="user_id" id="resetUserId" value="">

                    <button type="submit"
                            onclick="return confirm('Yakin reset password user ini ke 12345678?')"
                            class="inline-flex h-9 items-center gap-1.5 rounded-full bg-[#0b8a3e] px-5 text-xs font-semibold text-white transition-colors hover:bg-[#0da048]">
                        <i data-lucide="key-round" class="h-3.5 w-3.5"></i>
                        Ya, reset password
                    </button>
                </form>

            </div>

        </div>
    </div>

    <script>
        // Panel tambah pengguna
        document.getElementById('toggleAddUser').addEventListener('click', function () {
            document.getElementById('addUserPanel').classList.toggle('hidden');
        });

        function openResetPassword(id, name, email) {
            const modal = document.getElementById('resetPasswordModal');
            const form = document.getElementById('resetPasswordForm');

            form.action = `{{ route('admin.users.password.reset') }}`;
            document.getElementById('resetUserId').value = id;

            document.getElementById('resetUserName').textContent = name;
            document.getElementById('resetUserEmail').textContent = email;

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeResetPassword() {
            const modal = document.getElementById('resetPasswordModal');

            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('resetPasswordModal')
            .addEventListener('click', function (event) {
                if (event.target === this) {
                    closeResetPassword();
                }
            });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeResetPassword();
            }
        });
    </script>

</x-layouts.dashboard>
