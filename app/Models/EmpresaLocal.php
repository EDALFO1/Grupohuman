<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Documento;

class EmpresaLocal extends Model
{
    protected $table = 'empresa_local';

    protected $fillable = [
        'documento_id',
        'numero_documento',
        'nombre',
        'direccion',
        'telefono',
        'contacto',
        'activo',
    ];

  
    public function empresasLocales()
{
    return $this->hasMany(EmpresaLocal::class);
}
   public static function rules($id = null)
    {
        return [
            'documento_id' => 'required',
            'numero_documento' => 'required|numeric',
            'nombre' => [
    'required',
    'regex:/^[A-Za-zÁÉÍÓÚÜáéíóúüÑñ\s\.]+$/u',
    'max:255'
],

            'direccion' => 'required|string',
            'telefono' => 'required|numeric',
            'contacto' => ['required', 'regex:/^[A-Za-z\s]+$/', 'max:255'],
            'activo' => 'required|boolean',
        ];
    }

    // Relación con UsuarioExterno
    public function documento()
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
