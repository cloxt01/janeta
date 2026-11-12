<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OltActivityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $activity
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('olt-activity'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OltActivityUpdated';
    }

    public function broadcastWith(): array
    {
        return $this->activity;
    }
}
