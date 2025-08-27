<?php

namespace App\Http\Controllers;

use App\Models\EmpresaLocal;
use App\Models\Documento;
use Illuminate\Http\Request;

class EmpresaLocalController extends Controller
{
    public function index()
    {
        $titulo = "Empresa_local";
        $empresas = EmpresaLocal::with('documento')->get();
        return view('empresa_local.index', compact('titulo','empresas'));
    }

    public function create()
    {
        $titulo = 'Crear Empresa';
        $documentos = Documento::all();
        return view('empresa_local.create', compact('titulo','documentos'));
    }

    public function store(Request $request)
    {
    $validated = $request->validate(EmpresaLocal::rules());

    EmpresaLocal::create($validated);

    return redirect()->route('empresa_local')->with('success', 'Empresa local creada correctamente.');
    
    }

    public function edit(EmpresaLocal $empresa)
    {
        $titulo = 'Editar Empresa';
        $documentos = Documento::all();
        return view('empresa_local.edit', compact('titulo','empresa', 'documentos'));
    }

    public function update(Request $request, EmpresaLocal $empresa)
    {
    $validated = $request->validate(EmpresaLocal::rules($empresa->id));

    $empresa->update($validated);

    return redirect()->route('empresa_local')->with('success', 'Empresa local actualizada correctamente.');
    }

    public function destroy(EmpresaLocal $empresa)
    {
        $empresa->delete();
        return redirect()->route('empresa_local')->with('success', 'Empresa eliminada correctamente.');
    }
    // app/Http/Controllers/EmpresaLocalController.php
    public function setActual(Request $request)
    {
    $request->validate([
        'empresa_local_id' => ['required','integer','exists:empresa_local,id'],
    ]);

    session(['empresa_local_id' => (int) $request->empresa_local_id]);

    return back()->with('success', 'Empresa actual cambiada.');
    }

}
