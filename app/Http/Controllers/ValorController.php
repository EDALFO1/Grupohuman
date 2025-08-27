<?php

namespace App\Http\Controllers;

use App\Models\Valor;
use App\Models\EmpresaLocal;
use App\Http\Requests\ValorRequest;

class ValorController extends Controller
{
    public function index()
    {
        $empresaLocalId = session('empresa_local_id');

        $valores = Valor::with('empresaLocal')
            ->when($empresaLocalId, fn($q)=>$q->deEmpresa($empresaLocalId))
            ->orderByDesc('fecha_inicio')
            ->paginate(20);

        $titulo = 'Valores';
        return view('valores.index', compact('titulo','valores'));
    }

    public function create()
    {
        $titulo = 'Crear Valores';
        $valor = new Valor();
        $empresaActual = EmpresaLocal::find(session('empresa_local_id'));

        return view('valores.create', compact('titulo','valor','empresaActual'));
    }

    public function store(ValorRequest $request)
    {
        $data = $request->validated();
        // Asegura empresa por sesión
        $data['empresa_local_id'] = session('empresa_local_id');

        Valor::create($data);

        return to_route('valores.index')->with('success','Valores creados con éxito.');
    }

    public function edit(Valor $valor)
    {
        $titulo = 'Editar Valores';
        $empresaActual = $valor->empresaLocal;
        return view('valores.edit', compact('titulo','valor','empresaActual'));
    }

    public function update(ValorRequest $request, Valor $valor)
    {
        $data = $request->validated();
        // Mantén la empresa de la sesión o la actual
        $data['empresa_local_id'] = session('empresa_local_id') ?? $valor->empresa_local_id;

        $valor->update($data);

        return to_route('valores.index')->with('success','Valores actualizados con éxito.');
    }

    public function destroy(Valor $valor)
    {
        $valor->delete();
        return to_route('valores.index')->with('success','Valores eliminados.');
    }
}
