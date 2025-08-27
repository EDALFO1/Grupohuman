<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asesor extends Model
{
    use HasFactory;
    protected $table = 'asesores';

    protected $fillable = [
        'documento_id',
        'numero_documento',
        'nombre',
        'direccion',
        'telefono',
        'email',
    ];

    /**
     * Relación: Un asesor pertenece a un tipo de documento
     */
    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }
}
