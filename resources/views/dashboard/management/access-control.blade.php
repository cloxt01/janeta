<x-layouts.dashboard title="Access Control">

    @php
        $totalPermissions = $permissions->flatten()->count();
        $totalUsers = $users->count();
        $canManage = auth()->user()->hasPermission('access.manage');
    @endphp

    <style>
        /* =========================================================
           ACCESS CONTROL - CENTIPID DARK STYLE
           Charcoal surfaces, hairline borders, compact type,
           green primary pill buttons.
        ========================================================= */

        .ac-page,
        .ac-modal {
            --ac-bg:        #1c1c1c;
            --ac-surface:   #232323;
            --ac-surface-2: #2a2a2a;
            --ac-hover:     #2d2d2d;
            --ac-border:    #2e2e2e;
            --ac-border-2:  #3a3a3a;
            --ac-text:      #f2f2f2;
            --ac-muted:     #a1a1a1;
            --ac-faint:     #6d6d6d;
            --ac-green:     #0a8f3f;
            --ac-green-hi:  #0fa84b;
            --ac-green-txt: #22c55e;
            --ac-green-bg:  rgba(34, 197, 94, .10);
            --ac-red:       #ef4444;
            --ac-amber:     #f59e0b;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ---------- text helpers ---------- */
        .ac-page .ac-t { color: var(--ac-text); }
        .ac-page .ac-m { color: var(--ac-muted); }
        .ac-page .ac-f { color: var(--ac-faint); }
        .ac-page .ac-g { color: var(--ac-green-txt); }

        .ac-eyebrow {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ac-muted);
        }

        .ac-h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -.02em;
            color: var(--ac-text);
            line-height: 1.2;
        }
        .ac-h1 span { color: var(--ac-muted); }

        /* ---------- card ---------- */
        .ac-card {
            background: var(--ac-surface);
            border: 1px solid var(--ac-border);
            border-radius: 12px;
            color: var(--ac-text);
        }

        /* ---------- stat strip (satu kartu, dibagi garis) ---------- */
        .ac-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            background: var(--ac-surface);
            border: 1px solid var(--ac-border);
            border-radius: 12px;
            overflow: hidden;
        }
        .ac-stat { padding: 16px 20px; }
        .ac-stat + .ac-stat { border-left: 1px solid var(--ac-border); }
        .ac-stat-label { font-size: 11px; color: var(--ac-muted); }
        .ac-stat-value {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -.02em;
            margin: 6px 0 2px;
            color: var(--ac-text);
        }
        .ac-stat-note { font-size: 11px; color: var(--ac-faint); }

        @media (max-width: 640px) {
            .ac-stats { grid-template-columns: 1fr; }
            .ac-stat + .ac-stat { border-left: 0; border-top: 1px solid var(--ac-border); }
        }

        /* ---------- buttons (pill) ---------- */
        .ac-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 999px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.4;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s, border-color .15s, color .15s;
        }
        .ac-btn:disabled { opacity: .5; cursor: not-allowed; }

        .ac-btn-primary {
            background: var(--ac-green);
            border: 1px solid var(--ac-green);
            color: #fff;
        }
        .ac-btn-primary:hover { background: var(--ac-green-hi); border-color: var(--ac-green-hi); }

        .ac-btn-secondary {
            background: transparent;
            border: 1px solid var(--ac-border-2);
            color: var(--ac-text);
        }
        .ac-btn-secondary:hover { background: var(--ac-hover); }

        .ac-btn-danger {
            background: transparent;
            border: 1px solid rgba(239, 68, 68, .35);
            color: #f87171;
        }
        .ac-btn-danger:hover { background: rgba(239, 68, 68, .1); }

        .ac-link-btn {
            font-size: 11px;
            color: var(--ac-muted);
            background: none;
            border: 0;
            cursor: pointer;
            transition: color .15s;
        }
        .ac-link-btn:hover { color: var(--ac-green-txt); }

        .ac-icon-btn {
            color: var(--ac-muted);
            background: none;
            border: 0;
            cursor: pointer;
            padding: 4px;
            border-radius: 8px;
        }
        .ac-icon-btn:hover { color: var(--ac-text); background: var(--ac-hover); }

        /* ---------- tabs (segmented pill + count) ---------- */
        .ac-tabs {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 12px 0;
            border-top: 1px solid var(--ac-border);
            border-bottom: 1px solid var(--ac-border);
            margin-bottom: 20px;
            overflow-x: auto;
        }
        .ac-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            color: var(--ac-muted);
            border: 0;
            border-radius: 999px;
            background: transparent;
            white-space: nowrap;
            transition: background .15s, color .15s;
        }
        .ac-tab:hover { color: var(--ac-text); }
        .ac-tab.active { background: var(--ac-surface-2); color: var(--ac-text); }
        .ac-tab .ac-count { font-size: 10px; color: var(--ac-faint); }

        /* ---------- role selector ---------- */
        .role-selector {
            width: 100%;
            text-align: left;
            cursor: pointer;
            background: var(--ac-surface);
            border: 1px solid var(--ac-border);
            border-radius: 12px;
            color: var(--ac-text);
            padding: 14px 16px;
            transition: background .15s, border-color .15s;
        }
        .role-selector:hover { background: var(--ac-hover); border-color: var(--ac-border-2); }
        .role-selector.active {
            border-color: var(--ac-green);
            background: var(--ac-green-bg);
        }
        .ac-avatar {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--ac-surface-2);
            color: var(--ac-muted);
            flex-shrink: 0;
        }
        .ac-avatar.green { background: var(--ac-green-bg); color: var(--ac-green-txt); }

        /* ---------- inputs ---------- */
        .ac-input {
            display: block;
            width: 100%;
            background: var(--ac-bg);
            border: 1px solid var(--ac-border-2);
            border-radius: 9px;
            padding: 9px 12px;
            color: var(--ac-text);
            font-size: 13px;
            line-height: 1.5;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .ac-input::placeholder { color: var(--ac-faint); opacity: 1; }
        .ac-input:focus {
            border-color: var(--ac-green-hi);
            box-shadow: 0 0 0 3px rgba(15, 168, 75, .18);
        }
        select.ac-input { padding: 7px 10px; font-size: 12px; }
        select.ac-input option { background: var(--ac-surface); color: var(--ac-text); }

        /* ---------- checkbox ---------- */
        .ac-check {
            accent-color: var(--ac-green-hi);
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .ac-check:disabled { cursor: not-allowed; opacity: .5; }

        /* ---------- permission item ---------- */
        .ac-permission {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            background: var(--ac-bg);
            border: 1px solid var(--ac-border);
            border-radius: 9px;
            color: var(--ac-text);
            cursor: pointer;
            transition: background .15s, border-color .15s;
        }
        .ac-permission:hover { background: var(--ac-hover); border-color: var(--ac-border-2); }
        .ac-permission-name { font-size: 12px; font-weight: 500; color: var(--ac-text); }
        .ac-permission-slug { font-size: 10px; color: var(--ac-faint); margin-top: 3px; }

        /* ---------- badges ---------- */
        .ac-badge {
            display: inline-flex;
            align-items: center;
            font-size: 10px;
            padding: 3px 9px;
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: .2px;
            white-space: nowrap;
        }
        .ac-badge-green {
            background: var(--ac-green-bg);
            color: var(--ac-green-txt);
            border: 1px solid rgba(34, 197, 94, .25);
        }
        .ac-badge-gray {
            background: transparent;
            color: var(--ac-muted);
            border: 1px solid var(--ac-border-2);
        }

        /* ---------- section divider ---------- */
        .ac-divider-b { border-bottom: 1px solid var(--ac-border); }
        .ac-divider-t { border-top: 1px solid var(--ac-border); }

        .ac-module-title {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ac-green-txt);
        }

        /* ---------- table ---------- */
        .ac-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ac-table thead th {
            text-align: left;
            padding: 12px 20px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ac-faint);
            border-bottom: 1px solid var(--ac-border);
            background: transparent;
        }
        .ac-table tbody td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--ac-border);
            vertical-align: middle;
        }
        .ac-table tbody tr:last-child td { border-bottom: 0; }
        .ac-table tbody tr:hover { background: var(--ac-surface-2); }

        .ac-user-initial {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: var(--ac-surface-2);
            color: var(--ac-muted);
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ---------- callouts ---------- */
        .ac-callout {
            border-radius: 9px;
            padding: 10px 12px;
            font-size: 12px;
            line-height: 1.55;
        }
        .ac-callout-info  { background: rgba(59, 130, 246, .08); border: 1px solid rgba(59, 130, 246, .25); color: #93c5fd; }
        .ac-callout-warn  { background: rgba(245, 158, 11, .08); border: 1px solid rgba(245, 158, 11, .25); color: #fbbf24; }

        /* ---------- modal (dipindah ke body oleh JS) ---------- */
        .ac-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0, 0, 0, .6);
            backdrop-filter: blur(4px);
        }
        .ac-modal.show { display: flex; }

        .ac-modal .ac-card {
            position: relative;
            width: 100%;
            max-width: 480px;
            padding: 22px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            background: var(--ac-surface);
            box-shadow: 0 24px 60px rgba(0, 0, 0, .6);
        }
        .ac-modal .ac-card.wide { max-width: 760px; }
        .ac-modal h3 { color: var(--ac-text); font-size: 15px; font-weight: 700; }
        .ac-modal label.ac-label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: var(--ac-muted);
            margin-bottom: 6px;
        }
        .ac-modal .ac-sub { font-size: 11px; color: var(--ac-faint); margin-top: 4px; }

        /* scrollbar halus */
        .ac-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .ac-scroll::-webkit-scrollbar-thumb { background: var(--ac-border-2); border-radius: 999px; }

        @media (max-width: 640px) {
            .ac-modal { padding: 12px; }
            .ac-modal .ac-card { padding: 18px; }
        }
    </style>

    <div class="ac-page">


        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">

            <div>
                <div class="ac-eyebrow mb-2">Platform · Access control</div>

                <h1 class="ac-h1">
                    Kelola akses tim kamu<span>.</span>
                </h1>

                <p class="text-xs ac-m mt-2">
                    {{ $roles->count() }} role · {{ $totalPermissions }} permission · {{ $totalUsers }} pengguna JANETA
                </p>
            </div>

            @if($canManage)
                <button type="button" onclick="openRoleModal()" class="ac-btn ac-btn-primary">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Tambah Role
                </button>
            @endif

        </div>


        {{-- STATISTICS --}}
        <div class="ac-stats mb-6">

            <div class="ac-stat">
                <div class="ac-stat-label">Total roles</div>
                <div class="ac-stat-value">{{ $roles->count() }}</div>
                <div class="ac-stat-note">Role terdaftar</div>
            </div>

            <div class="ac-stat">
                <div class="ac-stat-label">Total permissions</div>
                <div class="ac-stat-value">{{ $totalPermissions }}</div>
                <div class="ac-stat-note">Hak akses sistem</div>
            </div>

            <div class="ac-stat">
                <div class="ac-stat-label">Total users</div>
                <div class="ac-stat-value">{{ $totalUsers }}</div>
                <div class="ac-stat-note">Pengguna JANETA</div>
            </div>

        </div>


        {{-- TABS --}}
        <div class="ac-tabs ac-scroll">

            <button type="button" class="ac-tab active" data-tab="roles">
                Role &amp; Permissions
                <span class="ac-count">{{ $roles->count() }}</span>
            </button>

            <button type="button" class="ac-tab" data-tab="users">
                User Assignment
                <span class="ac-count">{{ $totalUsers }}</span>
            </button>

        </div>


        {{-- ========================================================= --}}
        {{-- ROLE & PERMISSIONS --}}
        {{-- ========================================================= --}}

        <section id="tab-roles">

            <div class="mb-4">
                <h2 class="text-sm font-semibold ac-t">Role management</h2>
                <p class="text-xs ac-f mt-1">Pilih role untuk melihat dan mengatur permission.</p>
            </div>


            {{-- ROLE SELECTOR --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 mb-6">

                @foreach($roles as $role)
                    <button type="button"
                            class="role-selector"
                            data-role="role-{{ $role->id }}"
                            onclick="selectRole('role-{{ $role->id }}')">

                        <div class="flex justify-between items-start gap-2">

                            <div class="flex items-center gap-3">

                                <div class="ac-avatar {{ $role->name === 'super-admin' ? 'green' : '' }}">
                                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                                </div>

                                <div>
                                    <div class="font-semibold text-sm ac-t">{{ $role->display_name }}</div>
                                    <div class="text-xs ac-f mt-1">{{ $role->users_count }} pengguna</div>
                                </div>

                            </div>

                            @if($role->name === 'super-admin')
                                <span class="ac-badge ac-badge-green">Protected</span>
                            @endif

                        </div>

                        <p class="text-xs ac-m mt-3 line-clamp-2">
                            {{ $role->description ?: 'Tidak ada deskripsi.' }}
                        </p>

                        <div class="text-xs ac-f mt-3">
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
                        <div class="p-5 ac-divider-b flex flex-col lg:flex-row lg:items-center justify-between gap-4">

                            <div>
                                <div class="flex items-center gap-2">

                                    <h3 class="text-base font-bold ac-t">{{ $role->display_name }}</h3>

                                    @if($role->name === 'super-admin')
                                        <span class="ac-badge ac-badge-green">Super admin</span>
                                    @endif

                                </div>

                                <p class="text-xs ac-f mt-1">{{ $role->name }}</p>
                            </div>


                            @if($canManage && $role->name !== 'super-admin')

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

                        </div>


                        {{-- PERMISSION FORM --}}
                        <form method="POST" action="{{ route('access.roles.permissions', $role) }}">

                            @csrf
                            @method('PUT')

                            <div class="p-5">

                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">

                                    <div>
                                        <h4 class="font-semibold text-sm ac-t">Permissions</h4>
                                        <p class="text-xs ac-f mt-1">Hak akses yang dimiliki role ini.</p>
                                    </div>

                                    @if($role->name === 'super-admin')
                                        <span class="text-xs ac-g">
                                            <i data-lucide="lock" class="w-3.5 h-3.5 inline"></i>
                                            Seluruh permission aktif secara otomatis
                                        </span>
                                    @else
                                        <span class="text-xs ac-f">
                                            {{ $role->permissions->count() }} / {{ $totalPermissions }} aktif
                                        </span>
                                    @endif

                                </div>


                                @foreach($permissions as $module => $modulePermissions)

                                    <div class="mb-6 last:mb-0">

                                        <div class="flex items-center justify-between ac-divider-b pb-2 mb-3">

                                            <h5 class="ac-module-title">{{ $module }}</h5>

                                            @if($role->name !== 'super-admin' && $canManage)
                                                <button type="button"
                                                        class="ac-link-btn"
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
                                                        <div class="ac-permission-name">{{ $permission->display_name }}</div>
                                                        <div class="ac-permission-slug">{{ $permission->name }}</div>
                                                    </div>

                                                    <input
                                                        type="checkbox"
                                                        class="ac-check shrink-0 module-{{ $role->id }}-{{ $loop->parent->index }}"
                                                        name="permissions[]"
                                                        value="{{ $permission->id }}"
                                                        {{ $checked ? 'checked' : '' }}
                                                        {{ $role->name === 'super-admin' || !$canManage ? 'disabled' : '' }}
                                                    >

                                                </label>

                                            @endforeach

                                        </div>

                                    </div>

                                @endforeach

                            </div>


                            @if($role->name !== 'super-admin' && $canManage)

                                <div class="p-4 ac-divider-t flex justify-end">

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

                <div class="p-5 ac-divider-b">
                    <h3 class="font-semibold text-sm ac-t">User role assignment</h3>
                    <p class="text-xs ac-f mt-1">Atur role yang dimiliki masing-masing pengguna JANETA.</p>
                </div>

                <div class="overflow-x-auto ac-scroll">

                    <table class="ac-table">

                        <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Role saat ini</th>
                            <th>Pengaturan</th>
                        </tr>
                        </thead>

                        <tbody>

                        @forelse($users as $managedUser)

                            <tr>

                                <td>
                                    <div class="flex items-center gap-3">

                                        <div class="ac-user-initial">
                                            {{ strtoupper(mb_substr($managedUser->name, 0, 2)) }}
                                        </div>

                                        <div>
                                            <div class="font-medium text-sm ac-t">{{ $managedUser->name }}</div>
                                            <div class="text-xs ac-f mt-1">{{ $managedUser->email }}</div>
                                        </div>

                                    </div>
                                </td>


                                <td>
                                    <div class="flex flex-wrap gap-1.5">

                                        @forelse($managedUser->roles as $userRole)

                                            <span class="ac-badge {{ $userRole->name === 'super-admin' ? 'ac-badge-green' : 'ac-badge-gray' }}">
                                                {{ $userRole->display_name ?: $userRole->name }}
                                            </span>

                                        @empty

                                            <span class="text-xs ac-f">Belum memiliki role</span>

                                        @endforelse

                                    </div>
                                </td>


                                <td>
                                    @if($canManage)

                                        <div class="flex flex-wrap gap-2">

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

                                        </div>

                                    @else

                                        <span class="text-xs ac-f">Read only</span>

                                    @endif
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="3" class="text-center ac-f" style="padding: 40px 20px;">
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
        {{-- MODALS --}}
        {{-- ========================================================= --}}

        @if($canManage)

            {{-- ROLE MODAL --}}
            <div id="roleModal" class="ac-modal">

                <div class="ac-card">

                    <div class="flex justify-between items-center mb-5">

                        <h3 id="roleModalTitle">Tambah Role</h3>

                        <button type="button" onclick="closeModal('roleModal')" class="ac-icon-btn">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>

                    </div>


                    <form id="roleForm" method="POST" action="{{ route('access.roles.store') }}">

                        @csrf

                        <div id="roleMethod"></div>

                        <div class="space-y-4">

                            <div>
                                <label class="ac-label">Role name (slug)</label>
                                <input type="text" name="name" id="roleName"
                                       class="ac-input"
                                       placeholder="contoh: network-engineer"
                                       required>
                            </div>

                            <div>
                                <label class="ac-label">Display name</label>
                                <input type="text" name="display_name" id="roleDisplayName"
                                       class="ac-input"
                                       placeholder="Network Engineer"
                                       required>
                            </div>

                            <div>
                                <label class="ac-label">Deskripsi</label>
                                <textarea name="description" id="roleDescription"
                                          class="ac-input" rows="3"
                                          placeholder="Deskripsi role..."></textarea>
                            </div>

                        </div>

                        <div class="flex justify-end gap-2 mt-6">

                            <button type="button" onclick="closeModal('roleModal')" class="ac-btn ac-btn-secondary">
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

                <div class="ac-card">

                    <div class="flex justify-between items-center mb-5">

                        <div>
                            <h3>Atur Role Pengguna</h3>
                            <p id="userModalName" class="ac-sub"></p>
                        </div>

                        <button type="button" onclick="closeModal('userModal')" class="ac-icon-btn">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>

                    </div>


                    <form id="userRoleForm" method="POST">

                        @csrf
                        @method('PUT')

                        <div class="space-y-2">

                            @foreach($roles as $role)

                                <label class="ac-permission">

                                    <div>
                                        <div class="ac-permission-name" style="font-size:13px;">
                                            {{ $role->display_name }}
                                        </div>

                                        <div class="ac-permission-slug">
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

                        <div class="ac-callout ac-callout-warn mt-4">
                            <i data-lucide="info" class="w-3.5 h-3.5 inline"></i>
                            Super Admin memberikan seluruh hak akses sistem.
                        </div>

                        <div class="flex justify-end gap-2 mt-6">

                            <button type="button" onclick="closeModal('userModal')" class="ac-btn ac-btn-secondary">
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


            {{-- USER DIRECT PERMISSION MODAL (satu saja, di luar loop user) --}}
            <div id="userPermissionModal" class="ac-modal">

                <div class="ac-card wide">

                    <div class="flex justify-between items-center mb-5">

                        <div>
                            <h3>Permission Khusus User</h3>
                            <p id="userPermissionModalName" class="ac-sub"></p>
                        </div>

                        <button type="button" onclick="closeModal('userPermissionModal')" class="ac-icon-btn">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>

                    </div>

                    <div class="ac-callout ac-callout-info mb-5">
                        Atur hak akses khusus untuk user ini. Pengaturan langsung
                        akan memiliki prioritas di atas permission dari role.
                    </div>

                    <form id="userPermissionForm" method="POST">

                        @csrf
                        @method('PUT')

                        <div class="space-y-6 overflow-y-auto pr-2 ac-scroll" style="max-height:55vh;">

                            @foreach($permissions as $module => $modulePermissions)

                                <div>

                                    <div class="flex items-center justify-between ac-divider-b pb-2 mb-3">
                                        <h4 class="ac-module-title">{{ $module }}</h4>
                                    </div>

                                    <div class="space-y-2">

                                        @foreach($modulePermissions as $permission)

                                            <div class="flex items-center justify-between gap-4"
                                                 style="padding:10px 12px;background:var(--ac-bg);border:1px solid var(--ac-border);border-radius:9px;">

                                                <div class="min-w-0">
                                                    <div class="ac-permission-name">{{ $permission->display_name }}</div>
                                                    <div class="ac-permission-slug">{{ $permission->name }}</div>
                                                </div>

                                                <div class="w-32 shrink-0">
                                                    <select name="permissions[{{ $permission->id }}]"
                                                            class="ac-input user-direct-permission"
                                                            data-permission-id="{{ $permission->id }}">
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

                        <div class="flex items-center justify-between gap-3 mt-5 pt-4 ac-divider-t">

                            <p class="text-xs ac-f">Default mengikuti permission role user.</p>

                            <div class="flex justify-end gap-2">

                                <button type="button" onclick="closeModal('userPermissionModal')" class="ac-btn ac-btn-secondary">
                                    Batal
                                </button>

                                <button type="submit" class="ac-btn ac-btn-primary">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    Simpan Permission
                                </button>

                            </div>

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

                /* Pindahkan modal ke body (hindari stacking context layout) */
                document.querySelectorAll('.ac-modal').forEach(modal => {
                    document.body.appendChild(modal);
                });

                /* TABS */
                document.querySelectorAll('[data-tab]').forEach(button => {
                    button.addEventListener('click', () => {
                        document.querySelectorAll('[data-tab]').forEach(tab => tab.classList.remove('active'));
                        button.classList.add('active');

                        const isRoles = button.dataset.tab === 'roles';
                        document.getElementById('tab-roles').style.display = isRoles ? 'block' : 'none';
                        document.getElementById('tab-users').style.display = isRoles ? 'none' : 'block';
                    });
                });

                /* ROLE SELECTOR */
                window.selectRole = function (id) {
                    document.querySelectorAll('.role-panel').forEach(panel => panel.style.display = 'none');
                    document.querySelectorAll('.role-selector').forEach(button => button.classList.remove('active'));

                    const panel = document.getElementById(id);
                    const button = document.querySelector(`[data-role="${id}"]`);

                    if (panel) panel.style.display = 'block';
                    if (button) button.classList.add('active');
                };

                const firstRole = document.querySelector('.role-selector');
                if (firstRole) window.selectRole(firstRole.dataset.role);

                /* SELECT ALL PER MODULE */
                window.toggleModule = function (button, group) {
                    const separator = group.lastIndexOf('-');
                    const roleId = group.substring(0, separator);
                    const moduleIndex = group.substring(separator + 1);

                    const checkboxes = document.querySelectorAll(
                        `.module-${roleId}-${moduleIndex}:not(:disabled)`
                    );

                    const shouldCheck = [...checkboxes].some(box => !box.checked);
                    checkboxes.forEach(box => { box.checked = shouldCheck; });
                };

                /* MODAL HELPERS */
                window.openModal = function (id) {
                    const modal = document.getElementById(id);
                    if (!modal) return;

                    modal.classList.add('show');
                    document.body.style.overflow = 'hidden';

                    setTimeout(() => {
                        modal.querySelector('input:not([type="hidden"]):not([type="checkbox"]), textarea')?.focus();
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

                /* CREATE ROLE */
                window.openRoleModal = function () {
                    document.getElementById('roleModalTitle').textContent = 'Tambah Role';
                    document.getElementById('roleForm').action = @js(route('access.roles.store'));
                    document.getElementById('roleMethod').innerHTML = '';

                    document.getElementById('roleName').value = '';
                    document.getElementById('roleDisplayName').value = '';
                    document.getElementById('roleDescription').value = '';

                    window.openModal('roleModal');
                };

                /* EDIT ROLE */
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

                /* USER ROLE ASSIGNMENT */
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

                /* USER DIRECT PERMISSIONS */
                window.openUserPermissionModal = function (id, name, directPermissions) {
                    document.getElementById('userPermissionModalName').textContent = name;

                    document.getElementById('userPermissionForm').action =
                        @js(url('/dashboard/access/users')) + '/' + id + '/permissions';

                    const permissions = directPermissions || {};

                    document.querySelectorAll('.user-direct-permission').forEach(select => {
                        select.value = permissions[select.dataset.permissionId] || 'default';
                    });

                    window.openModal('userPermissionModal');

                    if (window.lucide) window.lucide.createIcons();
                };

                /* CLOSE MODAL (klik backdrop / Esc) */
                document.querySelectorAll('.ac-modal').forEach(modal => {
                    modal.addEventListener('click', event => {
                        if (event.target === modal) window.closeModal(modal.id);
                    });
                });

                document.addEventListener('keydown', event => {
                    if (event.key === 'Escape') {
                        document.querySelectorAll('.ac-modal.show').forEach(modal => {
                            window.closeModal(modal.id);
                        });
                    }
                });

                /* SWEETALERT2 DELETE CONFIRMATION (tema gelap) */
                document.querySelectorAll('.ac-delete-role-form').forEach(form => {
                    form.addEventListener('submit', async event => {
                        event.preventDefault();

                        const roleName = form.dataset.roleName || 'role ini';

                        if (!window.Swal) {
                            if (confirm(`Yakin ingin menghapus ${roleName}?`)) form.submit();
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
                            background: '#232323',
                            color: '#f2f2f2',
                            confirmButtonColor: '#dc2626',
                            cancelButtonColor: '#3a3a3a'
                        });

                        if (result.isConfirmed) form.submit();
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
