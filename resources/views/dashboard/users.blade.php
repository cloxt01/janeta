<x-layouts.dashboard title="User & Admin">

    @php
        $totalUsers = $users->total();
        $pageUsers = $users->count();
//        $adminUsers = $users->getCollection()->where('is_admin', true)->count();


    @endphp

    <style>
        .users-page .eyebrow {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .usr-card,
        .usr-toolbar,
        .usr-add {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 1px 2px rgb(15 23 42 / 4%);
        }

        .usr-stat {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            padding: 18px 20px;
            box-shadow: 0 1px 2px rgb(15 23 42 / 4%);
        }

        .usr-add {
            padding: 18px;
        }

        .usr-add summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            color: #1e293b;
            font-size: 13px;
            font-weight: 650;
            list-style: none;
        }

        .usr-add summary::-webkit-details-marker {
            display: none;
        }

        .usr-form {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }

        .usr-field label {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 10px;
            font-weight: 650;
        }

        .usr-form input:not([type="checkbox"]),
        .usr-form select,
        .usr-table input:not([type="checkbox"]),
        .usr-table select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #fff;
            padding: 9px 10px;
            color: #1e293b;
            font-size: 12px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .usr-form input:focus,
        .usr-form select:focus,
        .usr-table input:focus,
        .usr-table select:focus {
            border-color: #64748b;
            box-shadow: 0 0 0 3px rgb(100 116 139 / 11%);
        }

        .usr-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 15px 18px;
        }

        .usr-toolbar-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 650;
        }

        .usr-toolbar-meta {
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }

        .usr-table {
            border-collapse: collapse;
        }

        .usr-table th {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 12px 16px;
            white-space: nowrap;
        }

        .usr-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 16px;
        }

        .usr-table tr:last-child td {
            border-bottom: none;
        }

        .usr-role {
            display: inline-flex;
            align-items: center;
            border-radius: 6px;
            padding: 5px 9px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .usr-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 7px;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 650;
            cursor: pointer;
            transition: all .2s;
            white-space: nowrap;
        }

        .usr-save {
            background: #0f172a;
            color: #fff;
        }

        .usr-save:hover {
            background: #334155;
        }
        .usr-reset {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #2563eb;
        }

        .usr-reset:hover {
            background: #dbeafe;
        }

        .usr-delete {
            border: 1px solid #fecaca;
            background: #fff;
            color: #dc2626;
        }

        .usr-delete:hover {
            background: #fef2f2;
        }

        .usr-empty {
            padding: 48px 20px !important;
            text-align: center;
        }

        @media (min-width: 768px) {
            .usr-form {
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }
        }
        .usr-table td {
            vertical-align: middle;
        }

        .usr-table td:nth-child(3) {
            min-width: 280px;
        }

        .usr-table td:nth-child(4) {
            min-width: 450px;
        }

        .usr-action {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .usr-action input {
            min-width: 150px;
            flex: 1;
        }

        .usr-save,
        .usr-delete {
            flex-shrink: 0;
            white-space: nowrap;
        }
    </style>

    <div class="users-page space-y-6">

        {{-- PAGE HEADER --}}
        <div>
            <p class="eyebrow mb-2">Access Management</p>

            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                User & Admin Dashboard
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Kelola akun pengguna, hak akses, dan administrator
                yang dapat masuk ke panel JANETA.
            </p>
        </div>

        {{-- STATISTICS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            {{-- TOTAL USERS --}}
            <div class="usr-stat">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Total Users
                    </p>

                    <span class="rounded-lg bg-blue-50 p-2 text-blue-600">
                        <i data-lucide="users" class="h-5 w-5"></i>
                    </span>
                </div>

                <div class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ $totalUsers }}
                </div>

                <p class="mt-1 text-xs text-slate-400">
                    Akun terdaftar
                </p>
            </div>

            {{-- ADMINISTRATORS --}}
{{--            <div class="usr-stat">--}}
{{--                <div class="flex items-center justify-between">--}}
{{--                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">--}}
{{--                        Administrators--}}
{{--                    </p>--}}

{{--                    <span class="rounded-lg bg-amber-50 p-2 text-amber-600">--}}
{{--                        <i data-lucide="shield-check" class="h-5 w-5"></i>--}}
{{--                    </span>--}}
{{--                </div>--}}

{{--                <div class="mt-2 text-2xl font-semibold text-slate-900">--}}
{{--                    {{ $adminUsers }}--}}
{{--                </div>--}}

{{--                <p class="mt-1 text-xs text-slate-400">--}}
{{--                    Admin pada halaman ini--}}
{{--                </p>--}}
{{--            </div>--}}

            {{-- DISPLAYED --}}
            <div class="usr-stat">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Data Ditampilkan
                    </p>

                    <span class="rounded-lg bg-emerald-50 p-2 text-emerald-600">
                        <i data-lucide="list" class="h-5 w-5"></i>
                    </span>
                </div>

                <div class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ $pageUsers }}
                </div>

                <p class="mt-1 text-xs text-slate-400">
                    Dari {{ $totalUsers }} akun
                </p>
            </div>

        </div>

        {{-- FLASH MESSAGES --}}
        @if(session('success'))
            <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700">
                <i data-lucide="check-circle" class="h-4 w-4"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
                <i data-lucide="alert-circle" class="h-4 w-4"></i>
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-700">
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

        {{-- ADD USER --}}
        <details class="usr-add" {{ $errors->any() ? 'open' : '' }}>

            <summary>
                <span class="flex items-center gap-2">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Tambah pengguna dashboard
                </span>

                <i data-lucide="chevron-down" class="h-4 w-4"></i>
            </summary>

            <form
                method="POST"
                action="{{ route('users.store') }}"
                class="usr-form"
            >
                @csrf

                <div class="usr-field md:col-span-3">
                    <label>
                        Nama <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Nama pengguna"
                        required
                    >
                </div>

                <div class="usr-field md:col-span-3">
                    <label>
                        Email <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="user@example.com"
                        required
                    >
                </div>

                <div class="usr-field md:col-span-2">
                    <label>
                        Password <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        required
                    >
                </div>

                <div class="usr-field md:col-span-2">
                    <label>
                        Role <span class="text-rose-500">*</span>
                    </label>

                    <select name="role_id" required>
                        <option value="">Pilih Role</option>

                        @foreach($roles as $role)
                            <option
                                value="{{ $role->id }}"
                                @selected(old('role_id') == $role->id)
                            >
                                {{ $role->display_name ?: $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

{{--                <div class="flex items-end md:col-span-1">--}}
{{--                    <label class="flex min-h-[36px] cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600">--}}
{{--                        <input--}}
{{--                            type="checkbox"--}}
{{--                            name="is_admin"--}}
{{--                            value="1"--}}
{{--                            @checked(old('is_admin'))--}}
{{--                            class="h-4 w-4 rounded accent-slate-700"--}}
{{--                        >--}}

{{--                        Admin--}}
{{--                    </label>--}}
{{--                </div>--}}

                <div class="flex items-end md:col-span-1">
                    <button type="submit" class="jn-button w-full">
                        <i data-lucide="plus" class="mr-1 inline h-4 w-4"></i>
                        Tambah
                    </button>
                </div>

            </form>
        </details>

        {{-- TOOLBAR --}}
        <div class="usr-toolbar">
            <div>
                <div class="usr-toolbar-title">
                    Registered Users
                </div>

                <div class="usr-toolbar-meta">
                    {{ $totalUsers }} akun terdaftar
                </div>
            </div>

            <i data-lucide="users" class="h-5 w-5 text-slate-400"></i>
        </div>

        {{-- USER TABLE --}}
        <div class="usr-card">

            <div class="overflow-x-auto">

                <table class="usr-table w-full text-left">

                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role & Akses</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($users as $user)

                            <tr class="transition-colors hover:bg-slate-50/70">

                                {{-- USER NAME --}}
                                <td class="min-w-[180px]">
                                    <input
                                        form="update-user-{{ $user->id }}"
                                        type="text"
                                        name="name"
                                        value="{{ $user->name }}"
                                        required
                                    >
                                </td>

                                {{-- EMAIL --}}
                                <td class="min-w-[220px]">
                                    <input
                                        form="update-user-{{ $user->id }}"
                                        type="email"
                                        name="email"
                                        value="{{ $user->email }}"
                                        required
                                    >
                                </td>

                                {{-- ROLE --}}
                                <td class="min-w-[190px]">

                                    <select
                                        form="update-user-{{ $user->id }}"
                                        name="role_id"
                                        required
                                    >
                                        <option value="">Pilih Role</option>

                                        @foreach($roles as $role)
                                            <option
                                                value="{{ $role->id }}"
                                                @selected($user->roles->contains('id', $role->id))
                                            >
                                                {{ $role->display_name ?: $role->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                </td>


                                {{-- ACTIONS --}}
                                <td class="min-w-[260px]">

                                    <div class="flex items-center justify-end gap-2 flex-wrap">

                                        {{-- UPDATE FORM --}}
                                        <form
                                            id="update-user-{{ $user->id }}"
                                            method="POST"
                                            action="{{ route('users.update', $user) }}"
                                        >
                                            @csrf
                                            @method('PUT')
                                        </form>

                                        {{-- SIMPAN --}}
                                        <button
                                            type="submit"
                                            form="update-user-{{ $user->id }}"
                                            class="usr-action usr-save"
                                        >
                                            <i data-lucide="save" class="h-3.5 w-3.5"></i>
                                            Simpan
                                        </button>

                                        {{-- RESET PASSWORD --}}
                                        <button
                                            type="button"
                                            onclick="openResetPassword(
                '{{ $user->id }}',
                @js($user->name),
                @js($user->email)
            )"
                                            class="usr-action usr-reset"
                                        >
                                            <i data-lucide="key-round" class="h-3.5 w-3.5"></i>
                                            Reset Password
                                        </button>

                                        {{-- DELETE FORM --}}
                                        <form
                                            method="POST"
                                            action="{{ route('users.destroy', $user) }}"
                                            onsubmit="return confirm('Yakin ingin menghapus akun {{ addslashes($user->name) }}?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="usr-action usr-delete"
                                            >
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                Hapus
                                            </button>
                                        </form>

                                    </div>

                                </td>


                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="usr-empty">
                                    <div class="flex flex-col items-center justify-center gap-3">

                                        <span class="rounded-xl bg-slate-100 p-4 text-slate-400">
                                            <i data-lucide="users" class="h-7 w-7"></i>
                                        </span>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-700">
                                                Belum ada pengguna
                                            </p>

                                            <p class="mt-1 text-xs text-slate-400">
                                                Tambahkan akun untuk mulai mengelola akses dashboard JANETA.
                                            </p>
                                        </div>

                                    </div>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- PAGINATION --}}
            @if($users->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $users->links() }}
                </div>
            @endif

        </div>

    </div>



    {{-- RESET PASSWORD MODAL --}}
    <div
        id="resetPasswordModal"
        class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
    >
        <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">

            {{-- HEADER --}}
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-blue-50 p-2 text-blue-600">
                        <i data-lucide="key-round" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">
                            Reset Password
                        </h3>
                        <p class="text-xs text-slate-500">
                            Konfirmasi pengaturan ulang password
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="closeResetPassword()"
                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            {{-- BODY --}}
            <div class="space-y-4 p-5">

                <div class="rounded-lg border border-blue-100 bg-blue-50/70 p-4">
                    <p class="text-xs font-semibold text-blue-800">
                        Informasi akun
                    </p>

                    <p id="resetUserName" class="mt-2 text-sm font-semibold text-slate-800"></p>

                    <p id="resetUserEmail" class="mt-1 text-xs text-slate-500"></p>
                </div>

                <div>
                    <p class="text-sm font-medium text-slate-700">
                        Apakah Anda yakin ingin mereset password user ini?
                    </p>

                    <p class="mt-2 text-xs leading-relaxed text-slate-500">
                        Password akun akan dikembalikan ke password default:
                    </p>

                    <div class="mt-3 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <span class="text-xs font-medium text-slate-500">
                        Password Default
                    </span>

                        <span class="font-mono text-sm font-bold tracking-wider text-slate-800">
                        12345678
                    </span>
                    </div>
                </div>

            </div>

            {{-- FOOTER --}}
            <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">

                <button
                    type="button"
                    onclick="closeResetPassword()"
                    class="usr-action border border-slate-200 bg-white text-slate-600 hover:bg-slate-100"
                >
                    Batal
                </button>

                <form
                    id="resetPasswordForm"
                    method="POST"
                    action=""
                >
                    @csrf

                    <button
                        type="submit"
                        class="usr-action usr-reset"
                        onclick="return confirm('Yakin reset password user ini ke 12345678?')"
                    >
                        <i data-lucide="key-round" class="h-3.5 w-3.5"></i>
                        Ya, Reset Password
                    </button>
                </form>

            </div>

        </div>
    </div>

    <script>

        function openResetPassword(id, name, email) {
            const modal = document.getElementById('resetPasswordModal');
            const form = document.getElementById('resetPasswordForm');

            form.action = `/admin/users/${id}/password`;

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
            .addEventListener('click', function(event) {
                if (event.target === this) {
                    closeResetPassword();
                }
            });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeResetPassword();
            }
        });

    </script>
</x-layouts.dashboard>
