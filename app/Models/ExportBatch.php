<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportBatch extends Model
{
    protected $fillable = [
        'empresa_local_id', 'codigo', 'periodo', 'recibos_count', 'total',
    ];

    protected $casts = [
        'recibos_count' => 'integer',
        'total'         => 'decimal:2',
    ];

    public function recibos()
    {
        return $this->hasMany(\App\Models\Recibo::class, 'export_batch_id');
    }

    public function empresaLocal()
    {
        return $this->belongsTo(\App\Models\EmpresaLocal::class, 'empresa_local_id');
    }
}
