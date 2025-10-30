<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioExterno extends Model
{
    use HasFactory;

    protected $table = 'servicios_externos';

    protected $fillable = [
        'nombre',
        'url_login', // 👈 este es el campo correcto
        'tipo',
    ];

    public function claves()
    {
        return $this->hasMany(EmpresaClave::class, 'servicio_externo_id');
    }

    // 👇 Esto facilita acceder con $servicio->url
    public function getUrlAttribute()
    {
        return $this->url_login;
    }
}
