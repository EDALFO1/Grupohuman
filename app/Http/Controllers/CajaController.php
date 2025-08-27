<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function index()
    {
        $titulo = "Cajas";
        $cajas = Caja::all();
        return view('cajas.index', compact('titulo','cajas'));
    }

    public function create()
    {
        $titulo = 'Crear Caja';
        return view('cajas.create', compact('titulo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(Caja::rules());
        Caja::create($validated);
        return redirect()->route('cajas')->with('success', 'Caja creada correctamente.');
    }

    public function edit(Caja $caja)
    {
        $titulo = 'Editar Caja';
        return view('cajas.edit', compact('titulo', 'caja'));
    }

    public function update(Request $request, Caja $caja)
    {
        $validated = $request->validate(Caja::rules($caja->id));
        $caja->update($validated);
        return redirect()->route('cajas')->with('success', 'Caja actualizada correctamente.');
    }

    public function destroy(Caja $caja)
    {
        $caja->delete();
        return redirect()->route('cajas')->with('success', 'Caja eliminada correctamente.');
    }
}
