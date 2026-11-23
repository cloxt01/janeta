<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dikirim ke admin saat user menghentikan tracking.
 */
class LocationStopped implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $userId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('location-monitoring')];
    }

    public function broadcastAs(): string
    {
        return 'location.stopped';
    }

    public function broadcastWith(): array
    {
        return ['user_id' => $this->userId];
    }
}
