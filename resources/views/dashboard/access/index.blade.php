
<x-layouts.dashboard title="Access Control">

    @php
        $totalPermissions = $permissions->flatten()->count();
        $totalUsers = $users->count();
        $superAdmin = $roles->firstWhere('name', 'super-admin');
    @endphp

    <style>
        /* =========================================================
           ACCESS CONTROL - LIGHT THEME (sama dengan halaman Pengguna)
        ========================================================= */

        .ac-page .ac-card,
        .ac-modal .ac-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            color: #0f172a;
        }

        /* Teks: pakai warna gelap, jangan text-white */
        .ac-page .ac-title      { color: #0f172a; }
        .ac-page .ac-muted      { color: #64748b; }
        .ac-page .ac-faint      { color: #94a3b8; }

        /* Role selector */
        .ac-page .role-selector {
            width: 100%;
            text-align: left;
            cursor: pointer;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            transition: all .2s ease;
        }
        .ac-page .role-selector:hover {
            background: #f8fafc;
            border-color: #10b981;
        }
        .ac-page .role-selector.active {
            background: #ecfdf5;
            border-color: #10b981;
            box-shadow: inset 0 0 0 1px rgba(16, 185, 129, .2);
        }

        /* Input */
        .ac-input {
            display: block;
            width: 100%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 10px 13px;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.5;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .ac-input::placeholder { color: #94a3b8; opacity: 1; }
        .ac-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, .15);
        }

        /* Button */
        .ac-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border-radius: 9px;
            padding: 9px 14px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.4;
            cursor: pointer;
            white-space: nowrap;
            transition: background .2s, border-color .2s;
        }
        .ac-btn:disabled { opacity: .5; cursor: not-allowed; }

        .ac-btn-primary {
            background: #0f172a;
            border: 1px solid #0f172a;
            color: #ffffff;
        }
        .ac-btn-primary:hover { background: #1e293b; }

        .ac-btn-secondary {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }
        .ac-btn-secondary:hover { background: #f1f5f9; }

        .ac-btn-danger {
            background: #ffffff;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        .ac-btn-danger:hover { background: #fef2f2; }

        /* Tabs */
        .ac-tab {
            cursor: pointer;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            border: 0;
            border-bottom: 2px solid transparent;
            background: transparent;
            white-space: nowrap;
            transition: color .2s, border-color .2s;
        }
        .ac-tab:hover { color: #0f172a; }
        .ac-tab.active {
            color: #059669;
            border-bottom-color: #10b981;
        }

        /* Checkbox */
        .ac-check {
            accent-color: #10b981;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .ac-check:disabled { cursor: not-allowed; opacity: .55; }

        /* Permission item */
        .ac-permission {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 13px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            color: #0f172a;
            cursor: pointer;
            transition: background .2s, border-color .2s;
        }
        .ac-permission:hover {
            background: #f8fafc;
            border-color: #10b981;
        }

        /* Badge */
        .ac-badge {
            display: inline-flex;
            align-items: center;
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 700;
            letter-spacing: .3px;
            white-space: nowrap;
        }
        .ac-badge-green { background: #d1fae5; color: #047857; }
        .ac-badge-gray  { background: #f1f5f9; color: #475569; }

        /* Table */
        .ac-page table { border-collapse: collapse; }
        .ac-page table thead { background: #f8fafc; }
        .ac-page table tbody tr:hover { background: #f8fafc; }

        /* Modal (dipindah ke body oleh JS) */
        .ac-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, .45);
            backdrop-filter: blur(4px);
        }
        .ac-modal.show { display: flex; }

        .ac-modal .ac-card {
            position: relative;
            width: 100%;
            max-width: 480px;
            padding: 24px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .25);
        }
        .ac-modal h3 { color: #0f172a; font-size: 16px; font-weight: 700; }
        .ac-modal label { color: #334155; }

        @media (max-width: 640px) {
            .ac-modal { padding: 12px; }
            .ac-modal .ac-card { padding: 19px; }
        }
    </style>

    <div class="ac-page">


        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-7">

            <div>
                <div class="flex items-center gap-2 text-emerald-400 text-xs font-semibold mb-2">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    SYSTEM SECURITY
                </div>

                <h1 class="text-2xl font-bold text-white">
                    Access Control
                </h1>

                <p class="text-sm text-slate-400 mt-1">
                    Kelola role, permission, dan hak akses pengguna JANETA.
                </p>
            </div>

            @if(auth()->user()->hasPermission('access.manage'))
                <button type="button" onclick="openRoleModal()"
                        class="ac-btn ac-btn-primary">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Tambah Role
                </button>
            @endif

        </div>


        {{-- STATISTICS --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-7">

            <div class="ac-card p-5">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-slate-400">Total Roles</span>
                    <i data-lucide="shield" class="w-5 h-5 text-emerald-400"></i>
                </div>

                <div class="text-3xl font-bold mt-3 text-white">
                    {{ $roles->count() }}
                </div>

                <p class="text-xs text-slate-500 mt-1">
                    Role terdaftar
                </p>
            </div>

            <div class="ac-card p-5">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-slate-400">Total Permissions</span>
                    <i data-lucide="key-round" class="w-5 h-5 text-blue-400"></i>
                </div>

                <div class="text-3xl font-bold mt-3 text-white">
                    {{ $totalPermissions }}
                </div>

                <p class="text-xs text-slate-500 mt-1">
                    Hak akses sistem
                </p>
            </div>

            <div class="ac-card p-5">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-slate-400">Total Users</span>
                    <i data-lucide="users" class="w-5 h-5 text-violet-400"></i>
                </div>

                <div class="text-3xl font-bold mt-3 text-white">
                    {{ $totalUsers }}
                </div>

                <p class="text-xs text-slate-500 mt-1">
                    Pengguna JANETA
                </p>
            </div>

        </div>


        {{-- TABS --}}
        <div class="border-b border-slate-700 mb-6 flex gap-2 overflow-x-auto">

            <button type="button" class="ac-tab active whitespace-nowrap"
                    data-tab="roles">
                <i data-lucide="shield" class="w-4 h-4 inline mr-1"></i>
                Role & Permissions
            </button>

            <button type="button" class="ac-tab whitespace-nowrap"
                    data-tab="users">
                <i data-lucide="users" class="w-4 h-4 inline mr-1"></i>
                User Assignment
            </button>

        </div>


        {{-- ========================================================= --}}
        {{-- ROLE & PERMISSIONS --}}
        {{-- ========================================================= --}}

        <section id="tab-roles">

            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-semibold text-white">
                        Role Management
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Pilih role untuk melihat dan mengatur permission.
                    </p>
                </div>
            </div>


            {{-- ROLE SELECTOR --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 mb-6">

                @foreach($roles as $role)
                    <button type="button"
                            class="ac-card p-4 text-left role-selector"
                            data-role="role-{{ $role->id }}"
                            onclick="selectRole('role-{{ $role->id }}')">

                        <div class="flex justify-between items-start">

                            <div class="flex items-center gap-3">

                                <div class="w-10 h-10 rounded-xl flex items-center justify-center
                                    {{ $role->name === 'super-admin'
                                        ? 'bg-emerald-100 text-emerald-600'
                                        : 'bg-slate-100 text-slate-500' }}">

                                    <i data-lucide="shield-check" class="w-5 h-5"></i>

                                </div>

                                <div>
                                    <div class="font-semibold text-sm text-white">
                                        {{ $role->display_name }}
                                    </div>

                                    <div class="text-xs text-slate-500 mt-1">
                                        {{ $role->users_count }} pengguna
                                    </div>
                                </div>

                            </div>

                            @if($role->name === 'super-admin')
                                <span class="ac-badge ac-badge-green">
                                    PROTECTED
                                </span>
                            @endif

                        </div>

                        <p class="text-xs text-slate-400 mt-3 line-clamp-2">
                            {{ $role->description ?: 'Tidak ada deskripsi.' }}
                        </p>

                        <div class="text-xs text-slate-500 mt-3">
                            {{ $role->permissions->count() }} permissions
                        </div>

                    </button>
                @endforeach

            </div>


            {{-- ROLE DETAILS --}}
            @foreach($roles as $role)

                <div id="role-{{ $role->id }}"
                     class="role-panel"
                     style="{{ $loop->first ? '' : 'display:none' }}">

                    <div class="ac-card overflow-hidden">

                        {{-- ROLE HEADER --}}
                        <div class="p-5 border-b border-slate-800 flex flex-col lg:flex-row lg:items-center justify-between gap-4">

                            <div>
                                <div class="flex items-center gap-2">

                                    <h3 class="text-lg font-bold text-white">
                                        {{ $role->display_name }}
                                    </h3>

                                    @if($role->name === 'super-admin')
                                        <span class="ac-badge ac-badge-green">
                                            SUPER ADMIN
                                        </span>
                                    @endif

                                </div>

                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $role->name }}
                                </p>

                            </div>


                            @if(auth()->user()->hasPermission('access.manage'))

                                @if($role->name !== 'super-admin')

                                    <div class="flex gap-2">

                                        <button type="button"
                                                class="ac-btn ac-btn-secondary"
                                                onclick="editRole(
                                                {{ $role->id }},
                                                @js($role->name),
                                                @js($role->display_name),
                                                @js($role->description)
                                            )">

                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                            Edit Role

                                        </button>
                                        <form method="POST"
                                              action="{{ route('access.roles.destroy', $role) }}"
                                              class="ac-delete-role-form"
                                              data-role-name="{{ $role->display_name }}">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="ac-btn ac-btn-danger">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                Hapus
                                            </button>
                                        </form>

                                    </div>

                                @endif

                            @endif

                        </div>


                        {{-- PERMISSION FORM --}}
                        <form method="POST"
                              action="{{ route('access.roles.permissions', $role) }}">

                            @csrf
                            @method('PUT')

                            <div class="p-5">

                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">

                                    <div>
                                        <h4 class="font-semibold text-sm text-white">
                                            Permissions
                                        </h4>

                                        <p class="text-xs text-slate-500 mt-1">
                                            Hak akses yang dimiliki role ini.
                                        </p>
                                    </div>

                                    @if($role->name === 'super-admin')
                                        <span class="text-xs text-emerald-400">
                                            <i data-lucide="lock" class="w-3.5 h-3.5 inline"></i>
                                            Seluruh permission aktif secara otomatis
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-500">
                                            {{ $role->permissions->count() }} /
                                            {{ $totalPermissions }} aktif
                                        </span>
                                    @endif

                                </div>


                                @foreach($permissions as $module => $modulePermissions)

                                    <div class="mb-6 last:mb-0">

                                        <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-3">

                                            <h5 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">
                                                {{ $module }}
                                            </h5>

                                            @if($role->name !== 'super-admin' && auth()->user()->hasPermission('access.manage'))
                                                <button type="button"
                                                        class="text-xs text-slate-500 hover:text-emerald-400"
                                                        onclick="toggleModule(this, '{{ $role->id }}-{{ $loop->index }}')">
                                                    Pilih Semua
                                                </button>
                                            @endif

                                        </div>


                                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">

                                            @foreach($modulePermissions as $permission)

                                                @php
                                                    $checked = $role->permissions->contains('id', $permission->id);
                                                @endphp

                                                <label class="ac-permission">

                                                    <div class="min-w-0">

                                                        <div class="text-xs font-medium ">
                                                            {{ $permission->display_name }}
                                                        </div>

                                                        <div class="text-[10px] text-slate-500 mt-1">
                                                            {{ $permission->name }}
                                                        </div>

                                                    </div>

                                                    <input
                                                        type="checkbox"
                                                        class="ac-check shrink-0 module-{{ $role->id }}-{{ $loop->parent->index }}"
                                                        name="permissions[]"
                                                        value="{{ $permission->id }}"
                                                        {{ $checked ? 'checked' : '' }}
                                                        {{ $role->name === 'super-admin' || !auth()->user()->hasPermission('access.manage') ? 'disabled' : '' }}
                                                    >

                                                    @if($role->name === 'super-admin')
                                                        {{-- Tidak dikirim, karena Super Admin dilindungi --}}
                                                    @endif

                                                </label>

                                            @endforeach

                                        </div>

                                    </div>

                                @endforeach

                            </div>


                            @if($role->name !== 'super-admin' && auth()->user()->hasPermission('access.manage'))

                                <div class="p-4 border-t border-slate-800 flex justify-end">

                                    <button type="submit" class="ac-btn ac-btn-primary">
                                        <i data-lucide="save" class="w-4 h-4"></i>
                                        Simpan Permissions
                                    </button>

                                </div>

                            @endif

                        </form>

                    </div>

                </div>

            @endforeach

        </section>


        {{-- ========================================================= --}}
        {{-- USER ASSIGNMENT --}}
        {{-- ========================================================= --}}

        <section id="tab-users" style="display:none">

            <div class="ac-card overflow-hidden">

                <div class="p-5 border-b border-slate-800">

                    <h3 class="font-semibold text-white">
                        User Role Assignment
                    </h3>

                    <p class="text-xs text-slate-400 mt-1">
                        Atur role yang dimiliki masing-masing pengguna JANETA.
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full text-sm">

                        <thead class="bg-slate-900/70 text-xs text-slate-400">

                        <tr>
                            <th class="text-left px-5 py-4 font-medium">Pengguna</th>
                            <th class="text-left px-5 py-4 font-medium">Role Saat Ini</th>
                            <th class="text-left px-5 py-4 font-medium">Pengaturan</th>
                        </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-800">

                        @forelse($users as $managedUser)

                            <tr class="">

                                <td class="px-5 py-4">

                                    <div class="font-medium text-white">
                                        {{ $managedUser->name }}
                                    </div>

                                    <div class="text-xs text-slate-500 mt-1">
                                        {{ $managedUser->email }}
                                    </div>

                                </td>


                                <td class="px-5 py-4">

                                    <div class="flex flex-wrap gap-1.5">

                                        @forelse($managedUser->roles as $userRole)

                                            <span class="ac-badge
                                                {{ $userRole->name === 'super-admin' ? 'ac-badge-green' : 'ac-badge-gray' }}">
                                                {{ $userRole->display_name ?: $userRole->name }}
                                            </span>
                                        @empty

                                            <span class="text-xs text-slate-500">
                                                    Belum memiliki role
                                                </span>

                                        @endforelse

                                    </div>

                                </td>


                                <td class="px-5 py-4">

                                    @if(auth()->user()->hasPermission('access.manage'))

                                        <button type="button"
                                                class="ac-btn ac-btn-secondary"
                                                onclick="openUserModal(
                                                    {{ $managedUser->id }},
                                                    @js($managedUser->name),
                                                    @js($managedUser->roles->pluck('id')->all())
                                                )">

                                            <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                                            Atur Role

                                        </button>

                                        {{-- PERMISSION KHUSUS --}}
                                        <button type="button"
                                                class="ac-btn ac-btn-secondary"
                                                onclick="openUserPermissionModal(
                                                {{ $managedUser->id }},
                                                @js($managedUser->name),
                                                @js($managedUser->directPermissions->mapWithKeys(fn ($permission) => [
                                                    $permission->id => $permission->pivot->effect
                                                ]))
                                            )">

                                            <i data-lucide="key-round" class="w-3.5 h-3.5"></i>
                                            Permission Khusus
                                        </button>

                                        {{-- USER DIRECT PERMISSION MODAL --}}
                                        <div id="userPermissionModal" class="ac-modal">

                                            <div class="ac-card w-full max-w-3xl p-6">

                                                {{-- HEADER --}}
                                                <div class="flex justify-between items-center mb-5">

                                                    <div>
                                                        <h3 class="font-bold">
                                                            Permission Khusus User
                                                        </h3>

                                                        <p id="userPermissionModalName"
                                                           class="text-xs text-slate-500 mt-1"></p>
                                                    </div>

                                                    <button type="button"
                                                            onclick="closeModal('userPermissionModal')"
                                                            class="text-slate-400 hover:text-slate-900">
                                                        <i data-lucide="x"></i>
                                                    </button>

                                                </div>

                                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 mb-5">
                                                    <p class="text-xs text-blue-800 leading-relaxed">
                                                        Atur hak akses khusus untuk user ini. Pengaturan langsung
                                                        akan memiliki prioritas di atas permission dari role.
                                                    </p>
                                                </div>

                                                <form id="userPermissionForm" method="POST">

                                                    @csrf
                                                    @method('PUT')

                                                    <div class="space-y-6 max-h-[55vh] overflow-y-auto pr-2">

                                                        @foreach($permissions as $module => $modulePermissions)

                                                            <div>

                                                                <div class="flex items-center justify-between border-b border-slate-200 pb-2 mb-3">

                                                                    <h4 class="text-xs font-bold text-emerald-600 uppercase tracking-wider">
                                                                        {{ $module }}
                                                                    </h4>

                                                                </div>

                                                                <div class="space-y-2">

                                                                    @foreach($modulePermissions as $permission)

                                                                        <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-white p-3">

                                                                            <div class="min-w-0">

                                                                                <div class="text-xs font-semibold text-slate-800">
                                                                                    {{ $permission->display_name }}
                                                                                </div>

                                                                                <div class="text-[10px] text-slate-400 mt-1">
                                                                                    {{ $permission->name }}
                                                                                </div>

                                                                            </div>

                                                                            <div class="w-32 shrink-0">

                                                                                <select
                                                                                    name="permissions[{{ $permission->id }}]"
                                                                                    class="ac-input user-direct-permission"
                                                                                    data-permission-id="{{ $permission->id }}"
                                                                                >
                                                                                    <option value="default">Default</option>
                                                                                    <option value="grant">Grant</option>
                                                                                    <option value="deny">Deny</option>
                                                                                </select>

                                                                            </div>

                                                                        </div>

                                                                    @endforeach

                                                                </div>

                                                            </div>

                                                        @endforeach

                                                    </div>

                                                    <div class="flex items-center justify-between gap-3 mt-5 pt-4 border-t border-slate-200">

                                                        <p class="text-[10px] text-slate-400">
                                                            Default mengikuti permission role user.
                                                        </p>

                                                        <div class="flex justify-end gap-2">

                                                            <button type="button"
                                                                    onclick="closeModal('userPermissionModal')"
                                                                    class="ac-btn ac-btn-secondary">
                                                                Batal
                                                            </button>

                                                            <button type="submit"
                                                                    class="ac-btn ac-btn-primary">
                                                                <i data-lucide="save" class="w-4 h-4"></i>
                                                                Simpan Permission
                                                            </button>

                                                        </div>

                                                    </div>

                                                </form>

                                            </div>

                                        </div>
                                    @else

                                        <span class="text-xs text-slate-500">Read only</span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-slate-500">
                                    Belum ada pengguna.
                                </td>
                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- ROLE MODAL --}}
        {{-- ========================================================= --}}

        @if(auth()->user()->hasPermission('access.manage'))

            <div id="roleModal" class="ac-modal">

                <div class="ac-card w-full max-w-lg p-6">

                    <div class="flex justify-between items-center mb-5">

                        <h3 id="roleModalTitle" class="font-bold text-white">
                            Tambah Role
                        </h3>

                        <button type="button" onclick="closeModal('roleModal')"
                                class="text-slate-400 hover:text-slate-900">
                            <i data-lucide="x"></i>
                        </button>

                    </div>


                    <form id="roleForm" method="POST"
                          action="{{ route('access.roles.store') }}">

                        @csrf

                        <div id="roleMethod"></div>

                        <div class="space-y-4">

                            <div>
                                <label class="text-xs text-slate-400 block mb-2">
                                    Role Name (Slug)
                                </label>

                                <input type="text" name="name" id="roleName"
                                       class="ac-input"
                                       placeholder="contoh: network-engineer"
                                       required>
                            </div>

                            <div>
                                <label class="text-xs text-slate-400 block mb-2">
                                    Display Name
                                </label>

                                <input type="text" name="display_name"
                                       id="roleDisplayName"
                                       class="ac-input"
                                       placeholder="Network Engineer"
                                       required>
                            </div>

                            <div>
                                <label class="text-xs text-slate-400 block mb-2">
                                    Deskripsi
                                </label>

                                <textarea name="description" id="roleDescription"
                                          class="ac-input" rows="3"
                                          placeholder="Deskripsi role..."></textarea>
                            </div>

                        </div>

                        <div class="flex justify-end gap-2 mt-6">

                            <button type="button" onclick="closeModal('roleModal')"
                                    class="ac-btn ac-btn-secondary">
                                Batal
                            </button>

                            <button type="submit" class="ac-btn ac-btn-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Simpan Role
                            </button>

                        </div>

                    </form>

                </div>

            </div>


            {{-- USER ROLE MODAL --}}

            <div id="userModal" class="ac-modal">

                <div class="ac-card w-full max-w-lg p-6">

                    <div class="flex justify-between items-center mb-5">

                        <div>
                            <h3 class="font-bold text-white">
                                Atur Role Pengguna
                            </h3>

                            <p id="userModalName" class="text-xs mt-1"></p>
                        </div>

                        <button type="button" onclick="closeModal('userModal')"
                                class="text-slate-400 hover:text-white">
                            <i data-lucide="x"></i>
                        </button>

                    </div>


                    <form id="userRoleForm" method="POST">

                        @csrf
                        @method('PUT')

                        <div class="space-y-2">

                            @foreach($roles as $role)

                                <label class="ac-permission">

                                    <div>
                                        <div class="text-sm font-medium">
                                            {{ $role->display_name }}
                                        </div>

                                        <div class="text-xs text-slate-500 mt-1">
                                            {{ $role->description }}
                                        </div>
                                    </div>

                                    <input type="checkbox"
                                           name="roles[]"
                                           value="{{ $role->id }}"
                                           class="ac-check user-role-checkbox">

                                </label>

                            @endforeach

                        </div>

                        <p class="text-xs text-amber-400 mt-4">
                            <i data-lucide="info" class="w-3.5 h-3.5 inline"></i>
                            Super Admin memberikan seluruh hak akses sistem.
                        </p>

                        <div class="flex justify-end gap-2 mt-6">

                            <button type="button" onclick="closeModal('userModal')"
                                    class="ac-btn ac-btn-secondary">
                                Batal
                            </button>

                            <button type="submit" class="ac-btn ac-btn-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Simpan Role
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        @endif


        {{-- ========================================================= --}}
        {{-- JAVASCRIPT --}}
        {{-- ========================================================= --}}

        <script>
            (() => {
                'use strict';

                /* =========================================================
                   MOVE MODALS TO BODY
                   Menghindari benturan CSS .jn-main dan stacking context.
                ========================================================= */

                document.querySelectorAll('.ac-modal').forEach(modal => {
                    document.body.appendChild(modal);
                });

                /* =========================================================
                   TABS
                ========================================================= */

                document.querySelectorAll('[data-tab]').forEach(button => {
                    button.addEventListener('click', () => {
                        document.querySelectorAll('[data-tab]').forEach(tab => {
                            tab.classList.remove('active');
                        });

                        button.classList.add('active');

                        const isRoles = button.dataset.tab === 'roles';

                        document.getElementById('tab-roles').style.display =
                            isRoles ? 'block' : 'none';

                        document.getElementById('tab-users').style.display =
                            isRoles ? 'none' : 'block';
                    });
                });

                /* =========================================================
                   ROLE SELECTOR
                ========================================================= */

                window.selectRole = function (id) {
                    document.querySelectorAll('.role-panel').forEach(panel => {
                        panel.style.display = 'none';
                    });

                    document.querySelectorAll('.role-selector').forEach(button => {
                        button.classList.remove('active');
                    });

                    const panel = document.getElementById(id);
                    const button = document.querySelector(`[data-role="${id}"]`);

                    if (panel) panel.style.display = 'block';
                    if (button) button.classList.add('active');
                };

                const firstRole = document.querySelector('.role-selector');

                if (firstRole) {
                    window.selectRole(firstRole.dataset.role);
                }

                /* =========================================================
                   SELECT ALL PER MODULE
                ========================================================= */

                window.toggleModule = function (button, group) {
                    const separator = group.lastIndexOf('-');

                    const roleId = group.substring(0, separator);
                    const moduleIndex = group.substring(separator + 1);

                    const checkboxes = document.querySelectorAll(
                        `.module-${roleId}-${moduleIndex}:not(:disabled)`
                    );

                    const shouldCheck = [...checkboxes].some(box => !box.checked);

                    checkboxes.forEach(box => {
                        box.checked = shouldCheck;
                    });
                };

                /* =========================================================
                   MODAL HELPERS
                ========================================================= */

                window.openModal = function (id) {
                    const modal = document.getElementById(id);

                    if (!modal) return;

                    modal.classList.add('show');
                    document.body.style.overflow = 'hidden';

                    setTimeout(() => {
                        const input = modal.querySelector(
                            'input:not([type="hidden"]):not([type="checkbox"]), textarea'
                        );

                        input?.focus();
                    }, 100);
                };

                window.closeModal = function (id) {
                    const modal = document.getElementById(id);

                    if (!modal) return;

                    modal.classList.remove('show');

                    if (!document.querySelector('.ac-modal.show')) {
                        document.body.style.overflow = '';
                    }
                };

                /* =========================================================
                   CREATE ROLE
                ========================================================= */

                window.openRoleModal = function () {
                    const title = document.getElementById('roleModalTitle');
                    const form = document.getElementById('roleForm');

                    title.textContent = 'Tambah Role';

                    form.action = @js(route('access.roles.store'));

                    document.getElementById('roleMethod').innerHTML = '';

                    document.getElementById('roleName').value = '';
                    document.getElementById('roleDisplayName').value = '';
                    document.getElementById('roleDescription').value = '';

                    window.openModal('roleModal');
                };

                /* =========================================================
                   EDIT ROLE
                ========================================================= */

                window.editRole = function (id, name, displayName, description) {
                    document.getElementById('roleModalTitle').textContent = 'Edit Role';

                    document.getElementById('roleForm').action =
                        @js(url('/dashboard/access/roles')) + '/' + id;

                    document.getElementById('roleMethod').innerHTML =
                        '<input type="hidden" name="_method" value="PUT">';

                    document.getElementById('roleName').value = name;
                    document.getElementById('roleDisplayName').value = displayName;
                    document.getElementById('roleDescription').value = description || '';

                    window.openModal('roleModal');
                };

                /* =========================================================
                   USER ROLE ASSIGNMENT
                ========================================================= */

                window.openUserModal = function (id, name, selectedRoles) {
                    document.getElementById('userModalName').textContent = name;

                    document.getElementById('userRoleForm').action =
                        @js(url('/dashboard/access/users')) + '/' + id + '/roles';

                    const selected = (selectedRoles || []).map(String);

                    document.querySelectorAll('.user-role-checkbox').forEach(box => {
                        box.checked = selected.includes(box.value);
                    });

                    window.openModal('userModal');
                };

                /* =========================================================
   USER DIRECT PERMISSIONS
========================================================= */

                window.openUserPermissionModal = function (id, name, directPermissions) {

                    const form = document.getElementById('userPermissionForm');

                    document.getElementById('userPermissionModalName').textContent =
                        name;

                    form.action =
                        @js(url('/dashboard/access/users')) + '/' + id + '/permissions';

                    const permissions = directPermissions || {};

                    document.querySelectorAll('.user-direct-permission').forEach(select => {

                        const permissionId = select.dataset.permissionId;

                        select.value = permissions[permissionId] || 'default';

                    });

                    window.openModal('userPermissionModal');

                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                };
                /* =========================================================
                   CLOSE MODAL
                ========================================================= */

                document.querySelectorAll('.ac-modal').forEach(modal => {
                    modal.addEventListener('click', event => {
                        if (event.target === modal) {
                            window.closeModal(modal.id);
                        }
                    });
                });

                document.addEventListener('keydown', event => {
                    if (event.key === 'Escape') {
                        document.querySelectorAll('.ac-modal.show').forEach(modal => {
                            window.closeModal(modal.id);
                        });
                    }
                });

                /* =========================================================
                   SWEETALERT2 DELETE CONFIRMATION
                ========================================================= */

                document.querySelectorAll('.ac-delete-role-form').forEach(form => {
                    form.addEventListener('submit', async event => {
                        event.preventDefault();

                        const roleName = form.dataset.roleName || 'role ini';

                        if (!window.Swal) {
                            if (confirm(`Yakin ingin menghapus ${roleName}?`)) {
                                form.submit();
                            }
                            return;
                        }

                        const result = await Swal.fire({
                            icon: 'warning',
                            title: 'Hapus Role?',
                            html: `Role <strong>${escapeHtml(roleName)}</strong> akan dihapus.`,
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Hapus',
                            cancelButtonText: 'Batal',
                            reverseButtons: true,
                            focusCancel: true,
                            confirmButtonColor: '#dc2626'

                        });

                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });

                function escapeHtml(value) {
                    return String(value).replace(/[&<>"']/g, char => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    })[char]);
                }

            })();
        </script>

    </div>
</x-layouts.dashboard>
