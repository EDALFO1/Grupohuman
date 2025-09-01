<?php

namespace App\Exports;

use App\Models\PeriodoUsuario;
use App\Models\UsuarioExterno;
use App\Models\Recibo;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsuariosVigentesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    public function __construct(
        protected int $empresaLocalId,
        protected string $periodoYm // 'YYYY-MM' del período siguiente (ej. '2025-09')
    ) {}

    public function headings(): array
    {
        return [
            'empresa_local',
            'numero',
            'nombre_completo',
            'telefono',
            'total_a_pagar',
            'subtipo_cotizante',
            'eps',
            'nivel_arl',
            'pension',
            'caja',
            'fecha_ingreso',
            'empresa_externa',
        ];
    }

    public function collection(): Collection
    {
        // Período objetivo (P) y mes base = P - 1
        $anchor   = Carbon::createFromFormat('Y-m-d', "{$this->periodoYm}-01");
        $base     = $anchor->copy()->subMonthNoOverflow();
        $baseYm   = $base->format('Y-m');
        $baseIni  = $base->copy()->startOfMonth()->toDateString();
        $baseFin  = $base->copy()->endOfMonth()->toDateString();

        // 0) Asegurar/Reconstruir marcas para ESTE período desde recibos + afiliaciones del MES BASE
        $this->ensurePeriodoUsuarios($this->empresaLocalId, $this->periodoYm, $baseYm, $baseIni, $baseFin);

        // IDs con RECIBO de RETIRO en el mes base (para excluir por si acaso)
        $idsRetiroBase = Recibo::query()
            ->where('empresa_local_id', $this->empresaLocalId)
            ->whereRaw("DATE_FORMAT(fecha, '%Y-%m') = ?", [$baseYm])
            ->whereRaw("UPPER(TRIM(COALESCE(novedad,''))) = 'RETIRO'")
            ->pluck('usuario_externo_id')
            ->unique()
            ->values();

        // 1) Exportar SOLO los marcados como ACTIVO para el período
        //    y que NO estén en la lista de retiros del mes base.
        $puActivos = PeriodoUsuario::query()
            ->where('empresa_local_id', $this->empresaLocalId)
            ->where('periodo', $this->periodoYm)
            ->where('estado', 'Activo')
            ->when($idsRetiroBase->isNotEmpty(), function ($q) use ($idsRetiroBase) {
                $q->whereNotIn('usuario_externo_id', $idsRetiroBase);
            })
            ->with([
                'usuarioExterno' => function ($q) {
                    $q->select([
                        'id','empresa_local_id','empresa_externa_id',
                        'numero','telefono',
                        'primer_apellido','segundo_apellido',
                        'primer_nombre','segundo_nombre',
                        'fecha_afiliacion',
                        'eps_id','arl_id','pension_id','caja_id','subtipo_cotizantes_id',
                        'sueldo','admon','seg_exequial','mora','otros_servicios',
                    ])->with([
                        'empresaLocal:id,nombre',
                        'empresaExterna:id,nombre',
                        'eps:id,nombre,porcentaje',
                        'arl:id,nombre,nivel,porcentaje',
                        'pension:id,nombre,porcentaje',
                        'caja:id,nombre,porcentaje',
                        'subtipoCotizante:id,nombre',
                    ]);
                },
            ])
            ->orderBy('id','desc')
            ->get();

        return $puActivos;
    }

    public function map($pu): array
    {
        $u = $pu->usuarioExterno;

        $empresaLocal   = $u?->empresaLocal?->nombre ?? '';
        $numero         = (string)($u?->numero ?? '');
        $nombreCompleto = method_exists($u, 'getFullNameAttribute') ? ($u->full_name ?? '') : trim(sprintf(
            '%s %s %s %s',
            $u?->primer_nombre, $u?->segundo_nombre, $u?->primer_apellido, $u?->segundo_apellido
        ));
        $telefono       = (string)($u?->telefono ?? '');

        // === Cálculo del TOTAL para el PERÍODO (YYYY-MM); el service usa (remisión - 1 mes) como base ===
        $fechaRemision = Carbon::createFromFormat('Y-m-d', "{$this->periodoYm}-01")->toDateString();
        $dias = 0;
        $valores = LiquidacionService::calcular(
            $u,
            $fechaRemision,
            'Ingreso',     // Tratamos a los “activos” como ingreso (sin retiro)
            null,
            $dias
        );

        $totalPagar =
            ($valores['valor_eps'] ?? 0) +
            ($valores['valor_arl'] ?? 0) +
            ($valores['valor_pension'] ?? 0) +
            ($valores['valor_caja'] ?? 0) +
            ($valores['valor_admon'] ?? 0) +
            ($valores['valor_exequial'] ?? 0) +
            ($valores['valor_mora'] ?? 0);

        // “otros_servicios” fijo desde BD (si aplica)
        $otros = (int) round(((float)($u?->otros_servicios ?? 0)) / 100) * 100;
        $totalPagar += $otros;

        $subtipo        = $u?->subtipoCotizante?->nombre ?? '';
        $eps            = $u?->eps?->nombre ?? '';
        $pension        = $u?->pension?->nombre ?? '';
        $caja           = $u?->caja?->nombre ?? '';

        $nivelArl = '';
        $rawNivel = $u?->arl?->nivel ?? null;
        if ($rawNivel !== null && preg_match('/\d+/', (string)$rawNivel, $m)) {
            $n = (int)$m[0];
            if ($n >= 1 && $n <= 5) $nivelArl = (string)$n;
        }

        $fechaIngreso   = optional($u?->fecha_afiliacion)->format('Y-m-d') ?? '';
        $empresaExterna = $u?->empresaExterna?->nombre ?? '';

        return [
            $empresaLocal,
            $numero,
            trim($nombreCompleto),
            $telefono,
            (int)$totalPagar,
            $subtipo,
            $eps,
            $nivelArl,
            $pension,
            $caja,
            $fechaIngreso,
            $empresaExterna,
        ];
    }

    /**
     * Reconstruye la tabla periodo_usuarios PARA EL PERÍODO dado desde:
     *   1) Recibos del mes base (período - 1): Retiro => Retirado, de lo contrario Activo
     *      (comparación case-insensitive / con trim)
     *   2) Afiliaciones del mes base: crea Activo si no existe marca aún
     */
    private function ensurePeriodoUsuarios(
        int $empresaId,
        string $periodoYm,
        string $baseYm,
        string $baseIni,
        string $baseFin
    ): void {
        DB::transaction(function () use ($empresaId, $periodoYm, $baseYm, $baseIni, $baseFin) {

            // 1) Marcas desde RECIBOS del MES BASE → período (siguiente)
            Recibo::query()
                ->where('empresa_local_id', $empresaId)
                ->whereRaw("DATE_FORMAT(fecha, '%Y-%m') = ?", [$baseYm])
                ->orderBy('id')
                ->chunkById(500, function ($recibos) use ($empresaId, $periodoYm) {
                    foreach ($recibos as $r) {
                        $nov = strtoupper(trim((string)($r->novedad ?? '')));
                        $estado = ($nov === 'RETIRO') ? 'Retirado' : 'Activo';

                        PeriodoUsuario::updateOrCreate(
                            [
                                'empresa_local_id'   => (int) $empresaId,
                                'usuario_externo_id' => (int) $r->usuario_externo_id,
                                'periodo'            => $periodoYm, // ← siguiente período
                            ],
                            [
                                'estado'    => $estado,
                                'recibo_id' => (int) $r->id,
                            ]
                        );
                    }
                });

            // 2) Marcas desde USUARIOS (afiliaciones del MES BASE) → período (siguiente) si no existe marca
            UsuarioExterno::query()
                ->where('empresa_local_id', $empresaId)
                ->whereBetween('fecha_afiliacion', [$baseIni, $baseFin])
                ->where(function ($q) use ($baseIni) {
                    $q->whereNull('fecha_retiro')
                      ->orWhereDate('fecha_retiro', '>=', $baseIni);
                })
                ->whereNotExists(function ($sub) use ($empresaId, $periodoYm) {
                    $sub->selectRaw(1)
                        ->from('periodo_usuarios as pu')
                        ->whereColumn('pu.usuario_externo_id', 'usuario_externos.id')
                        ->where('pu.empresa_local_id', $empresaId)
                        ->where('pu.periodo', $periodoYm);
                })
                ->orderBy('id')
                ->chunkById(500, function ($usuarios) use ($empresaId, $periodoYm) {
                    foreach ($usuarios as $u) {
                        PeriodoUsuario::updateOrCreate(
                            [
                                'empresa_local_id'   => (int) $empresaId,
                                'usuario_externo_id' => (int) $u->id,
                                'periodo'            => $periodoYm,
                            ],
                            [
                                'estado'    => 'Activo',
                                'recibo_id' => null,
                            ]
                        );
                    }
                });
        });
    }
}
