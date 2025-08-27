<?php

namespace App\Services;

use App\Models\Valor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ValoresService
{
    public function vigentePara(int $empresaLocalId, ?string $fecha = null): ?Valor
    {
        $fecha = $fecha ? Carbon::parse($fecha)->toDateString() : now()->toDateString();

        $key = "valores:{$empresaLocalId}:{$fecha}";
        return Cache::remember($key, now()->addMinutes(10), function () use ($empresaLocalId, $fecha) {
            return Valor::where('empresa_local_id', $empresaLocalId)
                ->where('activa', true)
                ->whereDate('fecha_inicio', '<=', $fecha)
                ->where(function ($q) use ($fecha) {
                    $q->whereNull('fecha_fin')
                      ->orWhereDate('fecha_fin', '>=', $fecha);
                })
                ->orderByDesc('fecha_inicio')
                ->first();
        });
    }
}
