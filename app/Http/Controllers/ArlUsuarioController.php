<?php

namespace App\Http\Controllers;

use App\Models\ArlUsuario;
use App\Models\{Documento, Arl, EmpresaLocal, EmpresaExterna};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArlUsuarioController extends Controller
{
    public function index(Request $request)
    {
        $empresaLocalId = (int) session('empresa_local_id');
        $perPage = in_array($request->integer('per_page', 10), [10,25,50,100,200], true)
            ? $request->integer('per_page', 10) : 10;

        $q = ArlUsuario::with(['documento','arl','empresaExterna'])
            ->when($empresaLocalId, fn($qq) => $qq->deEmpresa($empresaLocalId))
            ->buscar($request->get('q'))
            ->orderByDesc('id');

        $usuarios = $q->paginate($perPage)->appends($request->query());

        return view('arl_usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('arl_usuarios.create', [
            'documentos'      => Documento::orderBy('nombre')->get(),
            'arls'            => Arl::orderBy('nivel')->get(),
            'empresaExternas' => EmpresaExterna::orderBy('nombre')->get(),
            'empresaActual'   => EmpresaLocal::find(session('empresa_local_id')),
            'arlUsuario'      => new ArlUsuario(),
        ]);
    }

    public function store(Request $request)
    {
        $empresaId = (int) session('empresa_local_id');
        if (!$empresaId) {
            return back()->withErrors(['empresa_local_id' => 'No hay empresa activa en la sesión.'])->withInput();
        }

        $data = $request->validate([
            'documento_id'       => ['required','exists:documentos,id'],
            'numero'             => ['required','string','unique:arl_usuarios,numero'],
            'nombre'             => ['required','string','max:255'],
            'fecha_ingreso'      => ['required','date'],
            'arl_id'             => ['required','exists:arls,id'],
            'empresa_externa_id' => ['required','exists:empresa_externas,id'],
            'base_cotizacion'    => ['nullable','numeric','min:0'],
            'administracion'     => ['nullable','numeric','min:0'],
            'estado'             => ['required','boolean'],
            'fecha_retiro'       => ['nullable','date','after_or_equal:fecha_ingreso'],
            'override_parametros'=> ['sometimes','boolean'],
        ]);

        $data['empresa_local_id'] = $empresaId;
        $data['base_cotizacion']  = $data['base_cotizacion']  ?? 0;
        $data['administracion']   = $data['administracion']   ?? 0;
        $data['override_parametros'] = (bool)($data['override_parametros'] ?? false);

        ArlUsuario::create($data);

        return redirect()->route('arl-usuarios.index')->with('success','Usuario ARL creado correctamente.');
    }

    public function edit(ArlUsuario $arlUsuario)
    {
        return view('arl_usuarios.edit', [
            'arlUsuario'      => $arlUsuario,
            'documentos'      => Documento::orderBy('nombre')->get(),
            'arls'            => Arl::orderBy('nivel')->get(),
            'empresaExternas' => EmpresaExterna::orderBy('nombre')->get(),
            'empresaActual'   => EmpresaLocal::find($arlUsuario->empresa_local_id),
        ]);
    }

    public function update(Request $request, ArlUsuario $arlUsuario)
    {
        $validated = $request->validate([
            'documento_id'       => ['required','exists:documentos,id'],
            'numero'             => ['required','string', Rule::unique('arl_usuarios','numero')->ignore($arlUsuario->id)],
            'nombre'             => ['required','string','max:255'],
            'fecha_ingreso'      => ['required','date'],
            'arl_id'             => ['required','exists:arls,id'],
            'empresa_externa_id' => ['required','exists:empresa_externas,id'],
            'base_cotizacion'    => ['nullable','numeric','min:0'],
            'administracion'     => ['nullable','numeric','min:0'],
            'estado'             => ['required','boolean'],
            'fecha_retiro'       => ['nullable','date','after_or_equal:fecha_ingreso'],
            'override_parametros'=> ['sometimes','boolean'],
        ]);

        // Mantener empresa_local (no se cambia aquí)
        $validated['empresa_local_id'] = $arlUsuario->empresa_local_id;
        $validated['base_cotizacion']  = $validated['base_cotizacion']  ?? 0;
        $validated['administracion']   = $validated['administracion']   ?? 0;
        $validated['override_parametros'] = (bool)($validated['override_parametros'] ?? false);

        $arlUsuario->update($validated);

        return redirect()->route('arl-usuarios.index')->with('success','Usuario ARL actualizado correctamente.');
    }

    public function destroy(ArlUsuario $arlUsuario)
    {
        try {
            $arlUsuario->delete();
            return back()->with('success','Usuario ARL eliminado.');
        } catch (\Throwable $e) {
            return back()->with('error','No se puede eliminar: tiene información relacionada.');
        }
    }
}
