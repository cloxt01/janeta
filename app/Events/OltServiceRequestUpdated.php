<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OltServiceRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $request_id,
        public int $user_id,
        public string $operation,
        public string $status,
        public ?string $message = null,
        public mixed $result = null,
        public ?string $error = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OltServiceRequestUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->request_id,
            'operation' => $this->operation,
            'status' => $this->status,
            'message' => $this->message,
            'result' => $this->result,
            'error' => $this->error,
        ];
    }
}