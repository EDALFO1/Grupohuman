<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaExterna extends Model
{
    protected $table = 'empresa_externas';

    protected $fillable = [
        'documento_id',
        'numero',
        'nombre',
        'direccion',
        'telefono',
        'contacto',
        'activo'
    ];

   

    public static function rules($id = null)
    {
        return [
            'documento_id' => 'required|exists:documentos,id',
            'numero' => 'required|string|max:255',
            'nombre' => ['required', 'regex:/^[\pL\s\.,\-\/]+$/u'],
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|numeric',
            'contacto' =>['required', 'regex:/^[\pL\pN\s\-]+$/u'],
            'activo' => 'required|boolean',
        ];
    }
     // Relación con UsuarioExterno
  


    public function Documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function usuarioExterno()
    {
        return $this->hasMany(UsuarioExterno::class);
    }
    public function remisiones()
{
    return $this->hasMany(Remision::class);
}
}
