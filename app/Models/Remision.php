<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remision extends Model
{
    protected $table = 'remisiones';

    protected $fillable = [
        'empresa_local_id',           // 👈 nuevo: clave de la empresa
        'numero', 'fecha', 'usuario_externo_id', 'dias_liquidar',
        'valor_eps', 'valor_arl', 'valor_pension', 'valor_caja',
        'valor_admon', 'valor_exequial', 'valor_mora', 'otros_servicios',
        'total', 'novedad', 'fecha_retiro',
    ];

    protected $casts = [
        'fecha'        => 'date',
        'fecha_retiro' => 'date',
        'valor_eps'        => 'decimal:2',
        'valor_arl'        => 'decimal:2',
        'valor_pension'    => 'decimal:2',
        'valor_caja'       => 'decimal:2',
        'valor_admon'      => 'decimal:2',
        'valor_exequial'   => 'decimal:2',
        'valor_mora'       => 'decimal:2',
        'otros_servicios'  => 'decimal:2',
        'total'            => 'decimal:2',
    ];

    /** Usuario dueño de la remisión */
    public function usuarioExterno()
    {
        return $this->belongsTo(UsuarioExterno::class);
    }

    /** Empresa a la que pertenece la remisión */
    public function empresaLocal()
    {
        return $this->belongsTo(EmpresaLocal::class, 'empresa_local_id');
    }

    /** Scope para filtrar por empresa activa (usar con session('empresa_local_id')) */
    public function scopeDeEmpresa($query, $empresaId)
    {
        return $query->where('empresa_local_id', $empresaId);
    }
        public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('fecha', $year)
                     ->whereMonth('fecha', $month);
    }

    /**
     * Acepta 'YYYY-MM' o Date/Carbon. Uso: Remision::forPeriod('2025-09')->get();
     */
    public function scopeForPeriod($query, $period)
    {
        if ($period instanceof \DateTimeInterface) {
            $year = $period->format('Y');
            $month = $period->format('m');
        } elseif (is_string($period) && preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
            $year = $m[1];
            $month = $m[2];
        } else {
            $year = now()->format('Y');
            $month = now()->format('m');
        }

        return $query->whereYear('fecha', $year)
                     ->whereMonth('fecha', $month);
    }

    // Ejemplo de relaciones comunes (ajusta nombres si usas otros)
   
}
