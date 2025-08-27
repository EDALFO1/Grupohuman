<?php
namespace App\Http\Controllers;

use App\Models\Asesor;
use App\Models\Documento;
use Illuminate\Http\Request;

class AsesorController extends Controller
{
    public function index()
    {
        $asesores = Asesor::with('documento')->get();
        return view('asesores.index', compact('asesores'));
    }

    public function create()
    {
       
        $documentos = Documento::all();
        $asesor = new Asesor(); // esto es CRUCIAL para que $asesor exista
        return view('asesores.create', compact('documentos', 'asesor'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'documento_id' => 'required|exists:documentos,id',
            'numero_documento' => 'required|unique:asesores,numero_documento',
            'nombre' => 'required',
            'direccion' => 'required',
            'telefono' => 'required',
            'email' => 'nullable|email',
        ]);

        Asesor::create($request->all());

        return to_route('asesores')->with('success', 'Asesor creado exitosamente.');
    }

    public function edit(Asesor $asesor)
    {
        $documentos = Documento::all();
        return view('asesores.edit', compact('asesor', 'documentos'));
    }

    public function update(Request $request, Asesor $asesor)
    {
        $request->validate([
            'documento_id' => 'required|exists:documentos,id',
            'numero_documento' => 'required|unique:asesores,numero_documento,' . $asesor->id,
            'nombre' => 'required',
            'direccion' => 'required',
            'telefono' => 'required',
            'email' => 'nullable|email',
        ]);

        $asesor->update($request->all());

        return to_route('asesores')->with('success', 'Asesor actualizado correctamente.');
    }

    public function destroy(Asesor $asesor)
    {
        $asesor->delete();
        return to_route('asesores')->with('success', 'Asesor eliminado correctamente.');
    }
}
