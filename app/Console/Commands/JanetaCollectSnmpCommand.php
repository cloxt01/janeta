<?php

namespace App\Console\Commands;

use App\Models\Olt;
use App\Services\OltService;
use Illuminate\Console\Command;

class JanetaCollectSnmpCommand extends Command
{
    protected $signature = 'janeta:collect-snmp {--frame=0}';
    protected $description = 'Collect OLT SNMP data and save the latest snapshot';

    public function handle(OltService $olt): int
    {

        $count = 0;
        foreach (Olt::where('is_active', true)->where(function ($query) {
            $query->where('snmp_enabled', true)->orWhere('connection_type', 'snmp');
        })->get() as $device) {
            try {
                $olt->collectSnmpSnapshot($device, (string) $this->option('frame'));
                $count++;
            } catch (\Throwable $exception) {
                report($exception);
                $this->error("{$device->name}: {$exception->getMessage()}");
            }
        }

        $this->info("SNMP snapshot collected for {$count} OLT.");

        return self::SUCCESS;
    }
}
