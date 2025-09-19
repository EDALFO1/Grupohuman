<?php

namespace App\Http\Controllers;

use Illuminate\Support\Arr;

class PlanesController extends Controller
{
    public function index()
    {
        $smmlv  = (int) config('planes.smmlv');
        $admin  = (int) config('planes.admin');
        $p      = config('planes.porcentajes');

        // helper: redondeo hacia arriba al siguiente múltiplo de 100
        $ceil100 = fn (float $v) => (int) (ceil($v / 100) * 100);

        // componentes base (cada uno se redondea por separado)
        $eps     = $ceil100($smmlv * Arr::get($p, 'eps'));
        $caja    = $ceil100($smmlv * Arr::get($p, 'caja'));
        $pension = $ceil100($smmlv * Arr::get($p, 'pension'));

        $arl = [];
        foreach (Arr::get($p, 'arl', []) as $nivel => $porc) {
            $arl[$nivel] = $ceil100($smmlv * $porc);
        }

        // Tablas de planes
        $planes = [
            'EPS + ARL' => collect($arl)->map(fn($v, $nivel) => [
                'label' => "EPS + ARL{$nivel}",
                'valor' => $eps + $v + $admin,
                'breakdown' => ['eps' => $eps, "arl{$nivel}" => $v, 'admin' => $admin],
            ])->values(),

            'EPS + ARL + CAJA' => collect($arl)->map(fn($v, $nivel) => [
                'label' => "EPS + ARL{$nivel} + CAJA",
                'valor' => $eps + $v + $caja + $admin,
                'breakdown' => ['eps' => $eps, "arl{$nivel}" => $v, 'caja' => $caja, 'admin' => $admin],
            ])->values(),

            'EPS + ARL + PENSIÓN' => collect($arl)->map(fn($v, $nivel) => [
                'label' => "EPS + ARL{$nivel} + PENSIÓN",
                'valor' => $eps + $v + $pension + $admin,
                'breakdown' => ['eps' => $eps, "arl{$nivel}" => $v, 'pension' => $pension, 'admin' => $admin],
            ])->values(),

            'EPS + ARL + CAJA + PENSIÓN' => collect($arl)->map(fn($v, $nivel) => [
                'label' => "EPS + ARL{$nivel} + CAJA + PENSIÓN",
                'valor' => $eps + $v + $caja + $pension + $admin,
                'breakdown' => ['eps' => $eps, "arl{$nivel}" => $v, 'caja' => $caja, 'pension' => $pension, 'admin' => $admin],
            ])->values(),
        ];

        $componentes = compact('eps','caja','pension','arl','admin','smmlv');

        return view('planes.index', compact('planes','componentes'));
    }
}
