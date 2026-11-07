<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Olt extends Model
{
    protected $fillable = [
        'name', 'connection_type', 'transport_type', 'host', 'port', 'username', 'password', 'is_active',
        'status', 'last_error', 'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }
}
