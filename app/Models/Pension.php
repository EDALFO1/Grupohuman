<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pension extends Model
{
    //
    protected $table = 'pensions';

    protected $fillable = ['nombre', 'codigo', 'porcentaje'];

    public static function rules($id = null)
    {
        return [
            'nombre' => ['required', 'regex:/^[\pL\s\-]+$/u'],
            'codigo' => ['required', 'regex:/^[A-Za-z0-9\-]+$/'],
            'porcentaje' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,4})?$/'],
            
        ];
    }
    public function usuarioExterno()
    {
        return $this->hasMany(UsuarioExterno::class);
    }
}
