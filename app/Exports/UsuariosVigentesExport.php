<?php

namespace App\Exports;

use App\Models\PeriodoUsuario;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsuariosVigentesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    public function __construct(
        protected int $empresaLocalId,
        protected string $periodoYm // 'YYYY-MM'
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
        return PeriodoUsuario::query()
            ->where('empresa_local_id', $this->empresaLocalId)
            ->where('periodo', $this->periodoYm)
            ->where('estado', 'Activo')
            ->with([
                // OJO: no necesitamos recibo aquí
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

        // === Cálculo de TOTAL para el período (YYYY-MM) usando mes base = período - 1 mes ===
        // Tomamos como "fecha de remisión" el día 1 del período; tu servicio usa (remisión - 1 mes).
        $fechaRemision = Carbon::createFromFormat('Y-m-d', "{$this->periodoYm}-01")->toDateString();
        $dias = 0;
        $valores = LiquidacionService::calcular(
            $u,
            $fechaRemision,
            'Ingreso',     // marca activa se trata como “Ingreso” (sin retiro)
            null,          // sin fecha de retiro
            $dias          // salida por referencia
        );

        $totalPagar =
            ($valores['valor_eps'] ?? 0) +
            ($valores['valor_arl'] ?? 0) +
            ($valores['valor_pension'] ?? 0) +
            ($valores['valor_caja'] ?? 0) +
            ($valores['valor_admon'] ?? 0) +
            ($valores['valor_exequial'] ?? 0) +
            ($valores['valor_mora'] ?? 0);

        // Si manejas “otros_servicios” como fijo desde BD, puedes sumarlo aquí:
        $otros = (int) round(((float)($u?->otros_servicios ?? 0)) / 100) * 100;
        $totalPagar += $otros;

        $subtipo        = $u?->subtipoCotizante?->nombre ?? '';
        $eps            = $u?->eps?->nombre ?? '';
        $pension        = $u?->pension?->nombre ?? '';
        $caja           = $u?->caja?->nombre ?? '';

        $nivelArl = '';
        $rawNivel = $u?->arl?->nivel ?? $u?->arl?->nivel ?? null;
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
            (int)$totalPagar,   // entero
            $subtipo,
            $eps,
            $nivelArl,
            $pension,
            $caja,
            $fechaIngreso,
            $empresaExterna,
        ];
    }
}
