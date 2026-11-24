<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\AuditLog;
use App\Services\OltService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OltController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('dashboard.olts', [
            'olts' => Olt::latest()->paginate(15),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PORT MONITOR
    |--------------------------------------------------------------------------
    */

    public function ports(Request $request): View
    {
        $request->validate([
            'olt' => ['nullable', 'exists:olts,name'],
            'frame' => ['nullable', 'regex:/^\d+$/'],
            'slot' => ['nullable', 'integer', 'between:0,11'],
        ]);

        $olts = Olt::where('is_active', true)
            ->orderBy('name')
            ->get();

        $oltName = $request->query('olt', $olts->first()?->name);
        $frame = $request->query('frame', '0');
        $slot = (int) $request->query('slot', 2);

        $scan = null;
        $error = null;

        if (! $oltName) {
            $error = 'Belum ada OLT aktif. Aktifkan OLT terlebih dahulu.';
        } else {
            $device = $olts->firstWhere('name', $oltName);

            $this->audit(
                $request,
                'olt.port_monitor',
                'Port Monitor dijalankan untuk '.$oltName.' frame '.$frame.'.',
                $device
            );

            try {
                if ($device?->snmp_enabled || $device?->connection_type === 'snmp') {
                    $snapshot = app(OltService::class)
                        ->latestSnmpSnapshot($device, $frame);

                    $scan = $snapshot?->data['monitor'];

                    if ($scan) {
                        $scan['scanned_at'] = $snapshot->collected_at;
                        $scan['snapshot_error'] = $snapshot->error;
                    } else {
                        $error = 'Belum ada data SNMP. Jalankan scheduler atau php artisan janeta:collect-snmp.';
                    }
                } else {
                    $scan = app(OltService::class)
                        ->scanAllGponPorts($oltName, $frame);
                }
            } catch (\Throwable $exception) {
                report($exception);
                $error = 'Scan port gagal: '.$exception->getMessage();
            }
        }

        return view('dashboard.olt-ports', compact(
            'olts',
            'oltName',
            'frame',
            'slot',
            'scan',
            'error'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->oltRules($request));

        $data['name'] = strtoupper($data['name']);
        $data = $this->normalizeOltServices($data);

        $olt = Olt::create($data);

        $results = app(OltService::class)->testServices($olt);
        $result = $this->summarizeServiceResults($results);

        $this->audit(
            $request,
            'olt.created',
            'OLT '.$olt->name.' ditambahkan.',
            $olt
        );

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            'OLT berhasil ditambahkan. '.$result['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION RULES
    |--------------------------------------------------------------------------
    */

    private function oltRules(Request $request, ?Olt $olt = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('olts', 'name')->ignore($olt?->id),
            ],

            'host' => ['required', 'string', 'max:255'],

            'ssh_enabled' => ['boolean'],
            'ssh_port' => ['nullable', 'integer', 'between:1,65535'],
            'ssh_username' => [
                Rule::requiredIf($request->boolean('ssh_enabled')),
                'nullable',
                'string',
                'max:255',
            ],
            'ssh_password' => ['nullable', 'string'],
            'ssh_private_key' => ['nullable', 'string'],

            'snmp_enabled' => ['boolean'],
            'snmp_port' => ['nullable', 'integer', 'between:1,65535'],
            'snmp_community' => [
                Rule::requiredIf($request->boolean('snmp_enabled')),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Olt $olt): RedirectResponse
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

        $this->audit(
            $request,
            'olt.updated',
            'OLT '.$olt->name.' diperbarui.',
            $olt
        );

        $result = $data['is_active']
            ? $this->summarizeServiceResults(
                app(OltService::class)->testServices($olt)
            )
            : [
                'ok' => true,
                'message' => 'OLT dinonaktifkan.',
            ];

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            'OLT berhasil diperbarui. '.$result['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE SERVICES
    |--------------------------------------------------------------------------
    */

    private function normalizeOltServices(
        array $data,
        ?Olt $olt = null
    ): array {
        $data['ssh_enabled'] = (bool) ($data['ssh_enabled'] ?? false);
        $data['snmp_enabled'] = (bool) ($data['snmp_enabled'] ?? false);

        $data['ssh_port'] = (int) ($data['ssh_port'] ?? 22);
        $data['snmp_port'] = (int) ($data['snmp_port'] ?? 161);

        $data['transport_type'] = 'tcp';

        $data['connection_type'] =
            $data['snmp_enabled'] && ! $data['ssh_enabled']
                ? 'snmp'
                : 'ssh';

        $data['port'] = $data['connection_type'] === 'snmp'
            ? $data['snmp_port']
            : $data['ssh_port'];

        $data['username'] = $data['connection_type'] === 'snmp'
            ? ($data['snmp_community'] ?? '')
            : ($data['ssh_username'] ?? '');

        $data['password'] = $data['connection_type'] === 'ssh'
            ? ($data['ssh_password'] ?? $olt?->ssh_password ?? '')
            : '';

        if ($olt && empty($data['ssh_password'])) {
            $data['ssh_password'] = $olt->ssh_password;
        }

        if ($olt && empty($data['ssh_private_key'])) {
            $data['ssh_private_key'] = $olt->ssh_private_key;
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE RESULT
    |--------------------------------------------------------------------------
    */

    private function summarizeServiceResults(array $results): array
    {
        $failed = collect($results)->filter(
            fn (array $result): bool => ! $result['ok']
        );

        return [
            'ok' => $failed->isEmpty(),

            'message' => $failed->isEmpty()
                ? 'Seluruh service aktif berhasil terhubung.'
                : 'Service gagal: '.$failed
                    ->map(
                        fn (array $result, string $service): string =>
                            strtoupper($service).' - '.$result['message']
                    )
                    ->implode('; '),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(Olt $olt): RedirectResponse
    {
        $this->audit(
            request(),
            'olt.deleted',
            'OLT '.$olt->name.' dihapus.',
            $olt
        );

        $olt->delete();

        return back()->with('success', 'OLT berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | TEST CONNECTION
    |--------------------------------------------------------------------------
    */

    public function test(Olt $olt, OltService $service): RedirectResponse
    {
        $result = $this->summarizeServiceResults(
            $service->testServices($olt)
        );

        $this->audit(
            request(),
            'olt.connection_test',
            'Koneksi OLT '.$olt->name.' diuji: '.$result['message'].'.',
            $olt
        );

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $olt->name.': '.$result['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TEST ALL CONNECTIONS
    |--------------------------------------------------------------------------
    */

    public function testAll(OltService $service): RedirectResponse
    {
        $results = Olt::where('is_active', true)
            ->get()
            ->mapWithKeys(
                fn (Olt $olt) => [
                    $olt->name => $this->summarizeServiceResults(
                        $service->testServices($olt)
                    ),
                ]
            );

        $failed = $results->filter(
            fn (array $result) => ! $result['ok']
        );

        $this->audit(
            request(),
            'olt.connection_test_all',
            $failed->isEmpty()
                ? 'Seluruh koneksi OLT aktif berhasil diuji.'
                : 'Uji koneksi selesai dengan kegagalan pada: '
                .$failed->keys()->implode(', ').'.'
        );

        return back()->with(
            $failed->isEmpty() ? 'success' : 'error',
            $failed->isEmpty()
                ? 'Semua OLT aktif berhasil terhubung.'
                : 'Sebagian koneksi gagal: '.$failed->keys()->implode(', ')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AUDIT
    |--------------------------------------------------------------------------
    */

    private function audit(
        Request $request,
        string $action,
        string $description,
        ?object $subject = null
    ): void {
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
