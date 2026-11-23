<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhitelistEntry extends Model
{

    public const ADMIN_ROLES = ['admin', 'super_admin'];
    protected $fillable = ['chat_id', 'name', 'role'];

    public function isAdmin(): bool
    {
        return in_array($this->role, self::ADMIN_ROLES);
    }
}
