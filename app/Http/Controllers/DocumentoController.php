<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Http\Request;

class DocumentoController extends Controller
{
    public function index()
    {
        $titulo = "documentos";
        $documentos = Documento::all();
        return view('documentos.index', compact('titulo','documentos'));
    }

    public function create()
    {
        $titulo = 'Crear Documento';
        return view('documentos.create', compact('titulo'));
    }

    public function store(Request $request)
    {
        $request->validate(Documento::rules());

        Documento::create($request->all());

        /*return redirect()->route('documentos.index')->with('success', 'Documento creado exitosamente.');*/
        return to_route('documentos')->with('success', 'Documento creado exitosamente.');
    }

    public function edit(Documento $documento)
    {
        $titulo = 'Editar Documento';
        return view('documentos.edit', compact('titulo','documento'));
    }

    public function update(Request $request, Documento $documento)
    {
        $request->validate(Documento::rules($documento->id));

        $documento->update($request->all());

        /*return redirect()->route('documentos.index')->with('success', 'Documento actualizado.');*/
        return to_route('documentos')->with('success', 'Documento actualizado.');
    }

    public function destroy(Documento $documento)
    {
        $documento->delete();

        /*return redirect()->route('documentos.index')->with('success', 'Documento eliminado.');*/
        return to_route('documentos')->with('success', 'Documento eliminado.');
    }
}
