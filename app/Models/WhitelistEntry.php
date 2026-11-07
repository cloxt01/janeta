<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhitelistEntry extends Model
{
    protected $fillable = ['chat_id', 'name', 'is_admin'];

    protected function casts(): array
    {
        return ['is_admin' => 'boolean'];
    }
}
