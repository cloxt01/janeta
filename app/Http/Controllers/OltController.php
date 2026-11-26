<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Services\Requests\OltManagementService;
use App\Services\OltService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OltController extends Controller
{
    protected AuditController $auditController;
    protected OltManagementService $oltManagementService;

    public function __construct(
        AuditController $auditController,
        OltManagementService $oltManagementService
    ) {
        $this->auditController = $auditController;
        $this->oltManagementService = $oltManagementService;
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('dashboard.devices.olts', [
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

            $this->auditController->audit(
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

        return view('dashboard.tools.port-monitor', compact(
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

        $result = $this->oltManagementService->create($data);

        $olt = $result['olt'];
        $serviceResult = $result['result'];

        $this->auditController->audit(
            $request,
            'olt.created',
            'OLT '.$olt->name.' ditambahkan.',
            $olt
        );

        return back()->with(
            $serviceResult['ok'] ? 'success' : 'error',
            'OLT berhasil ditambahkan. '.$serviceResult['message']
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

        $data['is_active'] = $request->boolean('is_active');

        $result = $this->oltManagementService->update($olt, $data);

        $olt = $result['olt'];
        $serviceResult = $result['result'];

        $this->auditController->audit(
            $request,
            'olt.updated',
            'OLT '.$olt->name.' diperbarui.',
            $olt
        );

        return back()->with(
            $serviceResult['ok'] ? 'success' : 'error',
            'OLT berhasil diperbarui. '.$serviceResult['message']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request, Olt $olt): RedirectResponse
    {
        $this->auditController->audit(
            $request,
            'olt.deleted',
            'OLT '.$olt->name.' dihapus.',
            $olt
        );

        $this->oltManagementService->delete($olt);

        return back()->with('success', 'OLT berhasil dihapus.');
    }
    /*
    |--------------------------------------------------------------------------
    | TEST CONNECTION
    |--------------------------------------------------------------------------
    */

    public function test(
        Request $request,
        Olt $olt
    ): RedirectResponse {
        $result = $this->oltManagementService->testServices($olt);

        $this->auditController->audit(
            $request,
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

    public function testAll(Request $request): RedirectResponse
    {
        $result = $this->oltManagementService->testAll();

        $this->auditController->audit(
            $request,
            'olt.connection_test_all',
            $result['ok']
                ? 'Seluruh koneksi OLT aktif berhasil diuji.'
                : 'Uji koneksi selesai dengan kegagalan pada: '
                .$result['failed']->keys()->implode(', ').'.'
        );

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'Semua OLT aktif berhasil terhubung.'
                : 'Sebagian koneksi gagal: '
                .$result['failed']->keys()->implode(', ')
        );
    }

}
