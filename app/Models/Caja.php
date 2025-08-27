<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    //
    protected $table = 'cajas';
    protected $fillable = ['nombre', 'codigo', 'porcentaje'];

    public static function rules($id = null)
    {
        return [
            'nombre' => ['required', 'regex:/^[\pL\s]+$/u'],
            'codigo' => ['required', 'alpha_num'],
            'porcentaje' => ['required', 'numeric', 'regex:/^\d{1,4}(\.\d{1,2})?$/']
        ];
    }
    public function usuarioExterno()
    {
        return $this->hasMany(UsuarioExterno::class);
    }
}
