<?php

namespace App\Console\Commands;

use App\Models\MikrotikRouter;
use App\Services\Router\MikrotikLogCollector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CollectMikrotikLogs extends Command
{
    protected $signature = 'janeta:mikrotik-logs';

    protected $description = 'Mengambil system, account, dan config history dari MikroTik';

    public function handle(MikrotikLogCollector $collector): int
    {
        $devices = MikrotikRouter::query()
            ->where('is_active', true)
            ->where('api_enabled', true)
            ->orderBy('id')
            ->get();

        foreach ($devices as $device) {
            try {
                $result = $collector->collect($device);

                $message = sprintf(
                    '%s: system=%d, account=%d, history=%d',
                    $device->name,
                    $result['system'],
                    $result['account'],
                    $result['history']
                );

                $this->info($message);
                Log::info('JANETA MikroTik logs collected', [
                    'router' => $device->name,
                    ...$result,
                ]);
            } catch (\Throwable $e) {
                $this->error(
                    "{$device->name}: {$e->getMessage()}"
                );

                Log::error('JANETA MikroTik log collection failed', [
                    'router' => $device->name,
                    'host' => $device->host,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }
}
