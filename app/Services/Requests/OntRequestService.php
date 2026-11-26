<?php

namespace App\Services\Requests;

use App\Events\OltServiceRequestUpdated;
use App\Jobs\ProcessOltServices;
use App\Models\OltServiceRequest;
use App\Models\User;
use Illuminate\Support\Str;

class OntRequestService
{
    /**
     * Membuat request ONT dan memasukkannya ke queue.
     */
    public function enqueue(
        User $user,
        string $operation,
        array $payload
    ): OltServiceRequest {
        $task = OltServiceRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'operation' => $operation,
            'status' => 'queued',
            'payload' => $payload,
        ]);

        event(new OltServiceRequestUpdated(
            request_id: $task->uuid,
            user_id: (int) $task->user_id,
            operation: $task->operation,
            status: 'queued',
            message: $this->queuedMessage($operation),
        ));

        ProcessOltServices::dispatch($task->id)
            ->onQueue('olt')
            ->afterCommit();

        return $task;
    }

    /**
     * Mengambil request berdasarkan UUID dan pemiliknya.
     */
    public function findOwnedRequest(
        string $uuid,
        User $user
    ): OltServiceRequest {
        return OltServiceRequest::query()
            ->where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    /**
     * Pesan ketika request masuk antrean.
     */
    public function queuedMessage(string $operation): string
    {
        return match ($operation) {
            'ont.restart' => 'Restart ONT dimasukkan ke antrean.',
            'ont.activate' => 'Aktivasi ONT dimasukkan ke antrean.',
            'ont.deactivate' => 'Penonaktifan ONT dimasukkan ke antrean.',
            'ont.lookup' => 'Pencarian ONT dimasukkan ke antrean.',
            'ont.description' => 'Pencarian deskripsi ONT dimasukkan ke antrean.',
            'ont.version' => 'Pencarian versi ONT dimasukkan ke antrean.',
            default => 'Request dimasukkan ke antrean.',
        };
    }

    /**
     * Pesan berdasarkan status request.
     */
    public function statusMessage(OltServiceRequest $request): string
    {
        return match ($request->status) {
            'queued' => 'Request masuk antrean.',
            'running' => 'JANETA sedang memproses request.',
            'completed' => 'Proses berhasil diselesaikan.',
            'failed' => 'Proses gagal dijalankan.',
            default => 'Menunggu status proses.',
        };
    }
}
