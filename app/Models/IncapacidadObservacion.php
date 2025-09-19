<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncapacidadObservacion extends Model
{
    protected $table = 'incapacidad_observaciones';

    protected $fillable = [
        'incapacidad_id',
        'nota',
    ];

    public function incapacidad(): BelongsTo
    {
        return $this->belongsTo(Incapacidad::class);
    }
}
