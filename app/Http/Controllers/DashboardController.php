<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhitelistEntry;
use App\Models\Olt;
use App\Models\AuditLog;
use App\Services\OltService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function login(): View|RedirectResponse
    {
        return Auth::check()
            ? redirect()->route('dashboard')
            : view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->audit($request, 'auth.login_failed', 'Percobaan login gagal untuk '.$credentials['email'].'.');

            return back()->withErrors(['email' => 'Email atau password tidak valid.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);
        $this->audit($request, 'auth.login', 'User berhasil login.');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->audit($request, 'auth.logout', 'User berhasil logout.');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function index(): View
    {
        return view('dashboard.index', [
            'olts' => Olt::latest()->get(),
            'whitelistCount' => WhitelistEntry::count(),
            'adminCount' => User::where('is_admin', true)->count(),
            'userCount' => User::count(),
            'recentWhitelist' => WhitelistEntry::latest()->limit(5)->get(),
        ]);
    }

    public function ontLookup(Request $request): View
    {
        $id = $request->query('id');
        $result = null;
        $error = null;
        if ($id) {
            $request->validate(['id' => ['string', 'max:100']]);
            $this->audit($request, $request->boolean('wifi') ? 'ont.wifi_lookup' : 'ont.lookup', 'Pencarian ONT dijalankan untuk ID '.$id.'.');
            $result = app(OltService::class)->apiVersion($id);
            if ($result && $request->boolean('wifi')) {
                $result['wlan'] = app(OltService::class)->wlan(
                    $result['olt'],
                    $result['frame'],
                    $result['slot'],
                    $result['port'],
                    $result['ontId'],
                );
            }
            if (! $result) {
                $activeOlts = Olt::where('is_active', true)->pluck('name')->implode(', ');
                $error = $activeOlts
                    ? "Data ONT dengan ID customer '{$id}' tidak ditemukan pada OLT aktif ({$activeOlts}). Pastikan OLT tempat ONT berada sudah diaktifkan."
                    : 'Tidak ada OLT aktif untuk pencarian. Aktifkan minimal satu OLT terlebih dahulu.';
            }
        }

        return view('dashboard.ont-lookup', compact('result', 'error', 'id'));
    }

    public function olts(): View
    {
        return view('dashboard.olts', ['olts' => Olt::latest()->paginate(15)]);
    }

    public function oltPorts(Request $request): View
    {
        $request->validate([
            'olt' => ['nullable', 'exists:olts,name'],
            'frame' => ['nullable', 'regex:/^\d+$/'],
            'slot' => ['nullable', 'integer', 'between:0,11'],
        ]);
        $olts = Olt::where('is_active', true)->orderBy('name')->get();
        $oltName = $request->query('olt', $olts->first()?->name);
        $frame = $request->query('frame', '0');
        $slot = (int) $request->query('slot', 2);
        $scan = null;
        $error = null;

        if (! $oltName) {
            $error = 'Belum ada OLT aktif. Aktifkan OLT terlebih dahulu.';
        } else {
            $this->audit($request, 'olt.port_monitor', 'Port Monitor dijalankan untuk '.$oltName.' frame '.$frame.'.', $olts->firstWhere('name', $oltName));
            try {
                $device = $olts->firstWhere('name', $oltName);
                if ($device?->snmp_enabled || $device?->connection_type === 'snmp') {
                    $snapshot = app(OltService::class)->latestSnmpSnapshot($device, $frame);
                    $scan = $snapshot?->data['monitor'];
                    if ($scan) {
                        $scan['scanned_at'] = $snapshot->collected_at;
                        $scan['snapshot_error'] = $snapshot->error;
                    } else {
                        $error = 'Belum ada data SNMP. Jalankan scheduler atau php artisan janeta:collect-snmp.';
                    }
                } else {
                    $scan = app(OltService::class)->scanAllGponPorts($oltName, $frame);
                }
            } catch (\Throwable $exception) {
                report($exception);
                $error = 'Scan port gagal: '.$exception->getMessage();
            }
        }

        return view('dashboard.olt-ports', compact('olts', 'oltName', 'frame', 'slot', 'scan', 'error'));
    }

    public function storeOlt(Request $request): RedirectResponse
    {
        $data = $request->validate($this->oltRules($request));
        $data['name'] = strtoupper($data['name']);
        $data = $this->normalizeOltServices($data);
        $olt = Olt::create($data);
        $results = app(OltService::class)->testServices($olt);
        $result = $this->summarizeServiceResults($results);
        $this->audit($request, 'olt.created', 'OLT '.$olt->name.' ditambahkan.', $olt);

        return back()->with($result['ok'] ? 'success' : 'error', 'OLT berhasil ditambahkan. '.$result['message']);
    }

    private function oltRules(Request $request, ?Olt $olt = null): array
    {
        $nameRule = $olt
            ? 'unique:olts,name,'.$olt->id
            : 'unique:olts,name';

        return [
            'name' => ['required', 'string', 'max:100', $nameRule],
            'host' => ['required', 'string', 'max:255'],
            'ssh_enabled' => ['boolean'],
            'ssh_port' => ['nullable', 'integer', 'between:1,65535'],
            'ssh_username' => [Rule::requiredIf($request->boolean('ssh_enabled')), 'nullable', 'string', 'max:255'],
            'ssh_password' => ['nullable', 'string'],
            'ssh_private_key' => ['nullable', 'string'],
            'snmp_enabled' => ['boolean'],
            'snmp_port' => ['nullable', 'integer', 'between:1,65535'],
            'snmp_community' => [Rule::requiredIf($request->boolean('snmp_enabled')), 'nullable', 'string', 'max:255'],
        ];
    }

    public function updateOlt(Request $request, Olt $olt): RedirectResponse
    {
        $rules = $this->oltRules($request, $olt);
        $rules['is_active'] = ['boolean'];
        $data = $request->validate($rules);
        $data['name'] = strtoupper($data['name']);
        $data = $this->normalizeOltServices($data, $olt);
        $data['is_active'] = $request->boolean('is_active');
        if (! $data['is_active']) {
            $data['status'] = 'unknown';
            $data['last_error'] = null;
        }
        $olt->update($data);
        $this->audit($request, 'olt.updated', 'OLT '.$olt->name.' diperbarui.', $olt);
        $result = $data['is_active']
            ? $this->summarizeServiceResults(app(OltService::class)->testServices($olt))
            : ['ok' => true, 'message' => 'OLT dinonaktifkan.'];

        return back()->with($result['ok'] ? 'success' : 'error', 'OLT berhasil diperbarui. '.$result['message']);
    }

    private function normalizeOltServices(array $data, ?Olt $olt = null): array
    {
        $data['ssh_enabled'] = (bool) ($data['ssh_enabled'] ?? false);
        $data['snmp_enabled'] = (bool) ($data['snmp_enabled'] ?? false);
        $data['ssh_port'] = (int) ($data['ssh_port'] ?? 22);
        $data['snmp_port'] = (int) ($data['snmp_port'] ?? 161);
        $data['transport_type'] = 'tcp';
        $data['connection_type'] = $data['snmp_enabled'] && ! $data['ssh_enabled'] ? 'snmp' : 'ssh';
        $data['port'] = $data['connection_type'] === 'snmp' ? $data['snmp_port'] : $data['ssh_port'];
        $data['username'] = $data['connection_type'] === 'snmp'
            ? ($data['snmp_community'] ?? '')
            : ($data['ssh_username'] ?? '');
        $data['password'] = $data['connection_type'] === 'ssh'
            ? ($data['ssh_password'] ?? $olt?->ssh_password ?? '')
            : '';

        if ($olt && (! array_key_exists('ssh_password', $data) || $data['ssh_password'] === '')) {
            $data['ssh_password'] = $olt->ssh_password;
        }
        if ($olt && (! array_key_exists('ssh_private_key', $data) || $data['ssh_private_key'] === '')) {
            $data['ssh_private_key'] = $olt->ssh_private_key;
        }

        return $data;
    }

    private function summarizeServiceResults(array $results): array
    {
        $failed = collect($results)->filter(fn (array $result): bool => ! $result['ok']);

        return [
            'ok' => $failed->isEmpty(),
            'message' => $failed->isEmpty()
                ? 'Seluruh service aktif berhasil terhubung.'
                : 'Service gagal: '.$failed->map(fn (array $result, string $service): string => strtoupper($service).' - '.$result['message'])->implode('; '),
        ];
    }

    public function destroyOlt(Olt $olt): RedirectResponse
    {
        $this->audit(request(), 'olt.deleted', 'OLT '.$olt->name.' dihapus.', $olt);
        $olt->delete();

        return back()->with('success', 'OLT berhasil dihapus.');
    }

    public function testOlt(Olt $olt, OltService $service): RedirectResponse
    {
        $result = $this->summarizeServiceResults($service->testServices($olt));
        $this->audit(request(), 'olt.connection_test', 'Koneksi OLT '.$olt->name.' diuji: '.$result['message'].'.', $olt);

        return back()->with($result['ok'] ? 'success' : 'error', $olt->name.': '.$result['message']);
    }

    public function testAllOlts(OltService $service): RedirectResponse
    {
        $results = Olt::where('is_active', true)->get()->mapWithKeys(
            fn (Olt $olt) => [$olt->name => $this->summarizeServiceResults($service->testServices($olt))],
        );
        $failed = $results->filter(fn (array $result) => ! $result['ok']);
        $this->audit(
            request(),
            'olt.connection_test_all',
            $failed->isEmpty()
                ? 'Seluruh koneksi OLT aktif berhasil diuji.'
                : 'Uji koneksi selesai dengan kegagalan pada: '.$failed->keys()->implode(', ').'.',
        );

        return back()->with(
            $failed->isEmpty() ? 'success' : 'error',
            $failed->isEmpty()
                ? 'Semua OLT aktif berhasil terhubung.'
                : 'Sebagian koneksi gagal: '.$failed->keys()->implode(', '),
        );
    }

    public function auditLogs(): View
    {
        return view('dashboard.audit-logs', [
            'logs' => AuditLog::with('user')->latest()->paginate(25),
        ]);
    }

    public function whitelist(): View
    {
        return view('dashboard.whitelist', ['entries' => WhitelistEntry::latest()->paginate(15)]);
    }

    public function storeWhitelist(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'chat_id' => ['required', 'string', 'max:64', 'unique:whitelist_entries,chat_id'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_admin' => ['boolean'],
        ]);
        $data['is_admin'] = $request->boolean('is_admin');
        $entry = WhitelistEntry::create($data);
        $this->audit($request, 'whitelist.created', 'Whitelist Telegram '.$entry->chat_id.' ditambahkan.', $entry);

        return back()->with('success', 'Whitelist Telegram berhasil ditambahkan.');
    }

    public function updateWhitelist(Request $request, WhitelistEntry $entry): RedirectResponse
    {
        $data = $request->validate([
            'chat_id' => ['required', 'string', 'max:64', 'unique:whitelist_entries,chat_id,'.$entry->id],
            'name' => ['nullable', 'string', 'max:255'],
            'is_admin' => ['boolean'],
        ]);
        $data['is_admin'] = $request->boolean('is_admin');
        $entry->update($data);
        $this->audit($request, 'whitelist.updated', 'Whitelist Telegram '.$entry->chat_id.' diperbarui.', $entry);

        return back()->with('success', 'Whitelist Telegram berhasil diperbarui.');
    }

    public function destroyWhitelist(WhitelistEntry $entry): RedirectResponse
    {
        $this->audit(request(), 'whitelist.deleted', 'Whitelist Telegram '.$entry->chat_id.' dihapus.', $entry);
        $entry->delete();

        return back()->with('success', 'Whitelist Telegram berhasil dihapus.');
    }

    public function users(): View
    {
        return view('dashboard.users', ['users' => User::latest()->paginate(15)]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['boolean'],
            'hris_role' => ['required', Rule::in(['super_admin', 'admin', 'operator', 'viewer'])],
        ]);
        $data['is_admin'] = $request->boolean('is_admin');
        $data['role'] = $data['hris_role'];
        User::create($data);
        $this->audit($request, 'user.created', 'Pengguna dashboard ditambahkan.');

        return back()->with('success', 'Pengguna dashboard berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'is_admin' => ['boolean'],
            'hris_role' => ['required', Rule::in(['super_admin', 'admin', 'operator', 'viewer'])],
        ]);
        if (! array_key_exists('password', $data) || $data['password'] === null || $data['password'] === '') {
            unset($data['password']);
        }
        $data['is_admin'] = $request->boolean('is_admin');
        $data['role'] = $data['hris_role'];
        $user->update($data);
        $this->audit($request, 'user.updated', 'Pengguna dashboard diperbarui.', $user);

        return back()->with('success', 'Pengguna dashboard berhasil diperbarui.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        abort_if($user->is(Auth::user()), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        $user->delete();
        $this->audit(request(), 'user.deleted', 'Pengguna dashboard dihapus.', $user);

        return back()->with('success', 'Pengguna dashboard berhasil dihapus.');
    }

    private function audit(Request $request, string $action, string $description, ?object $subject = null): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'ip_address' => $request->ip(),
        ]);
    }
}
