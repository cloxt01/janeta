<?php

namespace App\Http\Controllers\Router\Mikrotik;

use App\Http\Controllers\Controller;
use App\Models\MikrotikRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RouterOS\Client as RouterOSClient;

class MikrotikController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $this->authorizePermission($request, 'mikrotik.view');

        return view('dashboard.devices.mikrotik', [
            'title' => 'MikroTik',
            'devices' => MikrotikRouter::query()
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'mikrotik.view');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mikrotik_routers', 'name'),
            ],
            'host' => ['required', 'string', 'max:150'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],

            'api_enabled' => ['nullable', 'boolean'],
            'api_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'api_ssl' => ['nullable', 'boolean'],
            'api_username' => ['nullable', 'string', 'max:100'],
            'api_password' => ['nullable', 'string'],

            'ssh_enabled' => ['nullable', 'boolean'],
            'ssh_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ssh_username' => ['nullable', 'string', 'max:100'],
            'ssh_password' => ['nullable', 'string'],
            'ssh_private_key' => ['nullable', 'string'],

            'snmp_enabled' => ['nullable', 'boolean'],
            'snmp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'snmp_community' => ['nullable', 'string', 'max:100'],
        ]);

        $apiSsl = $request->boolean('api_ssl');

        $apiPort = (int) (
            $validated['api_port']
            ?? ($apiSsl ? 8729 : 8728)
        );

        $device = MikrotikRouter::create([
            'name' => $validated['name'],
            'host' => $validated['host'],
            'port' => (int) ($validated['port'] ?? $apiPort),
            'username' => $validated['username'],
            'password' => $validated['password'],
            'is_active' => $request->boolean('is_active', true),
            'status' => 'unknown',

            // RouterOS API
            'api_enabled' => $request->boolean('api_enabled', true),
            'api_port' => $apiPort,
            'api_ssl' => $apiSsl,
            'api_username' => filled($validated['api_username'] ?? null)
                ? $validated['api_username']
                : $validated['username'],
            'api_password' => filled($validated['api_password'] ?? null)
                ? $validated['api_password']
                : $validated['password'],

            // SSH
            'ssh_enabled' => $request->boolean('ssh_enabled'),
            'ssh_port' => (int) ($validated['ssh_port'] ?? 22),
            'ssh_username' => $validated['ssh_username']
                ?? $validated['username'],
            'ssh_password' => filled($validated['ssh_password'] ?? null)
                ? $validated['ssh_password']
                : $validated['password'],
            'ssh_private_key' => $validated['ssh_private_key'] ?? null,

            // SNMP
            'snmp_enabled' => $request->boolean('snmp_enabled'),
            'snmp_port' => (int) ($validated['snmp_port'] ?? 161),
            'snmp_community' => $validated['snmp_community'] ?? null,
        ]);

        $this->auditAction(
            $request,
            'mikrotik.created',
            "Menambahkan router MikroTik {$device->name} ({$device->host})."
        );

        return redirect()
            ->route('router.mikrotik.index')
            ->with('success', "Router {$device->name} berhasil ditambahkan.");
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        MikrotikRouter $mikrotik
    ): View {
        $this->authorizePermission($request, 'mikrotik.view');

        return view('dashboard.router.mikrotik-show', [
            'title' => "MikroTik · {$mikrotik->name}",
            'device' => $mikrotik,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */


    public function update(
        Request $request,
        MikrotikRouter $mikrotik
    ): RedirectResponse {
        $this->authorizePermission($request, 'mikrotik.view');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mikrotik_routers', 'name')
                    ->ignore($mikrotik->id),
            ],
            'host' => ['required', 'string', 'max:150'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],

            'api_enabled' => ['nullable', 'boolean'],
            'api_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'api_ssl' => ['nullable', 'boolean'],
            'api_username' => ['nullable', 'string', 'max:100'],
            'api_password' => ['nullable', 'string'],

            'ssh_enabled' => ['nullable', 'boolean'],
            'ssh_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ssh_username' => ['nullable', 'string', 'max:100'],
            'ssh_password' => ['nullable', 'string'],
            'ssh_private_key' => ['nullable', 'string'],

            'snmp_enabled' => ['nullable', 'boolean'],
            'snmp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'snmp_community' => ['nullable', 'string', 'max:100'],
        ]);

        $payload = [
            // General
            'name' => $validated['name'],
            'host' => $validated['host'],
            'username' => $validated['username'],
            'is_active' => $request->boolean(
                'is_active',
                (bool) $mikrotik->is_active
            ),

            // RouterOS API
            'api_enabled' => $request->boolean('api_enabled'),
            'api_ssl' => $request->boolean('api_ssl'),
            'api_port' => (int) (
                $validated['api_port'] ?? $mikrotik->api_port
            ),
            'api_username' => filled($validated['api_username'] ?? null)
                ? $validated['api_username']
                : $validated['username'],

            // General port
            'port' => (int) (
                $validated['port'] ?? $mikrotik->port
            ),

            // SSH
            'ssh_enabled' => $request->boolean('ssh_enabled'),
            'ssh_port' => (int) (
                $validated['ssh_port'] ?? $mikrotik->ssh_port ?? 22
            ),
            'ssh_username' => filled($validated['ssh_username'] ?? null)
                ? $validated['ssh_username']
                : $validated['username'],

            // SNMP
            'snmp_enabled' => $request->boolean('snmp_enabled'),
            'snmp_port' => (int) (
                $validated['snmp_port'] ?? $mikrotik->snmp_port ?? 161
            ),
            'snmp_community' => $validated['snmp_community']
                ?? $mikrotik->snmp_community,
        ];

        // Update passwords only when a new value is submitted.
        foreach ([
                     'password',
                     'api_password',
                     'ssh_password',
                     'ssh_private_key',
                 ] as $field) {
            if (filled($validated[$field] ?? null)) {
                $payload[$field] = $validated[$field];
            }
        }

        $mikrotik->update($payload);

        $this->auditAction(
            $request,
            'mikrotik.updated',
            "Memperbarui konfigurasi router MikroTik {$mikrotik->name}."
        );

        return redirect()
            ->route('router.mikrotik.index')
            ->with('success', "Router {$mikrotik->name} berhasil diperbarui.");
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        MikrotikRouter $mikrotik
    ): RedirectResponse {
        $this->authorizePermission($request, 'mikrotik.view');

        $name = $mikrotik->name;

        $mikrotik->delete();

        $this->auditAction(
            $request,
            'mikrotik.deleted',
            "Menghapus router MikroTik {$name}."
        );

        return redirect()
            ->route('router.mikrotik.index')
            ->with('success', "Router {$name} berhasil dihapus.");
    }

    /*
    |--------------------------------------------------------------------------
    | ROUTEROS CLIENT
    |--------------------------------------------------------------------------
    */

    protected function createRouterOsClient(
        MikrotikRouter $device
    ): RouterOSClient
    {
        if (!$device->is_active) {
            throw new \RuntimeException(
                "Router {$device->name} sedang dinonaktifkan."
            );
        }

        if (!$device->api_enabled) {
            throw new \RuntimeException(
                "RouterOS API dinonaktifkan pada {$device->name}."
            );
        }

        $host = trim((string)$device->host);

        $port = (int)(
        $device->api_port
            ?: ($device->api_ssl ? 8729 : 8728)
        );

        $username = trim((string)(
        filled($device->api_username)
            ? $device->api_username
            : $device->username
        ));

        $password = (string)(
        filled($device->api_password)
            ? $device->api_password
            : ($device->password ?? '')
        );

        if ($host === '' || $username === '') {
            throw new \RuntimeException(
                'Host atau username RouterOS belum dikonfigurasi.'
            );
        }

        if ($password === '') {
            throw new \RuntimeException(
                'Password RouterOS belum dikonfigurasi.'
            );
        }

        Log::debug('JANETA RouterOS connection configuration', [
            'device' => $device->name,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'ssl' => (bool)$device->api_ssl,
            'password_configured' => true,
        ]);

        return new RouterOSClient([
            'host' => $host,
            'user' => $username,
            'pass' => $password,

            // Custom port dari database
            'port' => $port,

            // API-SSL
            'ssl' => (bool)$device->api_ssl,
            'ssl_options' => [
                'ciphers' => 'ADH:ALL@SECLEVEL=0',
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],

            'timeout' => 10,
            'socket_timeout' => 15,
            'attempts' => 1,
            'delay' => 0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TEST CONNECTION
    |--------------------------------------------------------------------------
    */


    public function testConnection(
        Request $request,
        MikrotikRouter $mikrotik
    ): RedirectResponse {
        $this->authorizePermission($request, 'mikrotik.view');

        $result = $this->probeDevice($mikrotik);

        $mikrotik->update([
            'status' => $result['ok'] ? 'online' : 'offline',
            'last_error' => $result['error'],
            'last_checked_at' => now(),
        ]);

        $this->auditAction(
            $request,
            'mikrotik.connection_test',
            $result['ok']
                ? "Uji koneksi RouterOS API {$mikrotik->name} berhasil."
                : "Uji koneksi RouterOS API {$mikrotik->name} gagal: {$result['error']}"
        );

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? "Koneksi RouterOS API ke {$mikrotik->name} berhasil."
                : "Koneksi RouterOS API ke {$mikrotik->name} gagal: {$result['error']}"
        );
    }



    public function testAll(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'mikrotik.view');

        $devices = MikrotikRouter::query()
            ->orderBy('name')
            ->get();

        $results = [];

        foreach ($devices as $device) {
            $result = $this->probeDevice($device);

            $device->update([
                'status' => $result['ok'] ? 'online' : 'offline',
                'last_error' => $result['error'],
                'last_checked_at' => now(),
            ]);

            $results[$device->name] = $result;
        }

        $failed = collect($results)->filter(
            fn (array $result): bool => !$result['ok']
        );

        $this->auditAction(
            $request,
            'mikrotik.connection_test_all',
            $failed->isEmpty()
                ? 'Seluruh router MikroTik berhasil diuji.'
                : 'Uji koneksi gagal: ' . $failed->keys()->implode(', ')
        );

        return back()->with(
            $failed->isEmpty() ? 'success' : 'error',
            $failed->isEmpty()
                ? 'Semua router MikroTik berhasil terhubung.'
                : 'Sebagian koneksi gagal: ' . $failed->keys()->implode(', ')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CAPTURE
    |--------------------------------------------------------------------------
    */

    public function capture(
        Request $request,
        ?MikrotikRouter $mikrotik = null
    ): RedirectResponse {
        $this->authorizePermission($request, 'mikrotik.view');

        $device = $mikrotik;

        if (! $device && $request->filled('device_id')) {
            $device = MikrotikRouter::findOrFail(
                $request->integer('device_id')
            );
        }

        if ($device) {
            $result = $this->captureDeviceData($device);

            $device->update([
                'status' => $result['ok'] ? 'online' : 'offline',
                'last_error' => $result['error'],
                'last_checked_at' => now(),
            ]);

            $this->auditAction(
                $request,
                'mikrotik.captured',
                $result['ok']
                    ? "Capture router {$device->name} berhasil."
                    : "Capture router {$device->name} gagal: {$result['error']}"
            );

            $message = $result['ok']
                ? "Data MikroTik {$device->name} berhasil di-capture."
                : "Capture {$device->name} gagal: {$result['error']}";

            return back()->with(
                $result['ok'] ? 'success' : 'error',
                $message
            );
        }

        $devices = MikrotikRouter::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $results = $devices->mapWithKeys(
            function (MikrotikRouter $device): array {
                $result = $this->captureDeviceData($device);

                $device->update([
                    'status' => $result['ok'] ? 'online' : 'offline',
                    'last_error' => $result['error'],
                    'last_checked_at' => now(),
                ]);

                return [$device->name => $result];
            }
        );

        $failed = $results->filter(
            fn (array $result): bool => ! $result['ok']
        );

        $this->auditAction(
            $request,
            'mikrotik.captured_all',
            $failed->isEmpty()
                ? 'Capture semua router MikroTik berhasil.'
                : 'Capture gagal: ' . $failed->keys()->implode(', ')
        );

        return back()->with(
            $failed->isEmpty() ? 'success' : 'error',
            $failed->isEmpty()
                ? 'Semua router berhasil di-capture.'
                : 'Sebagian capture gagal: ' . $failed->keys()->implode(', ')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CAPTURE DEVICE DATA
    |--------------------------------------------------------------------------
    */

    protected function captureDeviceData(
        MikrotikRouter $device
    ): array {
        try {
            $client = $this->createRouterOsClient($device);

            $identityResponse = $client
                ->query('/system/identity/print')
                ->read();

            $resourceResponse = $client
                ->query('/system/resource/print')
                ->read();

            $identity = $identityResponse[0] ?? [];
            $resource = $resourceResponse[0] ?? [];

            $data = [
                'identity' => $identity['name'] ?? null,
                'version' => $resource['version'] ?? null,
                'uptime' => $resource['uptime'] ?? null,
                'cpu_load' => $resource['cpu-load'] ?? null,
                'free_memory' => $resource['free-memory'] ?? null,
                'total_memory' => $resource['total-memory'] ?? null,
                'board_name' => $resource['board-name'] ?? null,
            ];

            $this->saveServiceStatus(
                $device,
                'api',
                'up',
                null
            );

            return [
                'ok' => true,
                'error' => null,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('JANETA RouterOS capture failed', [
                'device' => $device->name,
                'host' => $device->host,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            $this->saveServiceStatus(
                $device,
                'api',
                'down',
                $e->getMessage()
            );

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PROBE DEVICE
    |--------------------------------------------------------------------------
    */


    protected function probeDevice(
        MikrotikRouter $device
    ): array {
        $apiError = null;
        $apiOk = false;

        // ROUTEROS API
        if ($device->api_enabled && $device->is_active) {
            try {
                $client = $this->createRouterOsClient($device);

                $response = $client
                    ->query('/system/identity/print')
                    ->read();

                if (!isset($response[0]['name'])) {
                    throw new \RuntimeException(
                        'RouterOS API terhubung tetapi identity tidak ditemukan.'
                    );
                }

                $apiOk = true;

            } catch (\Throwable $e) {
                $apiError = $e->getMessage();

                Log::warning('JANETA RouterOS API test failed', [
                    'device' => $device->name,
                    'host' => $device->host,
                    'error' => $apiError,
                ]);
            }

            $this->saveServiceStatus(
                $device,
                'api',
                $apiOk ? 'up' : 'down',
                $apiError
            );

        } else {
            $apiError = !$device->is_active
                ? 'Perangkat dinonaktifkan.'
                : 'RouterOS API dinonaktifkan.';

            $this->saveServiceStatus(
                $device,
                'api',
                'disabled',
                $apiError
            );
        }

        // SSH
        if ($device->ssh_enabled && $device->is_active) {
            $ssh = $this->checkTcpPort(
                $device->host,
                (int) ($device->ssh_port ?: 22)
            );

            $this->saveServiceStatus(
                $device,
                'ssh',
                $ssh['ok'] ? 'online' : 'offline',
                $ssh['error']
            );

        } else {
            $this->saveServiceStatus(
                $device,
                'ssh',
                'disabled',
                $device->ssh_enabled
                    ? 'Perangkat dinonaktifkan.'
                    : 'SSH dinonaktifkan.'
            );
        }

        // SNMP
        if ($device->snmp_enabled && $device->is_active) {
            $snmp = $this->checkUdpPort(
                $device->host,
                (int) ($device->snmp_port ?: 161)
            );

            $this->saveServiceStatus(
                $device,
                'snmp',
                $snmp['ok'] ? 'online' : 'offline',
                $snmp['error']
            );

        } else {
            $this->saveServiceStatus(
                $device,
                'snmp',
                'disabled',
                $device->snmp_enabled
                    ? 'Perangkat dinonaktifkan.'
                    : 'SNMP dinonaktifkan.'
            );
        }

        return [
            'ok' => $apiOk,
            'error' => $apiError,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SERVICE STATUS
    |--------------------------------------------------------------------------
    */


    protected function saveServiceStatus(
        MikrotikRouter $device,
        string $service,
        string $status,
        ?string $error
    ): void {
        $columns = [
            "{$service}_status" => $status,
            "{$service}_last_checked_at" => now(),
            "{$service}_last_error" => $error,
        ];

        foreach ($columns as $column => $value) {
            if (! Schema::hasColumn($device->getTable(), $column)) {
                throw new \RuntimeException(
                    "Kolom database {$column} tidak ditemukan."
                );
            }
        }

        $device->forceFill($columns)->save();
    }

    /*
    |--------------------------------------------------------------------------
    | TCP CHECK
    |--------------------------------------------------------------------------
    */

    protected function checkTcpPort(
        string $host,
        int $port
    ): array {
        $errno = 0;
        $errstr = '';

        $connection = @fsockopen(
            $host,
            $port,
            $errno,
            $errstr,
            3
        );

        if (is_resource($connection)) {
            fclose($connection);

            return [
                'ok' => true,
                'error' => null,
            ];
        }

        return [
            'ok' => false,
            'error' => $errstr ?: "Gagal konek ke {$host}:{$port}",
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | UDP CHECK
    |--------------------------------------------------------------------------
    */

    protected function checkUdpPort(
        string $host,
        int $port
    ): array {
        $errno = 0;
        $errstr = '';

        $connection = @stream_socket_client(
            "udp://{$host}:{$port}",
            $errno,
            $errstr,
            3,
            STREAM_CLIENT_CONNECT
        );

        if (is_resource($connection)) {
            fclose($connection);

            return [
                'ok' => true,
                'error' => null,
            ];
        }

        return [
            'ok' => false,
            'error' => $errstr ?: "Gagal membuka UDP {$host}:{$port}",
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | AUDIT
    |--------------------------------------------------------------------------
    */

    protected function auditAction(
        Request $request,
        string $action,
        string $description
    ): void {
        if (method_exists($this, 'audit')) {
            $this->audit($request, $action, $description);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSION
    |--------------------------------------------------------------------------
    */

    protected function authorizePermission(
        Request $request,
        string $permission
    ): void {
        $user = $request->user();

        if (
            ! $user
            || ! method_exists($user, 'hasPermission')
            || ! $user->hasPermission($permission)
        ) {
            abort(403, 'Anda tidak memiliki akses ke fitur MikroTik.');
        }
    }
}
