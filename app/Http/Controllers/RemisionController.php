<?php

namespace App\Http\Controllers;

use App\Models\{Remision, UsuarioExterno};
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RemisionController extends Controller
{
    public function index()
    {
        $empresaId = session('empresa_local_id');

        $remisiones = Remision::with('usuarioExterno')
            ->deEmpresa($empresaId)
            ->latest('fecha')
            ->paginate(10);

        return view('remisiones.index', compact('remisiones'));
    }

    public function create()
    {
        return view('remisiones.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuario_externo_id' => 'required|exists:usuario_externos,id',
            'fecha'              => 'required|date',
            'novedad'            => 'required|in:Ingreso,Retiro',
            'fecha_retiro'       => 'nullable|date|after_or_equal:1900-01-01',
            'otros_servicios'    => 'required|numeric|min:0',
        ]);

        $empresaId = (int) session('empresa_local_id');

        try {
            return DB::transaction(function () use ($validated, $empresaId) {
                // Usuario + validación empresa
                $usuario = UsuarioExterno::with(['eps','arl','pension','caja'])
                    ->findOrFail($validated['usuario_externo_id']);

                if ((int)$usuario->empresa_local_id !== $empresaId) {
                    abort(422, 'El usuario seleccionado no pertenece a la empresa actual.');
                }
                // 🔒 Unicidad por período (mes ANTERIOR a la fecha de la remisión)
$fr         = Carbon::parse($validated['fecha']);
$periodoIni = $fr->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
$periodoFin = $fr->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
$periodoStr = Carbon::parse($periodoIni)->format('Y-m');

$yaExiste = Remision::where('empresa_local_id', $empresaId)
    ->where('usuario_externo_id', $usuario->id)
    ->whereRaw('DATE_SUB(fecha, INTERVAL 1 MONTH) BETWEEN ? AND ?', [$periodoIni, $periodoFin])
    ->exists();

if ($yaExiste) {
    throw \Illuminate\Validation\ValidationException::withMessages([
        'fecha' => "El usuario ya tiene una remisión para el período {$periodoStr}.",
    ]);
}


                // Novedad / retiro
                $novedad = $validated['novedad'];
                $fechaRetiro = null;

                if ($novedad === 'Retiro') {
                    if (empty($validated['fecha_retiro'])) {
                        abort(422, 'Debe indicar la fecha de retiro.');
                    }
                    $this->validarRetiroMesAnteriorBase30($validated['fecha'], $validated['fecha_retiro']);
                    $fechaRetiro = $validated['fecha_retiro'];
                }

                // Cálculo (base-30) vía servicio
                $dias = 0;
                $valores = LiquidacionService::calcular(
                    $usuario,
                    $validated['fecha'],
                    $novedad,
                    $fechaRetiro,
                    $dias
                );

                // Redondeos y total
                $otros = (int) round(($validated['otros_servicios'] ?? 0) / 100) * 100;
                $total = array_sum($valores) + $otros;

                // Consecutivo por empresa (con bloqueo para concurrencia)
                $ultimoNumero = Remision::where('empresa_local_id', $empresaId)
                    ->lockForUpdate()
                    ->max('numero') ?? 0;

                $nuevoNumero  = $ultimoNumero + 1;

                Remision::create([
                    'empresa_local_id'   => $empresaId,
                    'numero'             => $nuevoNumero,
                    'fecha'              => $validated['fecha'],
                    'usuario_externo_id' => $usuario->id,
                    'dias_liquidar'      => $dias,
                    'otros_servicios'    => $otros,
                    'total'              => $total,
                    'novedad'            => $novedad,
                    'fecha_retiro'       => $fechaRetiro,
                ] + $valores);

                return redirect()->route('remisiones')->with('success', 'Remisión creada correctamente.');
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['fecha_retiro' => $e->getMessage()])->withInput();
        }
    }

    public function edit($id)
    {
        $empresaId = (int) session('empresa_local_id');

        $remision = Remision::with([
                'usuarioExterno.eps',
                'usuarioExterno.arl',
                'usuarioExterno.pension',
                'usuarioExterno.caja'
            ])
            ->deEmpresa($empresaId)
            ->findOrFail($id);

        $remision->fecha = Carbon::parse($remision->fecha);
        if ($remision->fecha_retiro) {
            $remision->fecha_retiro = Carbon::parse($remision->fecha_retiro);
        }

        return view('remisiones.edit', compact('remision'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'usuario_externo_id' => 'required|exists:usuario_externos,id',
            'fecha'              => 'required|date',
            'novedad'            => 'required|in:Ingreso,Retiro',
            'fecha_retiro'       => 'nullable|date|after_or_equal:1900-01-01',
            'otros_servicios'    => 'required|numeric|min:0',
        ]);

        $empresaId = (int) session('empresa_local_id');

        try {
            return DB::transaction(function () use ($validated, $id, $empresaId) {
                $remision = Remision::deEmpresa($empresaId)->findOrFail($id);

                $usuario = UsuarioExterno::with(['eps','arl','pension','caja'])
                    ->findOrFail($validated['usuario_externo_id']);

                if ((int)$usuario->empresa_local_id !== $empresaId) {
                    abort(422, 'El usuario seleccionado no pertenece a la empresa actual.');
                }
                // 🔒 Unicidad por período (mes ANTERIOR a la fecha de la remisión)
$fr         = Carbon::parse($validated['fecha']);
$periodoIni = $fr->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
$periodoFin = $fr->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
$periodoStr = Carbon::parse($periodoIni)->format('Y-m');

$yaExiste = Remision::where('empresa_local_id', $empresaId)
    ->where('usuario_externo_id', $usuario->id)
    ->whereRaw('DATE_SUB(fecha, INTERVAL 1 MONTH) BETWEEN ? AND ?', [$periodoIni, $periodoFin])
    ->exists();

if ($yaExiste) {
    throw \Illuminate\Validation\ValidationException::withMessages([
        'fecha' => "El usuario ya tiene una remisión para el período {$periodoStr}.",
    ]);
}


                $novedad = $validated['novedad'];
                $fechaRetiro = null;

                if ($novedad === 'Retiro') {
                    if (empty($validated['fecha_retiro'])) {
                        abort(422, 'Debe indicar la fecha de retiro.');
                    }
                    $this->validarRetiroMesAnteriorBase30($validated['fecha'], $validated['fecha_retiro']);
                    $fechaRetiro = $validated['fecha_retiro'];
                }

                $dias = 0;
                $valores = LiquidacionService::calcular(
                    $usuario,
                    $validated['fecha'],
                    $novedad,
                    $fechaRetiro,
                    $dias
                );

                $otros = (int) round(($validated['otros_servicios'] ?? 0) / 100) * 100;
                $total = array_sum($valores) + $otros;

                $remision->update([
                    'fecha'              => $validated['fecha'],
                    'usuario_externo_id' => $usuario->id,
                    'dias_liquidar'      => $dias,
                    'otros_servicios'    => $otros,
                    'total'              => $total,
                    'novedad'            => $novedad,
                    'fecha_retiro'       => $fechaRetiro,
                ] + $valores);

                return redirect()->route('remisiones')->with('success', 'Remisión actualizada correctamente.');
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['fecha_retiro' => $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        $empresaId = (int) session('empresa_local_id');

        $remision = Remision::deEmpresa($empresaId)->findOrFail($id);
        $remision->delete();

        return redirect()->route('remisiones')->with('success', 'Remisión eliminada correctamente.');
    }

    public function buscarUsuario(Request $request, $numero = null)
    {
        $numero = trim($numero ?? (string) $request->input('numero', ''));

        if ($numero === '') {
            return response()->json(['message' => 'Número no proporcionado'], 400);
        }

        $empresaId = (int) session('empresa_local_id');

        $query = UsuarioExterno::with(['eps', 'arl', 'pension', 'caja'])
            ->where('numero', $numero);

        if ($empresaId) {
            $query->where('empresa_local_id', $empresaId);
        }

        $usuario = $query->first();

        if (!$usuario) {
            $msg = $empresaId
                ? 'Usuario no encontrado'
                : 'Usuario no encontrado (verifica que haya una empresa seleccionada).';
            return response()->json(['message' => $msg], 404);
        }

        return response()->json($usuario);
    }

    // ====== Reglas base-30: retiro en mes anterior (día 1..30) ======
    private function validarRetiroMesAnteriorBase30(string $fechaRemision, string $fechaRetiro): void
    {
        $fr   = Carbon::createFromFormat('Y-m-d', $fechaRemision)->startOfDay();
        $fret = Carbon::createFromFormat('Y-m-d', $fechaRetiro)->startOfDay();

        $prev = $fr->copy()->subMonthNoOverflow();

        if ($fret->format('Y-m') !== $prev->format('Y-m')) {
            throw new \InvalidArgumentException('La fecha de retiro debe estar dentro del mes anterior a la remisión (1..30).');
        }

        $dia = (int) $fret->day;
        if ($dia < 1 || $dia > 30) {
            throw new \InvalidArgumentException('La fecha de retiro debe estar dentro del mes anterior a la remisión (1..30).');
        }
    }
}
