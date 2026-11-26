<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Services\Requests\OltManagementService;
use App\Services\OltService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ToolsController extends Controller
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



    public function portMonitor(Request $request): View
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

}
