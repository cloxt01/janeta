<?php

namespace App\Jobs;

use App\Models\MikrotikRouter;
use App\Services\Router\MikrotikLogCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class CollectMikrotikLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(
        public int $routerId
    ) {
        $this->onQueue('router');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                'mikrotik-collect-' . $this->routerId
            ))
                ->releaseAfter(10)
                ->expireAfter(180),
        ];
    }

    public function handle(
        MikrotikLogCollector $collector
    ): void {

        $device = MikrotikRouter::find($this->routerId);

        if (
            ! $device ||
            ! $device->is_active ||
            ! $device->api_enabled
        ) {
            return;
        }

        try {

            /*
             * Ambil koneksi dari persistent connection manager.
             *
             * Jika koneksi masih hidup:
             * gunakan koneksi sebelumnya.
             *
             * Jika koneksi mati:
             * manager membuat koneksi baru.
             */
            $client = $collector->connectPersistent($device);

            /*
             * Jalankan semua proses menggunakan
             * client yang sama.
             */
            $result = $collector->collect(
                $device,
                $client
            );

            Log::info('[JANETA] MikroTik collection completed', [
                'router_id' => $device->id,
                'router' => $device->name,
                'result' => $result,
            ]);

        } catch (Throwable $e) {

            Log::error('[JANETA] MikroTik collection failed', [
                'router_id' => $device->id,
                'router' => $device->name,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
