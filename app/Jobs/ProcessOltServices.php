<?php

namespace App\Jobs;

use App\Events\OltServiceRequestUpdated;
use App\Models\Olt;
use App\Models\OltServiceRequest;
use App\Services\OltService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessOltServices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public int $requestId
    ) {
        $this->onQueue('olt');
    }

    public function middleware(): array
    {
        $task = OltServiceRequest::find($this->requestId);

        return [
            (new WithoutOverlapping(
                'olt:'.($task?->olt_id ?? 'global')
            ))
                ->releaseAfter(10)
                ->expireAfter(360),
        ];
    }

    public function handle(OltService $service): void
    {
        $task = OltServiceRequest::findOrFail($this->requestId);

        $startedAt = microtime(true);

        $task->update([
            'status' => 'running',
            'started_at' => now(),
            'error' => null,
        ]);

        $this->broadcast($task, 'running', 'Proses OLT sedang dijalankan.');

        Log::info('OLT job started', [
            'request_id' => $task->uuid,
            'operation' => $task->operation,
            'olt_id' => $task->olt_id,
        ]);

        try {
            $params = $task->payload ?? [];

            $result = match ($task->operation) {

                // ONT LOOKUP DASHBOARD
                'ont.lookup' => $this->lookupOnt($service, $params),

                // ONT LOOKUP
                'ont.description' => $service->apiByDescription(
                    $params['id']
                ),

                'ont.version' => $service->apiVersion(
                    $params['id']
                ),

                'ont.serial' => $service->bySerial(
                    $params['sn']
                ),

                'ont.find' => $service->findPortOnt(
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id']
                ),

                // ONT CONTROL
                'ont.restart' => $service->restart(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id']
                ),

                'ont.activate' => $service->setOntState(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id'],
                    true
                ),

                'ont.deactivate' => $service->setOntState(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id'],
                    false
                ),

                'ont.wlan' => $service->wlan(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id']
                ),

                // PORT MONITORING
                'ont.port_scan' => $service->findPort(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port']
                ),

                'olt.scan_slot' => $service->scanGponPorts(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port_count'] ?? 16
                ),

                'olt.scan_all' => $service->scanAllGponPorts(
                    $params['olt'],
                    $params['frame'],
                    $params['slot_count'] ?? 12,
                    $params['port_count'] ?? 16
                ),

                // OLT MONITORING
                'olt.alarms' => $service->scanActiveAlarms(),

                'olt.connection_test' => $service->testConnection(
                    $params['olt']
                ),

                'olt.services_test' => $service->testServices(
                    Olt::findOrFail($task->olt_id)
                ),

                'olt.snmp_snapshot' => $service->collectSnmpSnapshot(
                    Olt::findOrFail($task->olt_id),
                    $params['frame'] ?? '0'
                ),

                default => throw new \InvalidArgumentException(
                    "Unknown OLT operation: {$task->operation}"
                ),
            };

            $duration = (int) round(
                (microtime(true) - $startedAt) * 1000
            );

            $task->update([
                'status' => 'completed',
                'result' => $result,
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            $this->broadcast(
                $task->fresh(),
                'completed',
                $result === null
                    ? 'Data ONT tidak ditemukan.'
                    : 'Proses OLT berhasil diselesaikan.',
                $result
            );

            Log::info('OLT job completed', [
                'request_id' => $task->uuid,
                'operation' => $task->operation,
                'duration_ms' => $duration,
            ]);

        } catch (Throwable $e) {
            $duration = (int) round(
                (microtime(true) - $startedAt) * 1000
            );

            $task->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            $this->broadcast(
                $task->fresh(),
                'failed',
                'Proses OLT gagal.',
                null,
                $e->getMessage()
            );

            Log::error('OLT job failed', [
                'request_id' => $task->uuid,
                'operation' => $task->operation,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            throw $e;
        }
    }

    /**
     * Lookup ONT beserta data WLAN jika diminta.
     */
    private function lookupOnt(OltService $service, array $params): ?array
    {
        $id = trim((string) ($params['id'] ?? ''));

        if ($id === '') {
            throw new \InvalidArgumentException('ID customer wajib diisi.');
        }

        $result = $service->apiVersion($id);

        if (! $result) {
            return null;
        }

        if (! empty($params['wifi'])) {
            $result['wlan'] = $service->wlan(
                $result['olt'],
                $result['frame'],
                $result['slot'],
                $result['port'],
                $result['ontId']
            );
        }

        return $result;
    }

    /**
     * Broadcast update ke private channel pemilik request.
     */
    private function broadcast(
        ?OltServiceRequest $task,
        string $status,
        ?string $message = null,
        mixed $result = null,
        ?string $error = null
    ): void {
        if (! $task || ! $task->user_id) {
            return;
        }

        event(new OltServiceRequestUpdated(
            request_id: $task->uuid,
            user_id: (int) $task->user_id,
            operation: $task->operation,
            status: $status,
            message: $message,
            result: $result,
            error: $error,
        ));
    }

    public function failed(?Throwable $exception): void
    {
        $task = OltServiceRequest::find($this->requestId);

        if (! $task || in_array($task->status, ['completed', 'failed'], true)) {
            return;
        }

        $error = $exception?->getMessage() ?? 'Queue job failed.';

        $task->update([
            'status' => 'failed',
            'error' => $error,
            'finished_at' => now(),
        ]);

        $this->broadcast(
            $task->fresh(),
            'failed',
            'Queue job gagal dijalankan.',
            null,
            $error
        );
    }
}