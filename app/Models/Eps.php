<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Eps extends Model
{
    protected $table = 'eps';

    protected $fillable = [
        'nombre',
        'codigo',
        'porcentaje',
    ];

    public static function rules($id = null)
    {
        return [
            // Solo letras, espacios, puntos y paréntesis
            'nombre' => [
                'required',
                'regex:/^[A-Za-zÁÉÍÓÚÜáéíóúüÑñ\s\.\(\)]+$/u',
                'max:255',
            ],
            'codigo'     => ['required', 'alpha_num'],
            'porcentaje' => [
                'required',
                'numeric',
                'regex:/^\d+(\.\d{1,4})?$/',
            ],
        ];
    }

    public function usuarioExterno()
    {
        return $this->hasMany(UsuarioExterno::class);
    }
}
