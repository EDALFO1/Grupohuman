<?php

namespace App\Services;

use App\Models\UsuarioExterno;
use Carbon\Carbon;

class LiquidacionService
{
    /**
     * Regla de negocio para días base 30 (mes anterior a la remisión).
     * - Si la afiliación es posterior al mes base → 0 días.
     * - Sin retiro: conteo inclusivo (30 - startDay + 1).
     * - Con retiro: inclusivo en ambos extremos (endDay - startDay + 1).
     * - Normaliza siempre entre 0 y 30.
     */
    protected static function diasMesBase30(
        Carbon $afiliacion,
        Carbon $inicioMes,
        Carbon $finMes,
        ?Carbon $retiro = null
    ): int {
        // Si la afiliación es posterior al mes base, no hay días que liquidar
        if ($afiliacion->gt($finMes)) {
            return 0;
        }

        // Día de inicio (base 30)
        $startDay = ($afiliacion->lte($inicioMes))
            ? 1
            : min($afiliacion->day, 30);

        // Día de fin (base 30)
        $endDay = 30;
        if ($retiro && $retiro->between($inicioMes, $finMes)) {
            $endDay = min($retiro->day, 30);
        }

        // Mes completo
        if ($startDay === 1 && $endDay === 30) {
            return 30;
        }

        // Sin retiro → inclusivo (cuenta el día de ingreso)
        if (!$retiro) {
            $dias = 30 - $startDay + 1;
            return max(0, min(30, $dias));
        }

        // Con retiro → inclusivo en ambos extremos
        $dias = $endDay - $startDay + 1;
        return max(0, min(30, $dias));
    }

    /**
     * Calcula EPS, ARL, pensión, caja y otros y devuelve también los días.
     *
     * @param UsuarioExterno $usuario
     * @param string $fechaRemision   Fecha de la remisión (Y-m-d)
     * @param string $novedad         'Ingreso' | 'Retiro'
     * @param string|null $fechaRetiro Fecha de retiro (Y-m-d) si aplica
     * @param int $dias               (salida) días calculados
     */
    public static function calcular(
        UsuarioExterno $usuario,
        string $fechaRemision,
        string $novedad,
        ?string $fechaRetiro,
        int &$dias
    ): array {
        // Mes base = MES ANTERIOR a la fecha de remisión
        $base      = Carbon::parse($fechaRemision)->subMonthNoOverflow();
        $inicioMes = $base->copy()->startOfMonth();
        $finMes    = $base->copy()->endOfMonth();

        // Normalizaciones
        $af = $usuario->fecha_afiliacion instanceof Carbon
            ? $usuario->fecha_afiliacion
            : Carbon::parse($usuario->fecha_afiliacion);

        // Solo considerar retiro si la novedad es 'Retiro'
        $ret = ($novedad === 'Retiro' && $fechaRetiro)
            ? Carbon::parse($fechaRetiro)
            : null;

        // Días a liquidar según la regla
        $dias = self::diasMesBase30($af, $inicioMes, $finMes, $ret);
        if ($dias > 30) {
            $dias = 30;
        }

        // Factor proporcional
        $sueldo = (float) $usuario->sueldo;
        $factor = $dias / 30;

        // Cálculos proporcionales
        $valor_eps     = round(($sueldo * ((float) ($usuario->eps->porcentaje ?? 0) / 100) * $factor) / 100) * 100;
        $valor_arl     = round(($sueldo * ((float) ($usuario->arl->porcentaje ?? 0) / 100) * $factor) / 100) * 100;
        $valor_pension = round(($sueldo * ((float) ($usuario->pension->porcentaje ?? 0) / 100) * $factor) / 100) * 100;

        // Caja (tu regla actual)
        $valor_caja = (strtolower((string) ($usuario->caja->nombre ?? '')) === 'comfandi')
            ? round(($sueldo * ((float) ($usuario->caja->porcentaje ?? 0) / 100) * $factor) / 100) * 100
            : 100;

        // Otros valores fijos
        $valor_admon    = round(((float) ($usuario->admon ?? 0)) / 100) * 100;
        $valor_exequial = round(((float) ($usuario->seg_exequial ?? 0)) / 100) * 100;
        $valor_mora     = round(((float) ($usuario->mora ?? 0)) / 100) * 100;

        return [
            'valor_eps'      => (int) $valor_eps,
            'valor_arl'      => (int) $valor_arl,
            'valor_pension'  => (int) $valor_pension,
            'valor_caja'     => (int) $valor_caja,
            'valor_admon'    => (int) $valor_admon,
            'valor_exequial' => (int) $valor_exequial,
            'valor_mora'     => (int) $valor_mora,
        ];
    }
}
