<?php

namespace App\Http\Controllers;

use App\Models\Pension;
use App\Models\UsuarioExterno;
use Illuminate\Http\Request;

class PensionController extends Controller
{
    public function index()
    {
        $titulo = "Pensiones";
        $pensions = Pension::all();
        return view('pensions.index', compact('titulo','pensions'));
    }

    public function create()
    {
        $titulo = 'Crear Pension';
        return view('pensions.create', compact('titulo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(Pension::rules());

        Pension::create($validated);

        return redirect()->route('pensions')->with('success', 'Pensión creada exitosamente.');
    }

    public function show(Pension $pension)
    {
        return view('pensions.show', compact('pension'));
    }

    public function edit(Pension $pension)
    {
         $titulo = 'Editar Pension';
        return view('pensions.edit', compact('titulo','pension'));
    }

    public function update(Request $request, Pension $pension)
    {
        $validated = $request->validate(Pension::rules($pension->id));

        $pension->update($validated);

        return redirect()->route('pensions')->with('success', 'Pensión actualizada exitosamente.');
    }

    public function destroy(Pension $pension)
    {
        $pension->delete();
        return redirect()->route('pensions')->with('success', 'Pensión eliminada exitosamente.');
    }
     
}
