<?php

namespace App\Http\Controllers;

use App\Models\ServicioExterno;
use Illuminate\Http\Request;

class ServicioExternoController extends Controller
{
    /**
     * Mostrar listado de servicios externos.
     */
    public function index()
    {
        $servicios = ServicioExterno::orderBy('nombre')->paginate(15);
        $titulo = 'Servicios Externos';
        return view('servicios_externos.index', compact('servicios', 'titulo'));
    }

    /**
     * Mostrar formulario de creación.
     */
    public function create()
    {
        $titulo = 'Crear Servicio Externo';
        $servicio = new ServicioExterno();
        return view('servicios_externos.create', compact('titulo', 'servicio'));
    }

    /**
     * Guardar un nuevo servicio.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:servicios_externos,nombre',
            'tipo' => 'required|string|max:100',
            'url_login' => 'nullable|url|max:255',
        ]);

        ServicioExterno::create($request->all());

        return redirect()
            ->route('servicios-externos.index')
            ->with('success', '✅ Servicio creado correctamente.');
    }

    /**
     * Mostrar formulario de edición.
     */
    public function edit(ServicioExterno $servicioExterno)
    {
        $titulo = 'Editar Servicio Externo';
        return view('servicios_externos.edit', [
            'servicio' => $servicioExterno,
            'titulo' => $titulo,
        ]);
    }

    /**
     * Actualizar servicio.
     */
    public function update(Request $request, ServicioExterno $servicioExterno)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:servicios_externos,nombre,' . $servicioExterno->id,
            'tipo' => 'required|string|max:100',
            'url_login' => 'nullable|url|max:255',
        ]);

        $servicioExterno->update($request->all());

        return redirect()
            ->route('servicios-externos.index')
            ->with('success', '🔄 Servicio actualizado correctamente.');
    }

    /**
     * Eliminar servicio.
     */
    public function destroy(ServicioExterno $servicioExterno)
    {
        $servicioExterno->delete();

        return redirect()
            ->route('servicios-externos.index')
            ->with('success', '🗑️ Servicio eliminado correctamente.');
    }
}
