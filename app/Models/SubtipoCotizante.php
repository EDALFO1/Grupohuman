<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubtipoCotizante extends Model
{
    use HasFactory;

    protected $table = 'subtipo_cotizantes';

    protected $fillable = ['codigo', 'nombre'];

     public function usuarioExterno()
    {
        return $this->hasMany(UsuarioExterno::class);
    }
}
