<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OltSnmpSnapshot extends Model
{
    protected $fillable = ['olt_id', 'frame', 'data', 'error', 'collected_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'collected_at' => 'datetime',
        ];
    }
}
