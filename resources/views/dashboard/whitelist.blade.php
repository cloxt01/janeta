<x-layouts.dashboard title="Telegram Whitelist">

    @php
        $totalEntries = $entries->total();
        $pageEntries = $entries->count();
        $adminEntries = $entries->getCollection()
            ->where('is_admin', true)
            ->count();
    @endphp

    <style>
        .whitelist-page .eyebrow {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .wl-toolbar,
        .wl-card,
        .wl-add,
        .wl-stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 1px 2px rgb(15 23 42 / 4%);
        }

        .wl-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 15px 18px;
        }

        .wl-toolbar-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 650;
        }

        .wl-toolbar-meta {
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }

        .wl-add {
            padding: 18px;
        }

        .wl-add summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            color: #1e293b;
            font-size: 13px;
            font-weight: 650;
            list-style: none;
        }

        .wl-add summary::-webkit-details-marker {
            display: none;
        }

        .wl-add summary svg {
            width: 16px;
            height: 16px;
            color: #64748b;
        }

        .wl-add-form {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }

        .wl-add-form input:not([type="checkbox"]),
        .wl-table input:not([type="checkbox"]) {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #fff;
            padding: 9px 10px;
            color: #1e293b;
            font-size: 12px;
            outline: 0;
            transition: border-color .2s, box-shadow .2s;
        }

        .wl-add-form input:focus,
        .wl-table input:focus {
            border-color: #64748b;
            box-shadow: 0 0 0 3px rgb(100 116 139 / 11%);
        }

        .wl-field label {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 10px;
            font-weight: 650;
        }

        .wl-stat-card {
            padding: 18px 20px;
        }

        .wl-table {
            border-collapse: collapse;
        }

        .wl-table th {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 12px 18px;
            white-space: nowrap;
        }

        .wl-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 18px;
        }

        .wl-table tr:last-child td {
            border-bottom: none;
        }

        .wl-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 6px;
            padding: 5px 9px;
            font-size: 10px;
            font-weight: 700;
        }

        .wl-badge-admin {
            background: #fffbeb;
            color: #b45309;
        }

        .wl-badge-user {
            background: #f1f5f9;
            color: #64748b;
        }

        .wl-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 7px;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 650;
            transition: all .2s;
            cursor: pointer;
        }

        .wl-action-save {
            background: #0f172a;
            color: #fff;
        }

        .wl-action-save:hover {
            background: #334155;
        }

        .wl-action-delete {
            border: 1px solid #fecaca;
            background: #fff;
            color: #dc2626;
        }

        .wl-action-delete:hover {
            background: #fef2f2;
        }

        .wl-empty {
            padding: 48px 20px !important;
            text-align: center;
        }

        @media (min-width: 768px) {
            .wl-add-form {
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }
        }
    </style>

    <div class="whitelist-page space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow mb-2">Configuration</p>

                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Telegram Whitelist
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kelola akses pengguna dan hak administrator yang diizinkan
                    berinteraksi dengan bot Telegram JANETA.
                </p>
            </div>
        </div>

        {{-- STATISTICS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            {{-- TOTAL --}}
            <div class="wl-stat-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Total Whitelist
                    </p>

                    <span class="rounded-lg bg-blue-50 p-2 text-blue-600">
                        <i data-lucide="users" class="h-5 w-5"></i>
                    </span>
                </div>

                <div class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ $totalEntries }}
                </div>

                <p class="mt-1 text-xs text-slate-400">
                    Chat terdaftar
                </p>
            </div>

            {{-- ADMIN --}}
            <div class="wl-stat-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Administrator
                    </p>

                    <span class="rounded-lg bg-amber-50 p-2 text-amber-600">
                        <i data-lucide="shield-check" class="h-5 w-5"></i>
                    </span>
                </div>

                <div class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ $adminEntries }}
                </div>

                <p class="mt-1 text-xs text-slate-400">
                    Admin pada halaman ini
                </p>
            </div>

            {{-- DISPLAYED --}}
            <div class="wl-stat-card">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Data Ditampilkan
                    </p>

                    <span class="rounded-lg bg-emerald-50 p-2 text-emerald-600">
                        <i data-lucide="list" class="h-5 w-5"></i>
                    </span>
                </div>

                <div class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ $pageEntries }}
                </div>

                <p class="mt-1 text-xs text-slate-400">
                    Dari {{ $totalEntries }} data
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

        {{-- ADD WHITELIST --}}
        <details class="wl-add" {{ $errors->any() ? 'open' : '' }}>

            <summary>
                <span class="flex items-center gap-2">
                    <i data-lucide="plus-circle" class="h-4 w-4"></i>
                    Tambah whitelist Telegram
                </span>

                <i data-lucide="chevron-down" class="h-4 w-4"></i>
            </summary>

            <form
                method="POST"
                action="{{ route('whitelist.store') }}"
                class="wl-add-form"
            >
                @csrf

                <div class="wl-field md:col-span-4">
                    <label>
                        Chat ID <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="text"
                        name="chat_id"
                        value="{{ old('chat_id') }}"
                        placeholder="Contoh: 123456789"
                        required
                    >
                </div>

                <div class="wl-field md:col-span-4">
                    <label>Nama / Grup</label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Contoh: NOC Team"
                    >
                </div>

                <div class="flex items-end md:col-span-2">
                    <label class="flex min-h-[36px] w-full cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600">
                        <input
                            type="checkbox"
                            name="is_admin"
                            value="1"
                            @checked(old('is_admin'))
                            class="h-4 w-4 rounded accent-slate-700"
                        >

                        <span>Admin Telegram</span>
                    </label>
                </div>

                <div class="flex items-end md:col-span-2">
                    <button type="submit" class="jn-button w-full">
                        <i data-lucide="save" class="mr-1 inline h-4 w-4"></i>
                        Simpan
                    </button>
                </div>

            </form>
        </details>

        {{-- TOOLBAR --}}
        <div class="wl-toolbar">
            <div>
                <div class="wl-toolbar-title">
                    Registered Whitelist
                </div>

                <div class="wl-toolbar-meta">
                    {{ $totalEntries }} records terdaftar
                </div>
            </div>

            <i data-lucide="send" class="h-5 w-5 text-slate-400"></i>
        </div>

        {{-- WHITELIST TABLE --}}
        <div class="wl-card">

            <div class="overflow-x-auto">

                <table class="wl-table w-full text-left">

                    <thead>
                        <tr>
                            <th>Chat ID</th>
                            <th>Nama / Grup</th>
                            <th>Hak Akses</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($entries as $entry)

                            <tr class="transition-colors hover:bg-slate-50/70">

                                {{-- CHAT ID --}}
                                <td class="w-1/3">
                                    <input
                                        form="update-{{ $entry->id }}"
                                        type="text"
                                        name="chat_id"
                                        value="{{ $entry->chat_id }}"
                                        required
                                        class="font-mono"
                                    >
                                </td>

                                {{-- NAME --}}
                                <td class="w-1/3">
                                    <input
                                        form="update-{{ $entry->id }}"
                                        type="text"
                                        name="name"
                                        value="{{ $entry->name }}"
                                        placeholder="Belum ada nama"
                                    >
                                </td>

                                {{-- ROLE --}}
                                <td>
                                    <div class="flex flex-col items-start gap-2">

                                        <span class="wl-badge {{ $entry->is_admin ? 'wl-badge-admin' : 'wl-badge-user' }}">
                                            <i data-lucide="{{ $entry->is_admin ? 'shield-check' : 'user' }}" class="h-3 w-3"></i>

                                            {{ $entry->is_admin ? 'Admin' : 'User' }}
                                        </span>

                                        <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-slate-500">
                                            <input
                                                form="update-{{ $entry->id }}"
                                                type="checkbox"
                                                name="is_admin"
                                                value="1"
                                                @checked($entry->is_admin)
                                                class="h-4 w-4 rounded accent-slate-700"
                                            >

                                            <span>Administrator</span>
                                        </label>

                                    </div>
                                </td>

                                {{-- ACTIONS --}}
                                <td>
                                    <div class="flex items-center justify-end gap-2">

                                        <form
                                            id="update-{{ $entry->id }}"
                                            method="POST"
                                            action="{{ route('whitelist.update', $entry) }}"
                                        >
                                            @csrf
                                            @method('PUT')
                                        </form>

                                        <button
                                            type="submit"
                                            form="update-{{ $entry->id }}"
                                            class="wl-action wl-action-save"
                                        >
                                            <i data-lucide="save" class="h-3.5 w-3.5"></i>
                                            Simpan
                                        </button>

                                        <form
                                            method="POST"
                                            action="{{ route('whitelist.destroy', $entry) }}"
                                            onsubmit="return confirm('Yakin ingin menghapus whitelist ini?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="wl-action wl-action-delete"
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
                                <td colspan="4" class="wl-empty">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <span class="rounded-xl bg-slate-100 p-4 text-slate-400">
                                            <i data-lucide="inbox" class="h-7 w-7"></i>
                                        </span>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-700">
                                                Belum ada whitelist
                                            </p>

                                            <p class="mt-1 text-xs text-slate-400">
                                                Tambahkan Chat ID Telegram untuk mulai mengelola akses bot.
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
            @if($entries->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $entries->links() }}
                </div>
            @endif

        </div>

    </div>

</x-layouts.dashboard>