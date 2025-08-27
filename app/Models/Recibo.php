<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Recibo extends Model
{
    protected $table = 'recibos';

    protected $fillable = [
        'empresa_local_id','numero','fecha','usuario_externo_id','dias_liquidar',
        'valor_eps','valor_arl','valor_pension','valor_caja',
        'valor_admon','valor_exequial','valor_mora','otros_servicios',
        'total','novedad','fecha_retiro',
        // snapshots de nombres
        'eps_nombre','arl_nombre','pension_nombre','caja_nombre',
        // snapshot ARL
        'arl_nivel','arl_nivel_riesgo','arl_actividad','arl_tarifa',
        // bases del período
        'sueldo_base','admon_base','override_parametros',
        // batch
        'export_batch_id',
    ];

    protected $casts = [
        'fecha'               => 'date',
        'fecha_retiro'        => 'date',
        'arl_tarifa'          => 'float',
        'sueldo_base'         => 'decimal:2',
        'admon_base'          => 'decimal:2',
        'override_parametros' => 'boolean',
        'total'               => 'decimal:2',
    ];

    public function usuarioExterno() { return $this->belongsTo(UsuarioExterno::class); }
    public function empresaLocal()   { return $this->belongsTo(EmpresaLocal::class, 'empresa_local_id'); }
    public function exportBatch()    { return $this->belongsTo(\App\Models\ExportBatch::class); }

    public function scopeDeEmpresa($q, $empresaId) { return $q->where('empresa_local_id', $empresaId); }
    public function scopePendientes($q)
    {
        $t = $q->getModel()->getTable();
        return Schema::hasColumn($t, 'export_batch_id') ? $q->whereNull("$t.export_batch_id") : $q;
    }

    public function getPeriodoAttribute(): ?string { return $this->fecha ? $this->fecha->format('Y-m') : null; }
}
