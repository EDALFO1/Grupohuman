<?php

namespace App\Services;

use App\Models\UsuarioExterno;
use App\Services\ValoresService;
use Carbon\Carbon;

class LiquidacionService
{
    /* ============================================================
     |  DÍAS BASE 30 (MES ANTERIOR A LA FECHA DEL RECIBO/REMISIÓN)
     |============================================================ */
    protected static function diasMesBase30(
        Carbon $afiliacion,
        Carbon $inicioMes,
        Carbon $finMes,
        ?Carbon $retiro = null
    ): int {
        // Afiliación posterior al mes base → 0 días
        if ($afiliacion->gt($finMes)) {
            return 0;
        }

        // Día inicio
        $startDay = $afiliacion->lte($inicioMes)
            ? 1
            : min($afiliacion->day, 30);

        // Día fin
        $endDay = 30;
        if ($retiro && $retiro->between($inicioMes, $finMes)) {
            $endDay = min($retiro->day, 30);
        }

        // Mes completo
        if ($startDay === 1 && $endDay === 30) {
            return 30;
        }

        // Sin retiro → inclusivo
        if (!$retiro) {
            return max(0, min(30, 30 - $startDay + 1));
        }

        // Con retiro → inclusivo
        return max(0, min(30, $endDay - $startDay + 1));
    }

    /* ============================================================
     |  CÁLCULO PRINCIPAL
     |============================================================ */
    public static function calcular(
        UsuarioExterno $usuario,
        string $fechaRecibo,
        string $novedad,
        ?string $fechaRetiro,
        int &$dias
    ): array {

        /* ================== VALORES DEL PERÍODO ================== */
        $valores = app(ValoresService::class)
            ->vigentePara($usuario->empresa_local_id, $fechaRecibo);

        if (!$valores) {
            throw new \RuntimeException(
                'No existen valores vigentes (salario / administración) para la fecha seleccionada.'
            );
        }

        /* ================== BASES CORRECTAS ================== */
        $override = (bool) $usuario->override_parametros;

        $sueldoBase = $override
            ? (float) $usuario->sueldo
            : (float) $valores->salario;

        $admonBase = $override
            ? (float) $usuario->admon
            : (float) $valores->administracion;

        /* ================== MES BASE ================== */
        $base      = Carbon::parse($fechaRecibo)->subMonthNoOverflow();
        $inicioMes = $base->copy()->startOfMonth();
        $finMes    = $base->copy()->endOfMonth();

        $afiliacion = $usuario->fecha_afiliacion instanceof Carbon
            ? $usuario->fecha_afiliacion
            : Carbon::parse($usuario->fecha_afiliacion);

        $retiro = ($novedad === 'Retiro' && $fechaRetiro)
            ? Carbon::parse($fechaRetiro)
            : null;

        /* ================== DÍAS ================== */
        $dias = self::diasMesBase30(
            $afiliacion,
            $inicioMes,
            $finMes,
            $retiro
        );

        $factor = $dias / 30;

        /* ================== PORCENTAJES ================== */
        $porcEps     = (float) ($usuario->eps->porcentaje     ?? 0);
        $porcArl     = (float) ($usuario->arl->porcentaje     ?? 0);
        $porcPension = (float) ($usuario->pension->porcentaje ?? 0);
        $porcCaja    = (float) ($usuario->caja->porcentaje    ?? 0);

        /* ================== CÁLCULOS (REDONDEO A 100) ================== */
        $round100 = fn ($v) => (int) (round($v / 100) * 100);

        $valor_eps     = $round100($sueldoBase * ($porcEps / 100) * $factor);
        $valor_arl     = $round100($sueldoBase * ($porcArl / 100) * $factor);
        $valor_pension = $round100($sueldoBase * ($porcPension / 100) * $factor);

        /* ================== CAJA ================== */
        $valor_caja = 0;
        $nombreCaja = strtolower(trim((string) ($usuario->caja->nombre ?? '')));

        if ($nombreCaja === 'comfandi') {
            $valor_caja = $round100($sueldoBase * ($porcCaja / 100) * $factor);
        } elseif ($usuario->caja) {
            $valor_caja = 100; // tu regla actual
        }

        /* ================== VALORES FIJOS ================== */
        $valor_admon    = $round100($admonBase);
        $valor_exequial = $round100((float) ($usuario->seg_exequial ?? 0));
        $valor_mora     = $round100((float) ($usuario->mora ?? 0));

        /* ================== RESULTADO ================== */
        return [
            'valor_eps'      => $valor_eps,
            'valor_arl'      => $valor_arl,
            'valor_pension'  => $valor_pension,
            'valor_caja'     => $valor_caja,
            'valor_admon'    => $valor_admon,
            'valor_exequial' => $valor_exequial,
            'valor_mora'     => $valor_mora,
        ];
    }
}
