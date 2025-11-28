<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotaController extends Controller
{
    // LISTADO
    public function index(Request $request)
    {
        $query = Nota::with(['creador'])
            ->orderBy('estado')
            ->orderBy('fecha_vencimiento')
            ->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('titulo', 'like', "%$buscar%")
                  ->orWhere('descripcion', 'like', "%$buscar%");
            });
        }

        $notas = $query->paginate(20);
        $titulo = 'Notas / Tareas';

        return view('notas.index', compact('notas', 'titulo'));
    }

    // NUEVA
    public function create()
    {
        $nota   = new Nota();
        $titulo = 'Crear Nota';

        return view('notas.create', compact('nota', 'titulo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'            => 'required|string|max:255',
            'descripcion'       => 'nullable|string',
            'tipo'              => 'required|in:traslado,certificado,cambio_empresa,recordatorio,otro',
            'estado'            => 'required|in:pendiente,en_proceso,resuelto,cancelado',
            'fecha_vencimiento' => 'nullable|date',
        ]);

        $data['creado_por_id'] = Auth::id();

        if (in_array($data['estado'], ['resuelto', 'cancelado'])) {
            $data['fecha_resuelto'] = now();
            $data['resuelto_por_id'] = Auth::id();
        }

        Nota::create($data);

        return redirect()->route('notas.index')
            ->with('success', 'Nota creada correctamente.');
    }

    // EDITAR
    public function edit(Nota $nota)
    {
        $titulo = 'Editar Nota';

        return view('notas.edit', compact('nota', 'titulo'));
    }

    public function update(Request $request, Nota $nota)
    {
        $data = $request->validate([
            'titulo'            => 'required|string|max:255',
            'descripcion'       => 'nullable|string',
            'tipo'              => 'required|in:traslado,certificado,cambio_empresa,recordatorio,otro',
            'estado'            => 'required|in:pendiente,en_proceso,resuelto,cancelado',
            'fecha_vencimiento' => 'nullable|date',
        ]);

        if (in_array($data['estado'], ['resuelto', 'cancelado']) && !$nota->fecha_resuelto) {
            $data['fecha_resuelto'] = now();
            $data['resuelto_por_id'] = Auth::id();
        }

        $nota->update($data);

        return redirect()->route('notas.index')
            ->with('success', 'Nota actualizada correctamente.');
    }

    public function destroy(Nota $nota)
    {
        $nota->delete();

        return redirect()->route('notas.index')
            ->with('success', 'Nota eliminada correctamente.');
    }
}
