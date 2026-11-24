<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MikrotikRouter extends Model
{
    protected $table = 'mikrotik_routers';

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'is_active',
        'status',
        'last_error',
        'last_checked_at',
        'api_enabled',
        'api_port',
        'api_ssl',
        'api_username',
        'api_password',
        'ssh_enabled',
        'ssh_port',
        'ssh_username',
        'ssh_password',
        'ssh_private_key',
        'snmp_enabled',
        'snmp_port',
        'snmp_community',
        // Di $fillable:
        'api_status',
        'api_last_checked_at',
        'api_last_error',
        'ssh_status',
        'ssh_last_checked_at',
        'ssh_last_error',
        'snmp_status',
        'snmp_last_checked_at',
        'snmp_last_error',
    ];

    protected $hidden = [
        'password',
        'api_password',
        'ssh_password',
        'ssh_private_key',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
            'api_enabled' => 'boolean',
            'api_port' => 'integer',
            'api_ssl' => 'boolean',
            'ssh_enabled' => 'boolean',
            'ssh_port' => 'integer',
            'snmp_enabled' => 'boolean',
            'snmp_port' => 'integer',
            'api_last_checked_at' => 'datetime',
            'ssh_last_checked_at' => 'datetime',
            'snmp_last_checked_at' => 'datetime',
        ];
    }

    protected function setEncryptedAttribute(string $key, mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes[$key] = null;
            return;
        }

        $this->attributes[$key] = Crypt::encryptString((string) $value);
    }

    protected function getEncryptedAttribute(string $key): ?string
    {
        $raw = $this->attributes[$key] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (DecryptException) {
            return (string) $raw;
        }
    }

    public function setPasswordAttribute(mixed $value): void
    {
        $this->setEncryptedAttribute('password', $value);
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->getEncryptedAttribute('password');
    }

    public function setApiPasswordAttribute(mixed $value): void
    {
        $this->setEncryptedAttribute('api_password', $value);
    }

    public function getApiPasswordAttribute(): ?string
    {
        return $this->getEncryptedAttribute('api_password');
    }

    public function setSshPasswordAttribute(mixed $value): void
    {
        $this->setEncryptedAttribute('ssh_password', $value);
    }

    public function getSshPasswordAttribute(): ?string
    {
        return $this->getEncryptedAttribute('ssh_password');
    }

    public function setSshPrivateKeyAttribute(mixed $value): void
    {
        $this->setEncryptedAttribute('ssh_private_key', $value);
    }

    public function getSshPrivateKeyAttribute(): ?string
    {
        return $this->getEncryptedAttribute('ssh_private_key');
    }
}
