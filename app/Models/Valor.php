<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Valor extends Model
{
    protected $table = 'valores';

    protected $fillable = [
        'empresa_local_id','fecha_inicio','fecha_fin',
        'salario','administracion','activa',
    ];

    protected $casts = [
        'fecha_inicio'   => 'date',
        'fecha_fin'      => 'date',
        'activa'         => 'boolean',
        'salario'        => 'decimal:2',
        'administracion' => 'decimal:2',
    ];

    // Relaciones
    public function empresaLocal()
    {
        return $this->belongsTo(\App\Models\EmpresaLocal::class, 'empresa_local_id');
    }

    // Scopes
    public function scopeDeEmpresa($q, int $empresaLocalId)
    {
        return $q->where('empresa_local_id', $empresaLocalId);
    }

    public function scopeActiva($q)
    {
        return $q->where('activa', true);
    }

    public function scopeVigenteEn($q, $fecha)
    {
        return $q->whereDate('fecha_inicio', '<=', $fecha)
                 ->where(function ($qq) use ($fecha) {
                    $qq->whereNull('fecha_fin')
                       ->orWhereDate('fecha_fin', '>=', $fecha);
                 })
                 ->orderByDesc('fecha_inicio');
    }
}
