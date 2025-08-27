<?php

namespace App\Http\Controllers;

use App\Models\SubtipoCotizante;
use Illuminate\Http\Request;

class SubtipoCotizanteController extends Controller
{
    public function index()
    {
        $subtipos = SubtipoCotizante::all();
        return view('subtipo_cotizantes.index', compact('subtipos'));
    }

    public function create()
    {
        return view('subtipo_cotizantes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|unique:subtipo_cotizantes,codigo',
            'nombre' => 'required'
        ]);

        SubtipoCotizante::create($request->all());
        return redirect()->route('subtipo_cotizantes')->with('success', 'Subtipo creado correctamente.');
    }

    public function edit(SubtipoCotizante $subtipo_cotizante)
    {
        return view('subtipo_cotizantes.edit', compact('subtipo_cotizante'));
    }

    public function update(Request $request, SubtipoCotizante $subtipo_cotizante)
    {
        $request->validate([
            'codigo' => 'required|unique:subtipo_cotizantes,codigo,' . $subtipo_cotizante->id,
            'nombre' => 'required'
        ]);

        $subtipo_cotizante->update($request->all());
        return redirect()->route('subtipo_cotizantes')->with('success', 'Subtipo actualizado correctamente.');
    }

    public function destroy(SubtipoCotizante $subtipo_cotizante)
    {
        $subtipo_cotizante->delete();
        return redirect()->route('subtipo_cotizantes')->with('success', 'Subtipo eliminado correctamente.');
    }
}
