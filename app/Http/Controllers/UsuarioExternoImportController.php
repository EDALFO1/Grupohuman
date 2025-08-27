<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\UsuarioExternosByNameImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsuarioExternosTemplateExport;



class UsuarioExternoImportController extends Controller
{
    public function index() { return $this->showForm(); }

    public function store(Request $request) { return $this->import($request); }

    public function showForm()
    {
        return view('usuario_externos.import');
    }

    public function import(Request $request)
   {
    $request->validate([
        'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
    ]);

    // ⚠️ Garantiza empresa en sesión (ya debería existir por el middleware)
    $empresaLocalId = session('empresa_local_id');
    if (!$empresaLocalId) {
        return back()->withErrors(['archivo' => 'No hay empresa activa en sesión.']);
    }

    // ✅ Pásale la empresa al importador para forzarla
    $import = new UsuarioExternosByNameImport($empresaLocalId);

    try {
        Excel::import($import, $request->file('archivo'));
    } catch (\Throwable $e) {
        return back()->withErrors(['archivo' => 'Error al procesar: ' . $e->getMessage()]);
    }

    if (!empty($import->failuresList)) {
        return back()
            ->with('partial_success', "Procesadas: {$import->processed}. Creadas: {$import->created}. Saltadas: {$import->skipped}.")
            ->withFailures($import->failuresList);
    }

    return redirect()
        ->route('usuario_externos')
        ->with('success', "¡Importación OK! Procesadas: {$import->processed}. Creadas: {$import->created}. Saltadas: {$import->skipped}.");
    }

    public function downloadTemplate()
    {
    // Si quieres proteger por auth, usa ->middleware('auth') en la ruta
    return Excel::download(new UsuarioExternosTemplateExport, 'usuario_externos_template.xlsx');
    }
}
