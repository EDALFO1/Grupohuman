<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoUsuario extends Model
{
    protected $table = 'periodo_usuarios';

    protected $fillable = [
        'empresa_local_id','usuario_externo_id','periodo','estado','recibo_id',
    ];

    public function usuarioExterno(){ return $this->belongsTo(UsuarioExterno::class); }
    public function empresaLocal(){ return $this->belongsTo(EmpresaLocal::class, 'empresa_local_id'); }
    public function recibo(){ return $this->belongsTo(Recibo::class); }

    // Scopes útiles
    public function scopeDeEmpresa($q, $empresaId){ return $q->where('empresa_local_id', $empresaId); }
    public function scopePeriodo($q, string $periodo){ return $q->where('periodo', $periodo); }
    public function scopeActivos($q){ return $q->where('estado', 'Activo'); }
    public function scopeRetirados($q){ return $q->where('estado', 'Retirado'); }
    
}
