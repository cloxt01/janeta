<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OntTypeMapping extends Model
{
    protected $fillable = ['equipment_id', 'brand', 'merek_id', 'tipe_id', 'wlan_band'];

    public function merek(): BelongsTo
    {
        return $this->belongsTo(Merek::class);
    }

    public function tipe(): BelongsTo
    {
        return $this->belongsTo(Tipe::class);
    }
}
