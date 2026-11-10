<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\OltServiceRequest;
use App\Jobs\ProcessOltServices;
use App\Events\OltServiceRequestUpdated;
use Illuminate\Http\JsonResponse;
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
}
