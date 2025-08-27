<?php

namespace App\Http\Controllers;

use App\Models\Eps;
use Illuminate\Http\Request;

class EpsController extends Controller
{
    public function index()
    {
        $titulo = "Eps";
        $eps = Eps::all();
        return view('eps.index', compact('titulo','eps'));
    }

    public function create()
    {
         $titulo = 'Crear Eps';
        return view('eps.create', compact('titulo'));
    }

    public function store(Request $request)
    {
        $request->validate(Eps::rules());

        Eps::create($request->all());

        return to_route('eps')->with('success', 'EPS creada correctamente.');
    }

    public function show(Eps $eps)
    {
        return view('eps.show', compact('eps'));
    }

    public function edit(Eps $eps)
    {
        $titulo = 'Editar Eps';
        return view('eps.edit', compact('titulo','eps'));
    }

    public function update(Request $request, Eps $eps)
    {
        $request->validate(Eps::rules($eps->id));

        $eps->update($request->all());

        return to_route('eps')->with('success', 'EPS actualizada correctamente.');
    }

    public function destroy(Eps $eps)
    {
        $eps->delete();
        return to_route('eps')->with('success', 'EPS eliminada correctamente.');
    }
}

