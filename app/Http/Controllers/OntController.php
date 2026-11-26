<?php

namespace App\Http\Controllers;

use App\Models\OltServiceRequest;
use App\Services\Requests\OntRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OntController extends Controller
{
    protected AuditController $auditController;
    protected OntRequestService $ontRequestService;

    public function __construct(
        AuditController $auditController,
        OntRequestService $ontRequestService
    ) {
        $this->auditController = $auditController;
        $this->ontRequestService = $ontRequestService;
    }

    public function control(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:restart,activate,deactivate'],
            'olt' => ['required', 'string', 'max:100'],
            'frame' => ['required', 'regex:/^\d+$/'],
            'slot' => ['required', 'regex:/^\d+$/'],
            'port' => ['required', 'regex:/^\d+$/'],
            'ont_id' => ['required', 'regex:/^\d+$/'],
        ]);

        $operation = match ($data['action']) {
            'restart' => 'ont.restart',
            'activate' => 'ont.activate',
            'deactivate' => 'ont.deactivate',
        };

        abort_unless(
            $request->user()->hasPermission($operation),
            403,
            'Anda tidak memiliki izin untuk operasi ini.'
        );

        $coordinate = sprintf(
            '%s %s/%s/%s:%s',
            strtoupper($data['olt']),
            $data['frame'],
            $data['slot'],
            $data['port'],
            $data['ont_id']
        );

        $message = match ($data['action']) {
            'restart' => "Restart ONT {$coordinate} dimasukkan ke antrean.",
            'activate' => "Aktivasi ONT {$coordinate} dimasukkan ke antrean.",
            'deactivate' => "Penonaktifan ONT {$coordinate} dimasukkan ke antrean.",
        };

        $task = $this->ontRequestService->enqueue(
            $request->user(),
            $operation,
            [
                'olt' => strtoupper($data['olt']),
                'frame' => $data['frame'],
                'slot' => $data['slot'],
                'port' => $data['port'],
                'ont_id' => $data['ont_id'],
            ]
        );

        $this->auditController->audit(
            $request,
            'portal.'.$operation,
            $message,
            $task
        );

        return response()->json([
            'status' => 202,
            'message' => $message,
            'data' => [
                'request_id' => $task->uuid,
                'operation' => $task->operation,
                'status' => $task->status,
            ],
        ], 202);
    }
    public function lookup(Request $request): JsonResponse
    {
        $operation = 'ont.lookup';

        $data = $request->validate([
            'id' => ['required', 'string', 'max:100'],
            'wifi' => ['nullable', 'boolean'],
        ]);

        abort_unless(
            $request->user()->hasPermission($operation),
            403,
            'Anda tidak memiliki izin untuk operasi ini.'
        );

        $id = trim($data['id']);

        if ($id === '') {
            return response()->json([
                'message' => 'ID customer wajib diisi.',
            ], 422);
        }

        $message = 'Pencarian ONT berhasil dimasukkan ke antrean.';

        $task = $this->ontRequestService->enqueue(
            $request->user(),
            $operation,
            [
                'id' => $id,
                'wifi' => $request->boolean('wifi'),
            ],
            $message
        );

        $this->auditController->audit(
            $request,
            $operation,
            'Pencarian ONT untuk ID '.$id.' dimasukkan ke antrean.',
            $task
        );

        return response()->json([
            'status' => 202,
            'message' => $message,
            'data' => [
                'request_id' => $task->uuid,
                'operation' => $task->operation,
                'status' => $task->status,
            ],
        ], 202);
    }

    /**
     * Halaman processing universal.
     */
    public function processing(string $uuid): View
    {
        $request = $this->findOwnedRequest($uuid);

        return view('portal.ont-processing', [
            'request' => $request,
        ]);
    }

    /**
     * Endpoint untuk membaca status request.
     * Digunakan saat halaman pertama dibuka dan sebagai fallback Reverb.
     */
    public function requestStatus(string $uuid): JsonResponse
    {
        $request = $this->findOwnedRequest($uuid);

        return response()->json([
            'request_id' => $request->uuid,
            'operation'  => $request->operation,
            'status'     => $request->status,
            'message'    => $this->statusMessage($request),
            'result'     => $request->result,
            'error'      => $request->error,
            'finished'   => in_array($request->status, [
                'completed',
                'failed',
            ], true),
        ]);
    }

    /**
     * Halaman hasil operasi.
     */
    public function result(string $uuid): \Illuminate\Http\RedirectResponse | \Illuminate\Contracts\View\View|View
    {
        $request = $this->findOwnedRequest($uuid);

        if (!in_array($request->status, ['completed', 'failed'], true)) {
            return redirect()->route('portal.ont.processing', $request->uuid);
        }

        if ($request->status === 'failed') {
            return redirect()->route('portal.ont.processing', $request->uuid);
        }

        return view('portal.ont-result', [
            'request' => $request,
        ]);
    }

    /**
     * Pastikan request hanya dapat diakses pemiliknya.
     */
    private function findOwnedRequest(string $uuid): OltServiceRequest
    {
        return OltServiceRequest::query()
            ->where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    /**
     * Pesan status request.
     */
    private function statusMessage(OltServiceRequest $request): string
    {
        return match ($request->status) {
            'queued'     => 'Requests masuk antrean.',
            'running'    => 'JANETA sedang memproses request.',
            'completed'  => 'Proses berhasil diselesaikan.',
            'failed'     => 'Proses gagal dijalankan.',
            default      => 'Menunggu status proses.',
        };
    }
}
