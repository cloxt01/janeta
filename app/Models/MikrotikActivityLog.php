<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikrotikActivityLog extends Model
{
    protected $fillable = [
        'mikrotik_router_id',
        'event_type',
        'topics',
        'router_date',
        'router_time',
        'router_timezone',
        'router_user',
        'action',
        'message',
        'fingerprint',
        'raw_data',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'collected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (MikrotikActivityLog $log) {
            event(new \App\Events\MikrotikActivityLogged($log));
        });
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(
            MikrotikRouter::class,
            'mikrotik_router_id'
        );
    }
}
