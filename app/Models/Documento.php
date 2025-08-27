<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = 'documentos';

    protected $fillable = [
        'nombre', // agrega más campos si los tienes
    ];

    public static function rules($id = null)
    {
        return [
            'nombre' => ['required', 'regex:/^[A-Za-z\s]+$/', 'max:255'],
        ];
    }
    public function empresaLocales()
    {
        return $this->hasMany(EmpresaLocal::class);
    }

    public function empresaExternas()
    {
        return $this->hasMany(EmpresaExterna::class);
    }

    public function usuarioExterno()
    {
        return $this->hasMany(UsuarioExterno::class);
    }
    public function asesores()
   {
    return $this->hasMany(Asesor::class);
   }
}
