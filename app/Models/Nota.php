<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas';

    protected $fillable = [
        'creado_por_id',
        'titulo',
        'descripcion',
        'tipo',
        'estado',
        'fecha_vencimiento',
        'fecha_resuelto',
        'resuelto_por_id',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_resuelto'    => 'datetime',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    public function resueltoPor()
    {
        return $this->belongsTo(User::class, 'resuelto_por_id');
    }

    // Ejemplo de scope para listar solo pendientes
    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['pendiente', 'en_proceso']);
    }
}
