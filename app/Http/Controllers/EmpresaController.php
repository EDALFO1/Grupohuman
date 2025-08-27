<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmpresaLocal;

class EmpresaController extends Controller
{
    public function seleccionar()
    {
        $empresas = EmpresaLocal::all();
        return view('empresa.seleccionar', compact('empresas'));
    }

    public function guardarSeleccion(Request $request)
    {
        $request->validate([
            'empresa_local_id' => 'required|exists:empresa_local,id',
        ]);

        session(['empresa_local_id' => $request->empresa_local_id]);

        return redirect()->route('home')->with('success', 'Empresa seleccionada correctamente');
    }
    
}
