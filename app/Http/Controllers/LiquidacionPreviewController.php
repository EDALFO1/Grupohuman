<?php

namespace App\Http\Controllers;

use App\Models\UsuarioExterno;
use App\Services\LiquidacionService;
use Illuminate\Http\Request;

class LiquidacionPreviewController extends Controller
{
    /**
     * Preview de liquidación (NO guarda nada)
     * Usado por AJAX desde Recibos y Remisiones
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'usuario_externo_id' => ['required', 'exists:usuario_externos,id'],
            'fecha'              => ['required', 'date'],
            'novedad'            => ['nullable', 'in:Ingreso,Retiro'],
            'fecha_retiro'       => ['nullable', 'date'],
            'otros_servicios'    => ['nullable', 'numeric', 'min:0'],
        ]);

        $usuario = UsuarioExterno::with([
            'eps',
            'arl',
            'pension',
            'caja',
        ])->findOrFail($data['usuario_externo_id']);

        $dias = 0;

        // 👉 CÁLCULO REAL (fuente de verdad)
        $valores = LiquidacionService::calcular(
            $usuario,
            $data['fecha'],
            $data['novedad'] ?? 'Ingreso',
            $data['fecha_retiro'] ?? null,
            $dias
        );

        // Otros servicios (redondeo a centenas)
        $otros = (int) round(((float) ($data['otros_servicios'] ?? 0)) / 100) * 100;

        $total = array_sum($valores) + $otros;

        return response()->json([
            'dias'    => $dias,
            'valores' => $valores,
            'otros'   => $otros,
            'total'   => $total,
        ]);
    }
}
