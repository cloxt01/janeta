<?php

namespace App\Events;

use App\Models\MikrotikActivityLog;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MikrotikActivityLogged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public array $activity;

    public function __construct(MikrotikActivityLog $log)
    {
        $log->loadMissing('router');

        $this->activity = [
            'id' => $log->id,
            'router_name' => $log->router?->name ?? 'Unknown Router',
            'event_type' => $log->event_type,
            'topics' => $log->topics,

            // Waktu asli dari MikroTik
            'router_date' => $log->router_date
                ? \Illuminate\Support\Carbon::parse($log->router_date)->format('Y-m-d')
                : null,

            'router_time' => $log->router_time,
            'router_timezone' => $log->router_timezone,

            'router_user' => $log->router_user,
            'action' => $log->action,
            'message' => $log->message,

            // Waktu pengambilan oleh JANETA
            'collected_at' => $log->collected_at?->toIso8601String(),
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('mikrotik.activity'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'mikrotik.activity.logged';
    }

    public function broadcastWith(): array
    {
        return [
            'activity' => $this->activity,
        ];
    }
}
