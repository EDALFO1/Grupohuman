<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsuariosVigentesExport;

class UsuariosVigentesExcelController extends Controller
{
    public function descargar(Request $request)
    {
        $request->validate([
            'empresa_local_id' => ['nullable','integer','exists:empresa_local,id'],
            'periodo'          => ['nullable','regex:/^\d{4}-\d{2}$/'], // YYYY-MM
        ]);

        $empresaId = (int)($request->input('empresa_local_id') ?: session('empresa_local_id'));
        abort_if(!$empresaId, 422, 'No hay empresa seleccionada.');

        $periodo = $request->input('periodo') ?: now()->format('Y-m');

        $filename = sprintf('UsuariosVigentes_%d_%s.xlsx', $empresaId, $periodo);

        return Excel::download(new UsuariosVigentesExport($empresaId, $periodo), $filename);
    }
}
