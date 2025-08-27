<?php

namespace App\Http\Controllers;

use App\Models\EmpresaExterna;
use App\Models\Documento;
use Illuminate\Http\Request;

class EmpresaExternaController extends Controller
{
    public function index()
    {
        $titulo = "Empresa Externa";
        $empresa_externas = EmpresaExterna::with('documento')->get();
        return view('empresa_externas.index', compact('titulo', 'empresa_externas'));
    }

    public function create()
    {
        $titulo = 'Crear Empresa Externa';
        $documentos = Documento::all();
        return view('empresa_externas.create', compact('titulo', 'documentos'));
    }

    public function store(Request $request)
    {
        $request->validate(EmpresaExterna::rules());

        EmpresaExterna::create($request->all());

        return to_route('empresa_externas')->with('success', 'Empresa externa creada correctamente.');
    }

    public function show(EmpresaExterna $empresa_externa)
    {
        return view('empresa_externas.show', compact('empresa_externa'));
    }

    public function edit(EmpresaExterna $empresa_externa)
    {
        $titulo = 'Editar Empresa Externa';
        $documentos = Documento::all();
        return view('empresa_externas.edit', compact('titulo', 'empresa_externa', 'documentos'));
    }

    public function update(Request $request, EmpresaExterna $empresa_externa)
    {
        $request->validate(EmpresaExterna::rules($empresa_externa->id));

        $empresa_externa->update($request->all());

        return to_route('empresa_externas')->with('success', 'Empresa externa actualizada correctamente.');
    }

    public function destroy(EmpresaExterna $empresa_externa)
    {
        $empresa_externa->delete();

        return to_route('empresa_externas')->with('success', 'Empresa externa eliminada correctamente.');
    }
}
