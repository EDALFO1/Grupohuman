<?php

namespace App\Http\Controllers;

use App\Models\{Remision, UsuarioExterno};
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RemisionController extends Controller
{
   public function index(Request $request)
{
    $empresaId = (int) session('empresa_local_id'); // o auth()->user()->empresa_local_id

    $period = $request->query('period', now()->format('Y-m'));

    if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
        $period = now()->format('Y-m');
    }

    [$year, $month] = explode('-', $period);

    $remisiones = Remision::with(['usuarioExterno'])
                    ->where('empresa_local_id', $empresaId) // 🔒 filtro por empresa
                    ->forMonth((int)$year, (int)$month)     // tu scope personalizado
                    ->orderBy('fecha', 'desc')
                    ->paginate(25)
                    ->appends(['period' => $period]);

    return view('remisiones.index', compact('remisiones', 'period'));
}

     public function apiListByPeriod(Request $request)
    {
        $period = $request->query('period', now()->format('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            return response()->json(['error' => 'Periodo inválido'], 422);
        }

        [$year, $month] = explode('-', $period);

        $remisiones = Remision::with(['usuarioExterno'])
                        ->forMonth((int)$year, (int)$month)
                        ->orderBy('fecha', 'desc')
                        ->get();

        // Si quieres transformar/ocultar campos sensibles, hazlo aquí.
        return response()->json(['remisiones' => $remisiones]);
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

                $fRem  = Carbon::parse($validated['fecha']);
                $base  = $fRem->copy()->subMonthNoOverflow();
                $fRet  = Carbon::parse($validated['fecha_retiro'])->startOfDay();
                $fAf   = $usuario->fecha_afiliacion ? Carbon::parse($usuario->fecha_afiliacion)->startOfDay() : null;

                // Validación original: retiro dentro del mes base (regla base-30)
                $this->validarRetiroMesAnteriorBase30($validated['fecha'], $validated['fecha_retiro']);

                // 🚫 Extra: si la afiliación cae en ese mes base, no permitir retiro < afiliación
                if ($fAf && $fAf->format('Y-m') === $base->format('Y-m') && $fRet->lt($fAf)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'fecha_retiro' => "La fecha de retiro no puede ser inferior a la fecha de afiliación del usuario ({$fAf->format('Y-m-d')}).",
                    ]);
                }

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

            // 🚫 Si es Retiro y días = 0 → error
            if ($novedad === 'Retiro' && $dias === 0) {
                $msgAf = $usuario->fecha_afiliacion
                    ? Carbon::parse($usuario->fecha_afiliacion)->format('Y-m-d')
                    : 'N/D';
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'fecha_retiro' => "Con la fecha seleccionada el cálculo produce 0 días. Verifique que la fecha de retiro no sea anterior a la fecha de afiliación ({$msgAf}) dentro del mes base.",
                ]);
            }

            // Redondeos y total
            $otros = (int) round(($validated['otros_servicios'] ?? 0) / 100) * 100;
            $total = array_sum($valores) + $otros;

            // 🔢 Consecutivo único por empresa (con lock para concurrencia)
            $ultimoNumero = Remision::where('empresa_local_id', $empresaId)
                ->lockForUpdate()
                ->max('numero') ?? 0;

            $nuevoNumero = $ultimoNumero + 1;

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
        ->where('empresa_local_id', $empresaId) // 👈 filtro empresa
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
            $remision = Remision::where('empresa_local_id', $empresaId) // 👈 filtro empresa
                ->findOrFail($id);

            $usuario = UsuarioExterno::with(['eps','arl','pension','caja'])
                ->findOrFail($validated['usuario_externo_id']);

            if ((int)$usuario->empresa_local_id !== $empresaId) {
                abort(422, 'El usuario seleccionado no pertenece a la empresa actual.');
            }

            // 🔒 Unicidad por período (mes ANTERIOR a la fecha de la remisión)
            $fr         = \Carbon\Carbon::parse($validated['fecha']);
            $periodoIni = $fr->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $periodoFin = $fr->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
            $periodoStr = \Carbon\Carbon::parse($periodoIni)->format('Y-m');

            $yaExiste = \App\Models\Remision::where('empresa_local_id', $empresaId)
                ->where('usuario_externo_id', $usuario->id)
                ->where('id', '<>', $remision->id) // 👈 excluir la actual
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

                $fRem = \Carbon\Carbon::parse($validated['fecha']);
                $base = $fRem->copy()->subMonthNoOverflow();
                $fRet = \Carbon\Carbon::parse($validated['fecha_retiro'])->startOfDay();
                $fAf  = $usuario->fecha_afiliacion
                    ? \Carbon\Carbon::parse($usuario->fecha_afiliacion)->startOfDay()
                    : null;

                if ($fAf && $fAf->format('Y-m') === $base->format('Y-m') && $fRet->lt($fAf)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'fecha_retiro' => "La fecha de retiro no puede ser inferior a la fecha de afiliación del usuario ({$fAf->format('Y-m-d')}).",
                    ]);
                }

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

            if ($novedad === 'Retiro' && $dias === 0) {
                $msgAf = $usuario->fecha_afiliacion
                    ? \Carbon\Carbon::parse($usuario->fecha_afiliacion)->format('Y-m-d')
                    : 'N/D';
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'fecha_retiro' => "Con la fecha seleccionada el cálculo produce 0 días. Verifique que la fecha de retiro no sea anterior a la fecha de afiliación ({$msgAf}) dentro del mes base.",
                ]);
            }

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

    $remision = Remision::where('empresa_local_id', $empresaId) // 👈 filtro empresa
        ->findOrFail($id);

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
    // App\Http\Controllers\RemisionController.php

// ====== Reglas base-30: retiro en mes anterior (día 1..30) ======
private function validarRetiroMesAnteriorBase30(string $fechaRemision, string $fechaRetiro, ?string $fechaAfiliacion = null): void
{
    $fr   = \Carbon\Carbon::createFromFormat('Y-m-d', $fechaRemision)->startOfDay();
    $fret = \Carbon\Carbon::createFromFormat('Y-m-d', $fechaRetiro)->startOfDay();

    // El retiro DEBE estar en el mes ANTERIOR a la remisión y entre 1..30 (base-30)
    $prev      = $fr->copy()->subMonthNoOverflow();
    $inicioMes = $prev->copy()->startOfMonth()->startOfDay();
    $finMes    = $prev->copy()->endOfMonth()->startOfDay();

    if ($fret->format('Y-m') !== $prev->format('Y-m')) {
        throw new \InvalidArgumentException('La fecha de retiro debe estar dentro del mes anterior a la remisión.');
    }
    $dia = (int) $fret->day;
    if ($dia < 1 || $dia > 30) {
        throw new \InvalidArgumentException('La fecha de retiro debe estar en el rango 1..30 (base 30).');
    }

    // 🚫 NUEVO: si la afiliación cae en el MISMO mes base, el retiro no puede ser menor a la afiliación
    if ($fechaAfiliacion) {
        $af = \Carbon\Carbon::createFromFormat('Y-m-d', $fechaAfiliacion)->startOfDay();
        if ($af->format('Y-m') === $prev->format('Y-m') && $fret->lt($af)) {
            $msgAf = $af->format('Y-m-d');
            throw new \InvalidArgumentException("La fecha de retiro no puede ser inferior a la fecha de afiliación del usuario ({$msgAf}).");
        }
    }
}

    // App\Http\Controllers\RemisionController.php

public function imprimir($id)
{
    $empresaId = (int) session('empresa_local_id');

    $remision = \App\Models\Remision::with([
            'usuarioExterno.eps',
            'usuarioExterno.arl',
            'usuarioExterno.pension',
            'usuarioExterno.caja',
            'usuarioExterno.empresaLocal',
            'usuarioExterno.empresaExterna',
            'usuarioExterno.empresaLocal',
        ])
        ->deEmpresa($empresaId)
        ->findOrFail($id);

    return view('remisiones.imprimir', compact('remision'));
}
public function apiPeriod(Request $request)
{
    $empresaId = (int) session('empresa_local_id');
    $period = $request->get('period'); // YYYY-MM
    [$year, $month] = explode('-', $period);

    $remisiones = Remision::where('empresa_local_id', $empresaId)
                          ->whereYear('fecha', $year)
                          ->whereMonth('fecha', $month)
                          ->with('usuarioExterno')
                          ->get();

    return response()->json(['remisiones' => $remisiones]);
}


}
