<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merek extends Model
{
    protected $table = 'tbl_merek';

    protected $fillable = ['nama'];

    public function tipe(): HasMany
    {
        return $this->hasMany(Tipe::class);
    }
}
