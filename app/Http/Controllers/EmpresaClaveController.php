<?php

namespace App\Http\Controllers;

use App\Models\EmpresaClave;
use App\Models\EmpresaLocal;
use App\Models\ServicioExterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;


class EmpresaClaveController extends Controller
{
    /**
     * 📋 Mostrar listado general de claves (index)
     */
    public function index()
    {
        $claves = EmpresaClave::with(['empresa', 'servicio'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('empresa_claves.index', compact('claves'));
    }

    /**
     * 🏢 Mostrar vista resumen agrupada por empresa.
     * Permite filtrar por empresa específica.
     */
    public function resumen(Request $request)
    {
        $empresaId = $request->get('empresa_id');

        $query = EmpresaLocal::with(['claves.servicio'])->orderBy('nombre');

        if ($empresaId) {
            $query->where('id', $empresaId);
        }

        $empresas = $query->get();
        $todasEmpresas = EmpresaLocal::orderBy('nombre')->pluck('nombre', 'id');
        $titulo = 'Claves por Empresa';

        return view('empresa_claves.resumen', compact('empresas', 'todasEmpresas', 'titulo', 'empresaId'));
    }

    /**
     * ➕ Mostrar formulario de creación de clave
     */
    public function create()
    {
        $empresas = EmpresaLocal::orderBy('nombre')->pluck('nombre', 'id');
        $servicios = ServicioExterno::orderBy('nombre')->pluck('nombre', 'id');
        $titulo = 'Crear Clave';
        $empresaClave = new EmpresaClave();

        return view('empresa_claves.create', compact('empresas', 'servicios', 'titulo', 'empresaClave'));
    }

    /**
     * 💾 Guardar nueva clave
     */
    public function store(Request $request)
    {
        $request->validate([
            'empresa_local_id'    => 'required|exists:empresa_local,id',
            'servicio_externo_id' => 'required|exists:servicios_externos,id',
            'usuario'             => 'nullable|string|max:255',
            'correo_registrado'   => 'nullable|string|max:255',
            'password'            => 'nullable|string|max:255',
            
        ]);

        EmpresaClave::create([
            'empresa_local_id'    => $request->empresa_local_id,
            'servicio_externo_id' => $request->servicio_externo_id,
            'usuario'             => $request->usuario,
            'correo_registrado'   => $request->correo_registrado,
            'password'            => $request->password,
            
        ]);

        return redirect()
            ->route('empresa-claves.index')
            ->with('success', '✅ Clave creada correctamente.');
    }

    /**
     * ✏️ Mostrar formulario de edición de una clave
     */
    public function edit(EmpresaClave $empresaClave)
    {
        $empresas = EmpresaLocal::orderBy('nombre')->pluck('nombre', 'id');
        $servicios = ServicioExterno::orderBy('nombre')->pluck('nombre', 'id');
        $titulo = 'Editar Clave';

        return view('empresa_claves.edit', compact('empresaClave', 'empresas', 'servicios', 'titulo'));
    }

    /**
     * 🔄 Actualizar una clave existente
     */
    public function update(Request $request, EmpresaClave $empresaClave)
    {
        $request->validate([
            'empresa_local_id'    => 'required|exists:empresa_local,id',
            'servicio_externo_id' => 'required|exists:servicios_externos,id',
            'usuario'             => 'nullable|string|max:255',
            'correo_registrado'   => 'nullable|string|max:255',
            'password'            => 'nullable|string|max:255',
        ]);

        $empresaClave->update([
            'empresa_local_id'    => $request->empresa_local_id,
            'servicio_externo_id' => $request->servicio_externo_id,
            'usuario'             => $request->usuario,
            'correo_registrado'   => $request->correo_registrado,
            'password'            => $request->password,
        ]);

        return redirect()
            ->route('empresa-claves.index')
            ->with('success', '🔄 Clave actualizada correctamente.');
    }

    /**
     * 🗑️ Eliminar una clave
     */
    public function destroy(EmpresaClave $empresaClave)
    {
        $empresaClave->delete();

        return redirect()
            ->route('empresa-claves.index')
            ->with('success', '🗑️ Clave eliminada correctamente.');
    }
}
