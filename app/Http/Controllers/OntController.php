<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\OltServiceRequest;
use App\Jobs\ProcessOltServices;
use App\Events\OltServiceRequestUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OntController extends Controller
{
    public function checkDescription(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'max:100'],
        ]);

        $id = trim($data['id']);

        if ($id === '') {
            return response()->json([
                'message' => 'Parameter ID wajib diisi.',
            ], 422);
        }

        $this->audit(
            $request,
            'api.ont_description_lookup',
            'Request pencarian ONT dimasukkan ke queue untuk ID '.$id.'.'
        );

        return $this->enqueue(
            $request,
            'ont.description',
            ['id' => $id],
            'Request pencarian ONT berhasil dimasukkan ke antrean.'
        );
    }

    public function version(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'max:100'],
        ]);

        $id = trim($data['id']);

        if ($id === '') {
            return response()->json([
                'message' => 'Parameter ID wajib diisi.',
            ], 422);
        }

        $this->audit(
            $request,
            'api.ont_version_lookup',
            'Request pencarian versi ONT dimasukkan ke queue untuk ID '.$id.'.'
        );

        return $this->enqueue(
            $request,
            'ont.version',
            ['id' => $id],
            'Request pencarian versi ONT berhasil dimasukkan ke antrean.'
        );
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

        $this->audit(
            $request,
            'portal.'.$operation,
            $message
        );

        return $this->enqueue(
            $request,
            $operation,
            [
                'olt' => strtoupper($data['olt']),
                'frame' => $data['frame'],
                'slot' => $data['slot'],
                'port' => $data['port'],
                'ont_id' => $data['ont_id'],
            ],
            $message
        );
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'max:100'],
            'wifi' => ['nullable', 'boolean'],
        ]);

        $id = trim($data['id']);

        if ($id === '') {
            return response()->json([
                'message' => 'ID customer wajib diisi.',
            ], 422);
        }

        $wifi = $request->boolean('wifi');

        $this->audit(
            $request,
            'ont.lookup',
            'Pencarian ONT dimasukkan ke queue untuk ID '.$id.'.'
        );

        return $this->enqueue(
            $request,
            'ont.lookup',
            [
                'id' => $id,
                'wifi' => $wifi,
            ],
            'Pencarian ONT berhasil dimasukkan ke antrean.'
        );
    }

    /**
     * Membuat request dan memasukkannya ke queue.
     */
    private function enqueue(
        Request $request,
        string $operation,
        array $payload,
        string $message
    ): JsonResponse {
        $task = OltServiceRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()?->id,
            'operation' => $operation,
            'status' => 'queued',
            'payload' => $payload,
        ]);

        if ($task->user_id) {
            event(new OltServiceRequestUpdated(
                request_id: $task->uuid,
                user_id: (int) $task->user_id,
                operation: $task->operation,
                status: 'queued',
                message: $message,
            ));
        }

        ProcessOltServices::dispatch($task->id)
            ->onQueue('olt')
            ->afterCommit();

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
     * Mencatat aktivitas API.
     */
    private function audit(
        Request $request,
        string $action,
        string $description
    ): void {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
        ]);
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
    public function result(string $uuid): View
    {
        $request = $this->findOwnedRequest($uuid);

        // Jangan tampilkan halaman hasil sebelum proses selesai.
        if (!in_array($request->status, ['completed', 'failed'], true)) {
            return redirect()->route('portal.ont.processing', $request->uuid);
        }

        // Request gagal tetap ditampilkan pada halaman processing.
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
            'queued'     => 'Request masuk antrean.',
            'running'    => 'JANETA sedang memproses request.',
            'completed'  => 'Proses berhasil diselesaikan.',
            'failed'     => 'Proses gagal dijalankan.',
            default      => 'Menunggu status proses.',
        };
    }
}
