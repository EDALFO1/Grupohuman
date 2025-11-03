<?php

namespace App\Http\Controllers;

use App\Models\ArlUsuario;
use App\Models\{Documento, Arl, EmpresaLocal, EmpresaExterna};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;


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
        ->estado($request->input('estado'))       // ← NUEVO: filtro por estado
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

    // Reglas base
    $rules = [
        'documento_id'       => ['required','exists:documentos,id'],
        'numero'             => ['required','string','unique:arl_usuarios,numero'],
        'nombre'             => ['required','string','max:255'],
        'fecha_ingreso'      => ['required','date'],
        'arl_id'             => ['required','exists:arls,id'],
        'empresa_externa_id' => ['required','exists:empresa_externas,id'],
        'base_cotizacion'    => ['nullable','numeric','min:0'],
        'administracion'     => ['nullable','numeric','min:0'],
        'estado'             => ['required','boolean'],
        'override_parametros'=> ['sometimes','boolean'],
    ];

    // Condición para fecha_retiro según estado
    if ($request->boolean('estado') === false) { // Inactivo
        $rules['fecha_retiro'] = ['required','date','after_or_equal:fecha_ingreso'];
    } else { // Activo
        $rules['fecha_retiro'] = ['nullable','date']; // se forzará a null más abajo
    }

    $data = $request->validate($rules);

    // Normalizar: si activo, la fecha de retiro debe quedar en NULL siempre
    if ($data['estado']) {
        $data['fecha_retiro'] = null;
    } elseif (empty($data['fecha_retiro'])) {
        // Por si llega como '' cuando está inactivo
        $data['fecha_retiro'] = null;
    }

    $data['empresa_local_id']   = $empresaId;
    $data['base_cotizacion']    = $data['base_cotizacion']  ?? 0;
    $data['administracion']     = $data['administracion']   ?? 0;
    $data['override_parametros']= (bool)($data['override_parametros'] ?? false);

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
    // Reglas base
    $rules = [
        'documento_id'       => ['required','exists:documentos,id'],
        'numero'             => ['required','string', Rule::unique('arl_usuarios','numero')->ignore($arlUsuario->id)],
        'nombre'             => ['required','string','max:255'],
        'fecha_ingreso'      => ['required','date'],
        'arl_id'             => ['required','exists:arls,id'],
        'empresa_externa_id' => ['required','exists:empresa_externas,id'],
        'base_cotizacion'    => ['nullable','numeric','min:0'],
        'administracion'     => ['nullable','numeric','min:0'],
        'estado'             => ['required','boolean'],
        'override_parametros'=> ['sometimes','boolean'],
    ];

    // Condición para fecha_retiro según estado
    if ($request->boolean('estado') === false) { // Inactivo
        $rules['fecha_retiro'] = ['required','date','after_or_equal:fecha_ingreso'];
    } else { // Activo
        $rules['fecha_retiro'] = ['nullable','date'];
    }

    $validated = $request->validate($rules);

    // Normalizar: si activo, fecha_retiro = null siempre
    if ($validated['estado']) {
        $validated['fecha_retiro'] = null;
    } elseif (empty($validated['fecha_retiro'])) {
        $validated['fecha_retiro'] = null;
    }

    // Mantener empresa_local_id
    $validated['empresa_local_id']   = $arlUsuario->empresa_local_id;
    $validated['base_cotizacion']    = $validated['base_cotizacion']  ?? 0;
    $validated['administracion']     = $validated['administracion']   ?? 0;
    $validated['override_parametros']= (bool)($validated['override_parametros'] ?? false);

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
    public function export(Request $request)
{
    $empresaLocalId = (int) session('empresa_local_id');

    $filename = 'arl_usuarios_'.now()->format('Ymd_His').'.xlsx';
    return Excel::download(
        new \App\Exports\ArlUsuariosExport(
            $empresaLocalId,
            $request->get('q'),
            $request->input('estado') // '1', '0' o null
        ),
        $filename
    );
}

}
