<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\ValoresService;

class UsuarioExterno extends Model
{
    use HasFactory;

    protected $table = 'usuario_externos';

    protected $fillable = [
        'documento_id','asesor_id','numero','fecha_expedicion',
        'primer_apellido','segundo_apellido','primer_nombre','segundo_nombre',
        'fecha_nacimiento','correo_electronico','direccion','telefono',
        'fecha_afiliacion','sexo','eps_id','arl_id','pension_id','caja_id',
        'subtipo_cotizantes_id','empresa_local_id','empresa_externa_id',
        'sueldo','admon','seg_exequial','mora','otros_servicios',
        'override_parametros','cargo','estado','novedad','fecha_retiro',
    ];

    protected $casts = [
        'estado'           => 'boolean',
        'fecha_expedicion' => 'date',
        'fecha_nacimiento' => 'date',
        'fecha_afiliacion' => 'date',
        'fecha_retiro'     => 'date',
        // Nota: decimal:2 devuelve string (preserva precisión)
        'sueldo'           => 'decimal:2',
        'admon'            => 'decimal:2',
        'seg_exequial'     => 'decimal:2',
        'mora'             => 'decimal:2',
        'otros_servicios'  => 'decimal:2',
    ];

    protected $appends = ['sueldo_vigente','admon_vigente','full_name'];

    // ----- Accessors / Appends -----
    public function getFullNameAttribute(): string
    {
        return trim(sprintf(
            '%s %s %s %s',
            $this->primer_nombre,
            $this->segundo_nombre,
            $this->primer_apellido,
            $this->segundo_apellido
        ));
    }

    public function getSueldoVigenteAttribute()
    {
        if ($this->override_parametros) return $this->sueldo;

        $empresaId = $this->empresa_local_id ?: session('empresa_local_id');
        if (!$empresaId) return $this->sueldo;

        $v = app(ValoresService::class)->vigentePara($empresaId);
        return $v->salario ?? $this->sueldo;
    }

    public function getAdmonVigenteAttribute()
    {
        if ($this->override_parametros) return $this->admon;

        $empresaId = $this->empresa_local_id ?: session('empresa_local_id');
        if (!$empresaId) return $this->admon;

        $v = app(ValoresService::class)->vigentePara($empresaId);
        return $v->administracion ?? $this->admon;
    }

    // Helpers por fecha (p.ej. fecha de remisión)
    public function sueldoEfectivoParaFecha(string $fecha): float
    {
        if ($this->override_parametros) return (float)$this->sueldo;
        $empresaId = $this->empresa_local_id ?: session('empresa_local_id');
        $v = app(ValoresService::class)->vigentePara($empresaId, $fecha);
        return (float)($v->salario ?? $this->sueldo);
    }

    public function admonEfectivoParaFecha(string $fecha): float
    {
        if ($this->override_parametros) return (float)$this->admon;
        $empresaId = $this->empresa_local_id ?: session('empresa_local_id');
        $v = app(ValoresService::class)->vigentePara($empresaId, $fecha);
        return (float)($v->administracion ?? $this->admon);
    }

    // ----- Scopes -----
    public function scopeDeEmpresa($q, int $empresaLocalId)
    {
        return $q->where('empresa_local_id', $empresaLocalId);
    }

    public function scopeActivos($q)
    {
        return $q->where('estado', true);
    }

    public function scopeBuscar($q, ?string $term)
    {
        if (!$term) return $q;
        $t = trim($term);
        return $q->where(function ($qq) use ($t) {
            $qq->where('numero', 'like', "%{$t}%")
               ->orWhere('primer_nombre', 'like', "%{$t}%")
               ->orWhere('segundo_nombre', 'like', "%{$t}%")
               ->orWhere('primer_apellido', 'like', "%{$t}%")
               ->orWhere('segundo_apellido', 'like', "%{$t}%");
        });
    }

    // ----- Relaciones -----
    public function documento()      { return $this->belongsTo(Documento::class); }
    public function asesor()         { return $this->belongsTo(Asesor::class); }
    public function eps()            { return $this->belongsTo(Eps::class); }
    public function arl()            { return $this->belongsTo(Arl::class); }
    public function pension()        { return $this->belongsTo(Pension::class); }
    public function caja()           { return $this->belongsTo(Caja::class); }
    public function subtipoCotizante(){ return $this->belongsTo(SubtipoCotizante::class, 'subtipo_cotizantes_id'); }
    public function empresaLocal()   { return $this->belongsTo(EmpresaLocal::class, 'empresa_local_id'); }
    public function empresaExterna() { return $this->belongsTo(EmpresaExterna::class, 'empresa_externa_id'); }
    public function remisiones()     { return $this->hasMany(Remision::class, 'usuario_externo_id'); }
}
