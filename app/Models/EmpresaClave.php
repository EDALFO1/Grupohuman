<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpresaClave extends Model
{
    use HasFactory;

    protected $table = 'empresa_claves';

    protected $fillable = [
        'empresa_local_id',
        'servicio_externo_id',
        'usuario',
        'correo_registrado',
        'password',
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(EmpresaLocal::class, 'empresa_local_id');
    }

    public function servicio()
    {
        return $this->belongsTo(ServicioExterno::class, 'servicio_externo_id');
    }
}
