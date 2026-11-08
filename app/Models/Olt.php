<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Olt extends Model
{
    protected $fillable = [
        'name', 'connection_type', 'transport_type', 'host', 'port', 'username', 'password',
        'ssh_enabled', 'ssh_port', 'ssh_username', 'ssh_password', 'ssh_private_key',
        'ssh_status', 'ssh_last_checked_at', 'ssh_last_error',
        'snmp_enabled', 'snmp_port', 'snmp_community', 'snmp_status', 'snmp_last_checked_at', 'snmp_last_error', 'is_active',
        'status', 'last_error', 'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ssh_enabled' => 'boolean',
            'snmp_enabled' => 'boolean',
            'last_checked_at' => 'datetime',
            'ssh_last_checked_at' => 'datetime',
            'snmp_last_checked_at' => 'datetime',
        ];
    }
}
