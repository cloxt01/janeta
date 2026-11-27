
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\CollectMikrotikLogs;
use App\Models\MikrotikRouter;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('janeta:collect-snmp')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/collect-snmp.log'));


/*
|--------------------------------------------------------------------------
| MIKROTIK LOG COLLECTOR
|--------------------------------------------------------------------------
*/
Schedule::call(function () {

    \Log::info('[JANETA] Scheduler MikroTik triggered');

    MikrotikRouter::query()
        ->where('is_active', true)
        ->where('api_enabled', true)
        ->select('id')
        ->each(function ($router) {

            CollectMikrotikLogs::dispatch($router->id)
                ->onQueue('router');

            \Log::info('[JANETA] MikroTik job dispatched', [
                'router_id' => $router->id,
            ]);
        });

})
    ->everyMinute()
    ->name('janeta-mikrotik-log-collector')
    ->withoutOverlapping(1);
