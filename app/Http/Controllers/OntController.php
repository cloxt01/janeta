<?php

namespace App\Http\Controllers;

use App\Services\OltService;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OntController extends Controller
{
    public function __construct(private OltService $olt) {}

    public function checkDescription(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if (!$id) return response()->json(['status' => 400, 'message' => 'Parameter "id" (ID Customer) wajib disertakan.'], 400);
        $this->audit($request, 'api.ont_description_lookup', 'API pencarian deskripsi ONT dijalankan untuk ID '.$id.'.');
        $data = $this->olt->apiByDescription($id);
        if (!$data) return response()->json(['status' => 404, 'message' => "Data ONT dengan ID '{$id}' tidak ditemukan di OLT."], 404);
        return response()->json(['status' => 200, 'message' => 'Data ONT berhasil ditarik', 'data' => ['customer' => ['id' => $data['descriptionTarget'], 'name' => $data['customerName'], 'serial_number' => $data['sn'], 'frame' => $data['frame'], 'slot' => $data['slot'], 'port' => $data['port'], 'ontID' => $data['ontID']]]]);
    }

    public function version(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if (!$id) return response()->json(['status' => 400, 'message' => 'Parameter "id" wajib disertakan.'], 400);
        $this->audit($request, 'api.ont_version_lookup', 'API pencarian versi ONT dijalankan untuk ID '.$id.'.');
        $data = $this->olt->apiVersion($id);
        if (!$data) return response()->json(['status' => 404, 'message' => "Data versi ONT pada ID Customer {$id} tidak ditemukan di OLT."], 404);

        $available = $data['versionAvailable'] ?? true;

        return response()->json([
            'status' => 200,
            'message' => $available
                ? 'Data spesifikasi ONT berhasil ditarik'
                : 'ONT ditemukan, tetapi data spesifikasi tidak dapat dibaca. '.$data['versionReason'],
            'data' => [
                'location' => ['id_customer' => $data['idcust'], 'frame' => $data['frame'], 'slot' => $data['slot'], 'port' => $data['port'], 'ont_id' => $data['ontId']],
                'ont' => [
                    'run_state' => $data['runState'],
                    'version_available' => $available,
                    'reason' => $data['versionReason'] ?? null,
                ],
                'specification' => ['serial_number' => $data['sn'], 'brand' => $data['brand'], 'type' => $data['type'], 'wlan_band' => $data['wlanType']],
            ],
        ]);
    }

    private function audit(Request $request, string $action, string $description): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
        ]);
    }
}
