<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tipe extends Model
{
    protected $table = 'ont_types';

    protected $fillable = ['merek_id', 'nama'];

    public function merek(): BelongsTo
    {
        return $this->belongsTo(Merek::class);
    }
}
