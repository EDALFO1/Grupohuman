<?php

namespace App\Http\Controllers;

use App\Models\UsuarioExterno;
use App\Models\{Documento, Asesor, Eps, Arl, SubtipoCotizante, Pension, Caja, EmpresaLocal, EmpresaExterna};
use App\Http\Requests\UsuarioExternoStoreRequest;
use App\Http\Requests\UsuarioExternoUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsuarioExternosTemplateExport;




class UsuarioExternoController extends Controller
{
    public function index(Request $request)
{
    $empresaLocalId = session('empresa_local_id');

    // Tamaños de página permitidos
    $allowed = [10, 25, 50, 100, 200];
    $perPage = $request->integer('per_page', 10);
    if (!in_array($perPage, $allowed, true)) {
        $perPage = 10;
    }

    $usuarios = UsuarioExterno::with([
            'documento','eps','arl','pension','caja','asesor','empresaLocal','empresaExterna'
        ])
        ->when(!$request->boolean('all') && $empresaLocalId, fn($q) => $q->deEmpresa($empresaLocalId))
        ->orderBy('id', 'desc')
        ->paginate($perPage)
        ->appends($request->query()); // preserva TODOS los parámetros de la URL

    return view('usuario_externos.index', compact('usuarios'));
}


    public function create()
    {
    return view('usuario_externos.create', [
        'usuarioExterno'  => new \App\Models\UsuarioExterno(),
        'documentos'      => Documento::orderBy('nombre')->get(),
        'asesores'        => Asesor::orderBy('nombre')->get(),
        'eps'             => Eps::orderBy('nombre')->get(),
        'arls'            => Arl::orderBy('nivel')->get(),
        'pensions'        => Pension::orderBy('nombre')->get(),
        'cajas'           => Caja::orderBy('nombre')->get(),
        'subtipos'        => SubtipoCotizante::orderBy('nombre')->get(),
        'empresaExternas' => EmpresaExterna::orderBy('nombre')->get(),
        'empresaActual'   => EmpresaLocal::find(session('empresa_local_id')),
        
    ]);
    }



    
    public function store(UsuarioExternoStoreRequest $request)
{
    $data = $request->validated();

    // Forzar empresa desde la sesión (sin permitir elección manual)
    $empresaId = (int) session('empresa_local_id');
    if (!$empresaId) {
        return back()
            ->withErrors(['empresa_local_id' => 'No hay empresa activa en la sesión.'])
            ->withInput();
    }
    $data['empresa_local_id'] = $empresaId;

    // Estado inicial
    $data['novedad']      = 'Ingreso';
    $data['fecha_retiro'] = null;

    $usuario = UsuarioExterno::create($data);

    // Marca ACTIVO para el período siguiente al ingreso
    $this->marcarPeriodoPorIngreso($usuario);

    return redirect()->route('usuario_externos')
        ->with('success', 'Usuario Externo creado correctamente.');
}



    public function update(Request $request, UsuarioExterno $usuarioExterno)
{
    // Validación principal (añadimos google_drive_folder_id)
    $validated = $request->validate([
        'documento_id'            => 'required|exists:documentos,id',
        'asesor_id'               => 'required|exists:asesores,id',
        'numero'                  => 'required|string|unique:usuario_externos,numero,' . $usuarioExterno->id,
        'fecha_expedicion'        => 'required|date',
        'primer_apellido'         => 'required|string',
        'segundo_apellido'        => 'nullable|string',
        'primer_nombre'           => 'required|string',
        'segundo_nombre'          => 'nullable|string',
        'fecha_nacimiento'        => 'required|date',
        'correo_electronico'      => 'nullable|email',
        'direccion'               => 'required|string',
        'telefono'                => 'required|string',
        'fecha_afiliacion'        => 'required|date',
        'sexo'                    => 'required|in:M,F,Otro',
        'eps_id'                  => 'required|exists:eps,id',
        'arl_id'                  => 'required|exists:arls,id',
        'pension_id'              => 'required|exists:pensions,id',
        'caja_id'                 => 'required|exists:cajas,id',
        'empresa_externa_id'      => 'required|exists:empresa_externas,id',
        'subtipo_cotizantes_id'   => 'required|exists:subtipo_cotizantes,id',
        'sueldo'                  => 'required|numeric|min:0',
        'admon'                   => 'required|numeric|min:0',
        'seg_exequial'            => 'nullable|numeric|min:0',
        'mora'                    => 'nullable|numeric|min:0',
        'otros_servicios'         => 'nullable|numeric|min:0',
        'cargo'                   => 'required|string',
        'estado'                  => 'required|boolean',
        'novedad'                 => ['required', \Illuminate\Validation\Rule::in(['Ingreso','Retiro'])],
        'fecha_retiro'            => 'nullable|date|after_or_equal:fecha_afiliacion|required_if:novedad,Retiro',

        // <-- Añadimos google_drive_folder_id aquí (puede venir como ID puro o URL completa)
        'google_drive_folder_id'  => 'nullable|string|max:255',
    ], [
        'fecha_retiro.required_if' => 'Debe indicar la fecha de retiro cuando la novedad es Retiro.',
    ]);

    // Normalizar google_drive_folder_id (extraer solo el id si el usuario pegó una URL)
    if (!empty($validated['google_drive_folder_id'])) {
        $validated['google_drive_folder_id'] = $this->extractDriveFolderId($validated['google_drive_folder_id']);
    }

    // ¿Se está REACTIVANDO? (antes inactivo → ahora activo)
    $reactivando = (!$usuarioExterno->estado && (bool)$validated['estado'] === true);

    if ($reactivando) {
        // ✅ Al reactivar SÍ se permite elegir la empresa destino (misma u otra)
        $request->validate([
            'empresa_local_id' => 'required|exists:empresa_local,id',
        ]);

        $validated['empresa_local_id'] = (int) $request->input('empresa_local_id');
        $validated['novedad']          = 'Ingreso';
        $validated['fecha_retiro']     = null;

    } else {
        // 🚫 Si NO se está reactivando, no permitimos cambiar la empresa
        $validated['empresa_local_id'] = $usuarioExterno->empresa_local_id;

        // coherencia de retiro
        if (($validated['novedad'] ?? 'Ingreso') !== 'Retiro') {
            $validated['fecha_retiro'] = null;
        }
    }

    // Ahora actualizamos incluyendo google_drive_folder_id
    $usuarioExterno->update($validated);

    // (Opcional) Si quieres marcar el período activo al reactivar:
    if ($reactivando && method_exists($this, 'marcarPeriodoPorIngreso')) {
        $this->marcarPeriodoPorIngreso($usuarioExterno->fresh());
    }

    return redirect()->route('usuario_externos')
        ->with('success', $reactivando
            ? 'Usuario reactivado correctamente (Ingreso).'
            : 'Usuario Externo actualizado correctamente.'
        );
}

/**
 * Extrae el ID de carpeta de Google Drive de una URL o devuelve el valor limpio.
 * Ejemplos aceptados:
 *  - https://drive.google.com/drive/folders/1xY...ABc?hl=es  => 1xY...ABc
 *  - 1xY...ABc                                            => 1xY...ABc
 */
private function extractDriveFolderId(string $input): string
{
    // Si contiene "folders/", extraemos lo que sigue hasta ? o / o final
    if (preg_match('#/folders/([a-zA-Z0-9_-]+)#', $input, $m)) {
        return $m[1];
    }

    // Si fue pegado como URL con id=... (caso alterno), extraemos id=...
    if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $input, $m2)) {
        return $m2[1];
    }

    // Si trae parámetros (?hl=es), los quitamos
    if (strpos($input, '?') !== false) {
        $input = strstr($input, '?', true);
    }

    // Trim final y devolver
    return trim($input);
}



    public function show(UsuarioExterno $usuarioExterno)
    {
        return view('usuario_externos.show', compact('usuarioExterno'));
    }
     public function edit(UsuarioExterno $usuarioExterno)
    {
    return view('usuario_externos.edit', [
        'usuarioExterno'  => $usuarioExterno,
        'documentos'      => Documento::orderBy('nombre')->get(),
        'asesores'        => Asesor::orderBy('nombre')->get(),
        'eps'             => Eps::orderBy('nombre')->get(),
        'arls'            => Arl::orderBy('nivel')->get(),
        'pensions'        => Pension::orderBy('nombre')->get(),
        'cajas'           => Caja::orderBy('nombre')->get(),
        'empresaActual'   => EmpresaLocal::find($usuarioExterno->empresa_local_id),
        'empresaExternas' => EmpresaExterna::orderBy('nombre')->get(),
        'subtipos'        => SubtipoCotizante::orderBy('nombre')->get(),
        // 👇 solo se usará si el usuario está INACTIVO
        'empresasLocales' => EmpresaLocal::orderBy('nombre')->get(),
    ]);
    }

    public function destroy(UsuarioExterno $usuarioExterno)
    {
        try {
            $usuarioExterno->delete();
            return redirect()->route('usuario_externos')->with('success','Usuario eliminado.');
        } catch (\Throwable $e) {
            return back()->with('error', 'No se puede eliminar: tiene información relacionada.');
        }
    }
    public function downloadTemplate()
    {
    return Excel::download(new UsuarioExternosTemplateExport, 'usuario_externos_template.xlsx');
    }
    public function reactivar(Request $request, UsuarioExterno $usuario)
    {
    $data = $request->validate([
        'empresa_local_id'   => 'required|exists:empresa_local,id',
        'fecha_afiliacion'   => 'nullable|date',
    ]);

    $usuario->update([
        'estado'          => true,
        'novedad'         => 'Ingreso',
        'fecha_retiro'    => null,
        'empresa_local_id'=> $data['empresa_local_id'],
        'fecha_afiliacion'=> $data['fecha_afiliacion'] ?? $usuario->fecha_afiliacion,
    ]);
    $this->marcarPeriodoPorIngreso($usuario->fresh());


    return back()->with('success', 'Usuario reactivado y (si aplica) trasladado de empresa.');
    }
    private function marcarPeriodoPorIngreso(\App\Models\UsuarioExterno $usuario): void
{
    if (!$usuario->fecha_afiliacion || !$usuario->empresa_local_id) return;

    $periodo = \Carbon\Carbon::parse($usuario->fecha_afiliacion)
        ->addMonthNoOverflow()
        ->format('Y-m');

    \App\Models\PeriodoUsuario::updateOrCreate(
        [
            'empresa_local_id'   => (int) $usuario->empresa_local_id,
            'usuario_externo_id' => (int) $usuario->id,
            'periodo'            => $periodo,
        ],
        [
            'estado'    => 'Activo',
            'recibo_id' => null, // aún no hay recibo; quedará null
        ]
    );
}

}
