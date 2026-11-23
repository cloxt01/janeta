<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dikirim ke admin setiap ada update GPS.
 *
 * ShouldBroadcastNow = langsung dikirim ke Reverb tanpa queue worker.
 * Kalau user banyak, ganti ke ShouldBroadcast dan jalankan `php artisan queue:work`
 * supaya request GPS tidak menunggu Reverb.
 */
class LocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $location) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('location-monitoring')];
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    public function broadcastWith(): array
    {
        return $this->location;
    }
}
