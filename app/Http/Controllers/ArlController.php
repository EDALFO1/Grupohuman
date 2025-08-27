<?php

namespace App\Http\Controllers;

use App\Models\Arl;
use Illuminate\Http\Request;

class ArlController extends Controller
{
    public function index()
    {
        $titulo = "ARL";
        $arls = Arl::orderBy('nivel')->paginate(20); // <- paginado
        return view('arls.index', compact('titulo', 'arls'));
    }

    public function create()
    {
        $titulo = 'Crear ARL';
        $arl = new Arl();
        return view('arls.create', compact('titulo', 'arl'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(Arl::rules());
        Arl::create($data);
        return to_route('arls')->with('success', 'ARL creada con éxito.');
    }

    public function edit(Arl $arl)
    {
        $titulo = 'Editar ARL';
        return view('arls.edit', compact('titulo', 'arl'));
    }

    public function update(Request $request, Arl $arl)
    {
        $data = $request->validate(Arl::rules($arl->id));
        $arl->update($data);
        return to_route('arls')->with('success', 'ARL actualizada con éxito.');
    }

    public function destroy(Arl $arl)
    {
    try {
        $arl->delete();

        return to_route('arls')
            ->with('success', 'ARL eliminada correctamente.');
    } catch (\Throwable $e) {
        return back()
            ->with('error', 'No se puede eliminar: existen usuarios vinculados a esta ARL.');
    }
    }

}
