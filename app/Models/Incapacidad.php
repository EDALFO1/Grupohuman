<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incapacidad extends Model
{
    protected $table = 'incapacidades'; // evitar pluralización "incapacidads"

    protected $fillable = [
        'usuario_externo_id',
        'documento',
        'nombre',
        'empresa_externa_id',
        'empresa_local_id',
        'entidad_tipo',
        'eps_id',
        'arl_id',
        'entidad_nombre',
        'fecha_inicio',
        'fecha_fin',
        'dias_solicitados',
        'fecha_radicacion',
        'estado',
        'cerrada',
        'fecha_cierre',
        'observaciones_libres',
        'fecha_pago',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_radicacion' => 'date',
        'fecha_pago' => 'date',
        'fecha_cierre' => 'date',
        'cerrada' => 'boolean',
    ];

    // Relaciones
    public function usuarioExterno(): BelongsTo { return $this->belongsTo(UsuarioExterno::class); }
    public function empresaExterna(): BelongsTo { return $this->belongsTo(EmpresaExterna::class, 'empresa_externa_id'); }
    public function empresaLocal(): BelongsTo { return $this->belongsTo(EmpresaLocal::class, 'empresa_local_id'); }
    public function eps(): BelongsTo { return $this->belongsTo(Eps::class, 'eps_id'); }
    public function arl(): BelongsTo { return $this->belongsTo(Arl::class, 'arl_id'); }
    public function observaciones(): HasMany { return $this->hasMany(IncapacidadObservacion::class); }

    // Helper para calcular días (inclusive)
    public static function calcularDiasSolicitados($fechaInicio, $fechaFin): int
    {
        $ini = \Carbon\Carbon::parse($fechaInicio)->startOfDay();
        $fin = \Carbon\Carbon::parse($fechaFin)->startOfDay();
        if ($fin->lt($ini)) return 0;
        return $ini->diffInDays($fin) + 1; // inclusivo
    }
}
