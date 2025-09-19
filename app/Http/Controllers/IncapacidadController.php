<?php

namespace App\Http\Controllers;

use App\Models\Incapacidad;
use App\Models\IncapacidadObservacion;
use App\Models\UsuarioExterno;
use App\Models\Eps;
use App\Models\Arl;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class IncapacidadController extends Controller
{
    protected array $estados = ['transcrita','radicada','aprobada','liquidada','rechazada','pagada'];
    protected array $estadosCierre = ['liquidada','rechazada','pagada'];

    // LISTADO
    public function index(Request $request)
    {
        $q = Incapacidad::query()
            ->with(['empresaLocal','empresaExterna'])
            ->when($request->filled('estado'), fn($qq) => $qq->where('estado', $request->estado))
            ->when($request->filled('documento'), fn($qq) => $qq->where('documento', 'like', '%'.$request->documento.'%'))
            ->when($request->filled('vigencia'), function($qq) use ($request) {
                if ($request->vigencia === 'activas')  { $qq->where('cerrada', false); }
                if ($request->vigencia === 'cerradas') { $qq->where('cerrada', true); }
            })
            ->orderByDesc('id');

        $incapacidades = $q->paginate(15)->appends($request->query());
        $titulo = 'Incapacidades';

        return view('incapacidades.index', compact('incapacidades','titulo'));
    }

    // FORM CREAR
    public function create()
    {
        $titulo = 'Nueva Incapacidad';
        $entidadTipos = ['EPS','ARL'];
        $epsList = Eps::orderBy('nombre')->get();
        $arlList = Arl::orderBy('nombre')->get();

        return view('incapacidades.create', [
            'titulo' => $titulo,
            'estados' => $this->estados,
            'entidadTipos' => $entidadTipos,
            'epsList' => $epsList,
            'arlList' => $arlList,
        ]);
    }

    // GUARDAR
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // Cálculos
        $data['dias_solicitados'] = Incapacidad::calcularDiasSolicitados($data['fecha_inicio'], $data['fecha_fin']);
        $data['entidad_nombre'] = $this->resolverNombreEntidad($data['entidad_tipo'], $data['eps_id'] ?? null, $data['arl_id'] ?? null, $data['entidad_nombre'] ?? null);
        $this->aplicarCierre($data);

        DB::transaction(function() use ($data, $request) {
            $inc = Incapacidad::create($data);

            if ($request->filled('observacion_inicial')) {
                IncapacidadObservacion::create([
                    'incapacidad_id' => $inc->id,
                    'nota' => $request->observacion_inicial,
                ]);
            }
        });

        return redirect()->route('incapacidades.index')->with('ok','Incapacidad creada correctamente.');
    }

    // FORM EDITAR
    public function edit(Incapacidad $incapacidad)
    {
        $titulo = 'Editar Incapacidad #'.$incapacidad->id;
        $entidadTipos = ['EPS','ARL'];
        $epsList = Eps::orderBy('nombre')->get();
        $arlList = Arl::orderBy('nombre')->get();
        $incapacidad->load('observaciones');

        return view('incapacidades.edit', compact('incapacidad','titulo','entidadTipos','epsList','arlList') + [
            'estados' => $this->estados
        ]);
    }

    // ACTUALIZAR
    public function update(Request $request, Incapacidad $incapacidad)
    {
        $data = $this->validateData($request);
        $data['dias_solicitados'] = Incapacidad::calcularDiasSolicitados($data['fecha_inicio'], $data['fecha_fin']);
        $data['entidad_nombre'] = $this->resolverNombreEntidad($data['entidad_tipo'], $data['eps_id'] ?? null, $data['arl_id'] ?? null, $data['entidad_nombre'] ?? null);
        $this->aplicarCierre($data);

        $incapacidad->update($data);

        if ($request->filled('nueva_observacion')) {
            IncapacidadObservacion::create([
                'incapacidad_id' => $incapacidad->id,
                'nota' => $request->nueva_observacion,
            ]);
        }

        return redirect()->route('incapacidades.index')->with('ok','Incapacidad actualizada.');
    }

    // ELIMINAR
    public function destroy(Incapacidad $incapacidad)
    {
        $incapacidad->delete();
        return redirect()->route('incapacidades.index')->with('ok','Incapacidad eliminada.');
    }

    // CERRAR MANUALMENTE (opcional)
    public function cerrar(Incapacidad $incapacidad)
    {
        $incapacidad->update([
            'cerrada' => true,
            'fecha_cierre' => now()->toDateString(),
        ]);
        return back()->with('ok', 'Incapacidad cerrada.');
    }

    // Observación vía AJAX
    public function agregarObservacion(Request $request, Incapacidad $incapacidad)
    {
        $request->validate(['nota' => ['required','string','max:2000']]);

        $obs = IncapacidadObservacion::create([
            'incapacidad_id' => $incapacidad->id,
            'nota' => $request->nota,
        ]);

        return response()->json([
            'ok' => true,
            'observacion' => [
                'id' => $obs->id,
                'nota' => $obs->nota,
                'created_at' => $obs->created_at->format('Y-m-d H:i'),
            ]
        ]);
    }

    // Buscar usuario externo por documento
    public function buscarUsuario(Request $request)
    {
        $request->validate(['documento' => ['required','string']]);

        $u = UsuarioExterno::with(['eps','arl','empresaLocal','empresaExterna'])
            ->where('numero', $request->documento)->first();

        if (!$u) return response()->json(['ok' => false, 'msg' => 'Usuario no encontrado.']);

        $nombre = trim($u->primer_nombre.' '.($u->segundo_nombre ?? '').' '.$u->primer_apellido.' '.($u->segundo_apellido ?? ''));

        return response()->json([
            'ok' => true,
            'usuario' => [
                'id' => $u->id,
                'documento' => $u->numero,
                'nombre' => $nombre,
                'empresa_local_id' => $u->empresa_local_id,
                'empresa_local_nombre' => optional($u->empresaLocal)->nombre,
                'empresa_externa_id' => $u->empresa_externa_id,
                'empresa_externa_nombre' => optional($u->empresaExterna)->nombre,
                'eps_id' => $u->eps_id,
                'eps_nombre' => optional($u->eps)->nombre,
                'arl_id' => $u->arl_id,
                'arl_nombre' => optional($u->arl)->nombre,
            ]
        ]);
    }

    // ===== Helpers =====

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'usuario_externo_id' => ['nullable','exists:usuario_externos,id'],
            'documento' => ['required','string','max:50'],
            'nombre' => ['required','string','max:255'],

            'empresa_externa_id' => ['nullable','exists:empresa_externas,id'],
            'empresa_local_id' => ['nullable','exists:empresa_local,id'],

            'entidad_tipo' => ['required', Rule::in(['EPS','ARL'])],
            'eps_id' => ['nullable','exists:eps,id'],
            'arl_id' => ['nullable','exists:arls,id'],
            'entidad_nombre' => ['nullable','string','max:255'],

            'fecha_inicio' => ['required','date'],
            'fecha_fin' => ['required','date','after_or_equal:fecha_inicio'],

            'fecha_radicacion' => ['nullable','date'],
            'estado' => ['required', Rule::in($this->estados)],
            'fecha_cierre' => ['nullable','date'],
            'observaciones_libres' => ['nullable','string'],
            'fecha_pago' => ['nullable','date'],
        ]);
    }

    protected function aplicarCierre(array &$data): void
    {
        if (in_array($data['estado'], $this->estadosCierre, true)) {
            $data['cerrada'] = true;
            $data['fecha_cierre'] = $data['fecha_cierre'] ?? now()->toDateString();
        } else {
            $data['cerrada'] = false;
            $data['fecha_cierre'] = null;
        }
    }

    protected function resolverNombreEntidad(string $tipo, $epsId, $arlId, $fallback = null): string
    {
        if ($tipo === 'EPS' && $epsId) { $eps = Eps::find($epsId); if ($eps) return $eps->nombre; }
        if ($tipo === 'ARL' && $arlId) { $arl = Arl::find($arlId); if ($arl) return $arl->nombre; }
        return $fallback ?: 'N/D';
    }
}
