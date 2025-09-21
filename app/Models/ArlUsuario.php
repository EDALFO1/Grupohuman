<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\ValoresService;

class ArlUsuario extends Model
{
    use HasFactory;

    protected $table = 'arl_usuarios';

    protected $fillable = [
        'empresa_local_id',
        'documento_id',
        'numero',
        'nombre',
        'fecha_ingreso',
        'arl_id',
        'empresa_externa_id',
        'base_cotizacion',
        'administracion',
        'estado',
        'fecha_retiro',
        'override_parametros',
    ];

    protected $casts = [
        'estado'          => 'boolean',
        'fecha_ingreso'   => 'date',
        'fecha_retiro'    => 'date',
        'base_cotizacion' => 'decimal:2',
        'administracion'  => 'decimal:2',
    ];

    protected $appends = ['valor']; // valor calculado

    // ----- Relaciones -----
    public function documento()      { return $this->belongsTo(Documento::class); }
    public function arl()            { return $this->belongsTo(Arl::class); }
    public function empresaLocal()   { return $this->belongsTo(EmpresaLocal::class, 'empresa_local_id'); }
    public function empresaExterna() { return $this->belongsTo(EmpresaExterna::class, 'empresa_externa_id'); }

    // ----- Scopes -----
    public function scopeDeEmpresa($q, int $empresaLocalId)
    {
        return $q->where('empresa_local_id', $empresaLocalId);
    }

    public function scopeBuscar($q, ?string $term)
    {
        if (!$term) return $q;
        $t = trim($term);
        return $q->where(function ($qq) use ($t) {
            $qq->where('numero', 'like', "%{$t}%")
               ->orWhere('nombre', 'like', "%{$t}%");
        });
    }

    // ----- Accessors -----
    public function getValorAttribute(): int
{
    $porc = (float)($this->arl->porcentaje ?? 0);
    $base = $this->getBaseEfectiva();
    $adm  = $this->getAdministracionEfectiva();

    $valor = ($base * ($porc / 100)) + $adm;          // bruto
    $redondeado = round($valor / 100) * 100;          // ← al centenar más cercano
    return (int) $redondeado;                          // sin decimales
}

    public function getBaseEfectiva(): float
    {
        if ($this->override_parametros) return (float)$this->base_cotizacion;

        $empresaId = $this->empresa_local_id ?: session('empresa_local_id');
        if (!$empresaId) return (float)$this->base_cotizacion;

        $v = app(ValoresService::class)->vigentePara($empresaId);
        // Si base_cotizacion no está seteada (>0), usa la del servicio
        $base = (float)($this->base_cotizacion > 0 ? $this->base_cotizacion : ($v->salario ?? 0));
        return $base;
    }

    public function getAdministracionEfectiva(): float
    {
        if ($this->override_parametros) return (float)$this->administracion;

        $empresaId = $this->empresa_local_id ?: session('empresa_local_id');
        if (!$empresaId) return (float)$this->administracion;

        $v = app(ValoresService::class)->vigentePara($empresaId);
        $adm = (float)($this->administracion > 0 ? $this->administracion : ($v->administracion ?? 0));
        return $adm;
    }
}
