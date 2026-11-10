<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OltServiceRequest extends Model
{
    protected $fillable = [
        'uuid',
        'olt_id',
        'user_id',
        'operation',
        'status',
        'payload',
        'result',
        'error',
        'duration_ms',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }
}
