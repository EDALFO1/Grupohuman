<?php

namespace App\Http\Controllers;

use App\Models\EmpresaLocal;
use App\Models\ExportBatch;
use App\Models\Recibo;
use App\Models\UsuarioExterno;
use App\Services\LiquidacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\PeriodoUsuario;


class ReciboController extends Controller
{
    public function index()
    {
        $titulo = 'Listado de recibos';

        $empresas = EmpresaLocal::withCount('usuarioExterno')
            ->having('usuario_externo_count', '>', 0)
            ->orderBy('nombre')
            ->get();

        $empresaIdActual = session('empresa_local_id');
        if (!$empresaIdActual && $empresas->isNotEmpty()) {
            $empresaIdActual = $empresas->first()->id;
            session(['empresa_local_id' => $empresaIdActual]);
        }

        $recibos = Recibo::with(['usuarioExterno.documento', 'usuarioExterno.empresaLocal'])
            ->deEmpresa($empresaIdActual)
            ->latest('fecha')
            ->paginate(15);

        $periodoActual   = now()->format('Y-m');
        $empresaIdActual = $empresaIdActual ?? ($empresas->first()->id ?? null);

        // ✅ Conteo de pendientes (solo si existe la columna)
        $pendientesCount = 0;
        if ($empresaIdActual && Schema::hasColumn('recibos', 'export_batch_id')) {
            $pendientesCount = Recibo::deEmpresa($empresaIdActual)->pendientes()->count();
        }

        return view('recibos.index', compact(
            'titulo',
            'empresas',
            'recibos',
            'periodoActual',
            'empresaIdActual',
            'pendientesCount'
        ));
    }

    private function esIngresoMesAnterior(UsuarioExterno $usuario, string $fechaRecibo): bool
    {
        $fr     = Carbon::parse($fechaRecibo);
        $inicio = $fr->copy()->subMonthNoOverflow()->startOfMonth();
        $fin    = $fr->copy()->subMonthNoOverflow()->endOfMonth();

        $af = $usuario->fecha_afiliacion instanceof Carbon
            ? $usuario->fecha_afiliacion
            : Carbon::parse($usuario->fecha_afiliacion);

        return $af->betweenIncluded($inicio, $fin);
    }

    public function create()
    {
        return view('recibos.create');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'usuario_externo_id' => 'required|exists:usuario_externos,id',
        'fecha'              => 'required|date',
        'novedad'            => 'nullable|in:Ingreso,Retiro',
        'fecha_retiro'       => 'nullable|date|after_or_equal:1900-01-01|required_if:novedad,Retiro',
        'otros_servicios'    => 'required|numeric|min:0',
    ]);

    $empresaId = (int) session('empresa_local_id');
    abort_if(!$empresaId, 422, 'No hay empresa seleccionada.');

    try {
        return DB::transaction(function () use ($validated, $empresaId) {
            $usuario = UsuarioExterno::with(['eps', 'arl', 'pension', 'caja'])
                ->findOrFail($validated['usuario_externo_id']);

            // Validar empresa del usuario
            if ((int) $usuario->empresa_local_id !== $empresaId) {
                throw ValidationException::withMessages([
                    'usuario_externo_id' => 'El usuario seleccionado no pertenece a la empresa actual.',
                ]);
            }

            // 🚫 Un recibo por período (mes ANTERIOR a la fecha del recibo)
            $fr         = Carbon::parse($validated['fecha']);
            $periodoIni = $fr->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $periodoFin = $fr->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
            $periodoStr = Carbon::parse($periodoIni)->format('Y-m');

            $yaExiste = Recibo::where('empresa_local_id', $empresaId)
                ->where('usuario_externo_id', $usuario->id)
                ->whereRaw('DATE_SUB(fecha, INTERVAL 1 MONTH) BETWEEN ? AND ?', [$periodoIni, $periodoFin])
                ->exists();

            if ($yaExiste) {
                throw ValidationException::withMessages([
                    'fecha' => "Usuario con recibo para el período {$periodoStr}.",
                ]);
            }

            // --- Novedad (NO forzar “Ingreso”) ---
            $nov = $validated['novedad'] ?? null;
            if ($nov === 'Retiro') {
                // Validar rango base-30 y coherencia con afiliación
                $this->validarRetiroConAfiliacion($usuario, $validated['fecha'], $validated['fecha_retiro']);
                $novedad     = 'Retiro';
                $fechaRetiro = $validated['fecha_retiro'];
            } elseif ($nov === 'Ingreso') {
                $novedad     = 'Ingreso';
                $fechaRetiro = null;
            } else {
                // Sin novedad
                $novedad     = null;
                $fechaRetiro = null;
            }

            // ✅ Validación adicional: el recibo debe estar en el MES SIGUIENTE al mes base
            $this->assertFechaReciboCorrespondeAlMesSiguiente($usuario, $validated['fecha'], $fechaRetiro);

            // ================== SNAPSHOT ARL (nivel, actividad, tarifa) ==================
            $nivelFromAny = static function ($nivel): ?int {
                if ($nivel === null) return null;
                if (is_numeric($nivel)) return max(1, min(5, (int) $nivel));
                if (preg_match('/(\d+)/', (string) $nivel, $m)) {
                    $n = (int) $m[1];
                    return ($n >= 1 && $n <= 5) ? $n : null;
                }
                return null;
            };

            $arlNivelSnap = $nivelFromAny($usuario->arl->nivel ?? $usuario->arl->nivel_riesgo ?? null);

            $mapConfig   = (array) config('arl.actividad_por_nivel', []);
            $mapFallback = [1 => '1711001', 2 => '2741001', 3 => '3432101', 4 => '4466301', 5 => '5432201'];
            $arlActSnap  = $usuario->arl->actividad_economica
                ?? ($arlNivelSnap ? ($mapConfig[$arlNivelSnap] ?? $mapFallback[$arlNivelSnap] ?? null) : null);

            // Tarifa “humana” (no ratio)
            $arlTarifaSnap = null;
            $p             = $usuario->arl->porcentaje ?? null;
            if ($p !== null) {
                $s = str_replace([',', '%', ' '], ['.', '', ''], (string) $p);
                if (is_numeric($s)) $arlTarifaSnap = (float) $s;
            }
            // ============================================================================

            // --- Bases SIEMPRE por BD/efectivo a la fecha (sin override) ---
            $fechaPeriodo = $validated['fecha'];
            $sueldoBase = (float) ($usuario->sueldoEfectivoParaFecha($fechaPeriodo) ?? $usuario->sueldo ?? 0);
            $admonBase  = (float) ($usuario->admonEfectivoParaFecha($fechaPeriodo)  ?? $usuario->admon  ?? 0);

            // Sobrescribir EN MEMORIA para LiquidacionService
            $usuario->setAttribute('sueldo', $sueldoBase);
            $usuario->setAttribute('admon', $admonBase);

            // Cálculo de valores
            $dias     = 0;
            $esRetiro = ($novedad === 'Retiro');
            $valores  = LiquidacionService::calcular(
                $usuario,
                $validated['fecha'],
                $esRetiro ? 'Retiro' : 'Ingreso', // para cálculo solo distingue Retiro
                $fechaRetiro,
                $dias
            );

            $otros = round(($validated['otros_servicios'] ?? 0) / 100) * 100;
            $total = array_sum($valores) + $otros;

            // Consecutivo por empresa con lock
            $last = Recibo::where('empresa_local_id', $empresaId)
                ->orderByDesc('numero')
                ->lockForUpdate()
                ->first();
            $nuevoNumero = ($last?->numero ?? 0) + 1;

            // Payload (incluye snapshots y posible novedad NULL)
            $payload = [
                'empresa_local_id'   => $empresaId,
                'numero'             => $nuevoNumero,
                'fecha'              => $validated['fecha'],
                'usuario_externo_id' => $usuario->id,
                'dias_liquidar'      => $dias,
                'valor_eps'          => $valores['valor_eps'],
                'valor_arl'          => $valores['valor_arl'],
                'valor_pension'      => $valores['valor_pension'],
                'valor_caja'         => $valores['valor_caja'],
                'valor_admon'        => $valores['valor_admon'],
                'valor_exequial'     => $valores['valor_exequial'] ?? 0,
                'valor_mora'         => $valores['valor_mora'] ?? 0,
                'otros_servicios'    => $otros, // ← corregido (sin espacio)
                'total'              => $total,
                'novedad'            => $novedad,     // ← puede ser null
                'fecha_retiro'       => $fechaRetiro, // ← puede ser null

                // Snapshot ARL
                'arl_nivel'          => $arlNivelSnap,
                'arl_actividad'      => $arlActSnap,
                'arl_tarifa'         => $arlTarifaSnap,
            ];

            // Snapshots de base (si existen columnas)
            if (Schema::hasColumn('recibos', 'sueldo_base')) {
                $payload['sueldo_base'] = $sueldoBase;
            }
            if (Schema::hasColumn('recibos', 'admon_base')) {
                $payload['admon_base'] = $admonBase;
            }

            // Snapshots de nombres (para Excel)
            $payload += [
                'eps_nombre'     => $usuario->eps->nombre ?? null,
                'arl_nombre'     => $usuario->arl->nombre ?? null,
                'pension_nombre' => $usuario->pension->nombre ?? null,
                'caja_nombre'    => $usuario->caja->nombre ?? null,
            ];

            // ✅ Crear y usar la MISMA instancia
            $rec = Recibo::create($payload);

            // ✅ Marca período SIGUIENTE (mes de la fecha del recibo + 1)
            $this->upsertPeriodoUsuario($rec);

            // Si es Retiro, marcar usuario inactivo
            if ($novedad === 'Retiro') {
                UsuarioExterno::whereKey($usuario->id)
                    ->lockForUpdate()
                    ->update([
                        'estado'       => false,
                        'novedad'      => 'Retiro',
                        'fecha_retiro' => $fechaRetiro,
                    ]);
            }

            return redirect()
                ->route('recibos')
                ->with('success', 'Recibo creado correctamente' . ($novedad === 'Retiro' ? ' y el usuario fue marcado como Inactivo.' : '.'));
        });
    } catch (InvalidArgumentException $e) {
        return back()->withErrors(['fecha_retiro' => $e->getMessage()])->withInput();
    }
}


    public function edit($id)
    {
        $empresaId = session('empresa_local_id');

        $recibo = Recibo::with([
                'usuarioExterno.eps',
                'usuarioExterno.arl',
                'usuarioExterno.pension',
                'usuarioExterno.caja',
            ])
            ->deEmpresa($empresaId)
            ->findOrFail($id);

        $recibo->fecha = Carbon::parse($recibo->fecha);
        if ($recibo->fecha_retiro) {
            $recibo->fecha_retiro = Carbon::parse($recibo->fecha_retiro);
        }

        return view('recibos.edit', compact('recibo'));
    }

   public function update(Request $request, Recibo $recibo)
{
    $validated = $request->validate([
        'usuario_externo_id' => 'required|exists:usuario_externos,id',
        'fecha'              => 'required|date',
        'novedad'            => 'nullable|in:Ingreso,Retiro',
        'fecha_retiro'       => 'nullable|date|after_or_equal:1900-01-01|required_if:novedad,Retiro',
        'otros_servicios'    => 'required|numeric|min:0',
    ]);

    $empresaId = (int) session('empresa_local_id');
    abort_if(!$empresaId, 422, 'No hay empresa seleccionada.');

    // El recibo debe pertenecer a la empresa activa
    abort_if((int) $recibo->empresa_local_id !== $empresaId, 403, 'Recibo de otra empresa.');

    try {
        return DB::transaction(function () use ($validated, $empresaId, $recibo) {
            $usuario = UsuarioExterno::with(['eps', 'arl', 'pension', 'caja'])
                ->findOrFail($validated['usuario_externo_id']);

            // Validar empresa del usuario
            if ((int) $usuario->empresa_local_id !== $empresaId) {
                throw ValidationException::withMessages([
                    'usuario_externo_id' => 'El usuario seleccionado no pertenece a la empresa actual.',
                ]);
            }

            // 🚫 Un recibo por período (mes ANTERIOR), excluyendo este recibo
            $fr         = Carbon::parse($validated['fecha']);
            $periodoIni = $fr->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $periodoFin = $fr->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
            $periodoStr = Carbon::parse($periodoIni)->format('Y-m');

            $yaExiste = Recibo::where('empresa_local_id', $empresaId)
                ->where('usuario_externo_id', $usuario->id)
                ->where('id', '<>', $recibo->id)
                ->whereRaw('DATE_SUB(fecha, INTERVAL 1 MONTH) BETWEEN ? AND ?', [$periodoIni, $periodoFin])
                ->exists();

            if ($yaExiste) {
                throw ValidationException::withMessages([
                    'fecha' => "Usuario con recibo para el período {$periodoStr}.",
                ]);
            }

            // --- Novedad (NO forzar “Ingreso”) ---
            $nov = $validated['novedad'] ?? null;
            if ($nov === 'Retiro') {
                // Validar rango base-30 + coherencia con afiliación en el mes base
                $this->validarRetiroConAfiliacion($usuario, $validated['fecha'], $validated['fecha_retiro']);
                $novedad     = 'Retiro';
                $fechaRetiro = $validated['fecha_retiro'];
            } elseif ($nov === 'Ingreso') {
                $novedad     = 'Ingreso';
                $fechaRetiro = null;
            } else {
                $novedad     = null;
                $fechaRetiro = null;
            }

            // ✅ Validación adicional: recibo debe estar en el MES SIGUIENTE al mes base
            $this->assertFechaReciboCorrespondeAlMesSiguiente($usuario, $validated['fecha'], $fechaRetiro);

            // ================== SNAPSHOT ARL (nivel, actividad, tarifa) ==================
            $nivelFromAny = static function ($nivel): ?int {
                if ($nivel === null) return null;
                if (is_numeric($nivel)) return max(1, min(5, (int) $nivel));
                if (preg_match('/(\d+)/', (string) $nivel, $m)) {
                    $n = (int) $m[1];
                    return ($n >= 1 && $n <= 5) ? $n : null;
                }
                return null;
            };

            $arlNivelSnap = $nivelFromAny($usuario->arl->nivel ?? $usuario->arl->nivel_riesgo ?? null);

            $mapConfig   = (array) config('arl.actividad_por_nivel', []);
            $mapFallback = [1 => '1711001', 2 => '2741001', 3 => '3432101', 4 => '4466301', 5 => '5432201'];
            $arlActSnap  = $usuario->arl->actividad_economica
                ?? ($arlNivelSnap ? ($mapConfig[$arlNivelSnap] ?? $mapFallback[$arlNivelSnap] ?? null) : null);

            $arlTarifaSnap = null;
            $p             = $usuario->arl->porcentaje ?? null;
            if ($p !== null) {
                $s = str_replace([',', '%', ' '], ['.', '', ''], (string) $p);
                if (is_numeric($s)) $arlTarifaSnap = (float) $s;
            }
            // ============================================================================

            // --- Bases SIEMPRE por BD/efectivo a la fecha (sin override) ---
            $fechaPeriodo = $validated['fecha'];
            $sueldoBase = (float) ($usuario->sueldoEfectivoParaFecha($fechaPeriodo) ?? $usuario->sueldo ?? 0);
            $admonBase  = (float) ($usuario->admonEfectivoParaFecha($fechaPeriodo)  ?? $usuario->admon  ?? 0);

            // Sobrescribir EN MEMORIA para LiquidacionService
            $usuario->setAttribute('sueldo', $sueldoBase);
            $usuario->setAttribute('admon', $admonBase);

            // Cálculo de valores
            $dias     = 0;
            $esRetiro = ($novedad === 'Retiro');
            $valores  = LiquidacionService::calcular(
                $usuario,
                $validated['fecha'],
                $esRetiro ? 'Retiro' : 'Ingreso',
                $fechaRetiro,
                $dias
            );

            $otros = round(($validated['otros_servicios'] ?? 0) / 100) * 100;
            $total = array_sum($valores) + $otros;

            // Actualizar (NO cambiamos el número del recibo)
            $payload = [
                'fecha'              => $validated['fecha'],
                'usuario_externo_id' => $usuario->id,
                'dias_liquidar'      => $dias,
                'valor_eps'          => $valores['valor_eps'],
                'valor_arl'          => $valores['valor_arl'],
                'valor_pension'      => $valores['valor_pension'],
                'valor_caja'         => $valores['valor_caja'],
                'valor_admon'        => $valores['valor_admon'],
                'valor_exequial'     => $valores['valor_exequial'] ?? 0,
                'valor_mora'         => $valores['valor_mora'] ?? 0,
                'otros_servicios'    => $otros,
                'total'              => $total,
                'novedad'            => $novedad,     // ← puede ser null
                'fecha_retiro'       => $fechaRetiro, // ← puede ser null

                // Snapshot ARL
                'arl_nivel'          => $arlNivelSnap,
                'arl_actividad'      => $arlActSnap,
                'arl_tarifa'         => $arlTarifaSnap,
            ];

            // Snapshots de base (si existen columnas)
            if (Schema::hasColumn('recibos', 'sueldo_base')) {
                $payload['sueldo_base'] = $sueldoBase;
            }
            if (Schema::hasColumn('recibos', 'admon_base')) {
                $payload['admon_base'] = $admonBase;
            }

            // Snapshots de nombres (para Excel)
            $payload += [
                'eps_nombre'     => $usuario->eps->nombre ?? null,
                'arl_nombre'     => $usuario->arl->nombre ?? null,
                'pension_nombre' => $usuario->pension->nombre ?? null,
                'caja_nombre'    => $usuario->caja->nombre ?? null,
            ];

            $recibo->fill($payload)->save();

            // Actualiza la marca del período con los datos del recibo editado
            $this->upsertPeriodoUsuario($recibo);

            // Si es Retiro, marcar usuario inactivo (igual que en store)
            if ($esRetiro) {
                UsuarioExterno::whereKey($usuario->id)
                    ->lockForUpdate()
                    ->update([
                        'estado'       => false,
                        'novedad'      => 'Retiro',
                        'fecha_retiro' => $fechaRetiro,
                    ]);
            }

            return redirect()
                ->route('recibos')
                ->with('success', 'Recibo actualizado correctamente' . ($esRetiro ? ' y el usuario fue marcado como Inactivo.' : '.'));
        });
    } catch (InvalidArgumentException $e) {
        return back()->withErrors(['fecha_retiro' => $e->getMessage()])->withInput();
    }
}



    public function destroy($id)
    {
        $empresaId = (int) session('empresa_local_id');

        return DB::transaction(function () use ($id, $empresaId) {
            // Trae el recibo y su usuario, y bloquea fila del recibo para consistencia
            $recibo = Recibo::with('usuarioExterno')
                ->deEmpresa($empresaId)
                ->lockForUpdate()
                ->findOrFail($id);

            $usuarioId = (int) $recibo->usuario_externo_id;
            $esRetiro  = $recibo->novedad === 'Retiro';

            // Eliminar el recibo
            $recibo->delete();

            // Si el recibo eliminado marcaba Retiro, recalcular estado del usuario
            if ($esRetiro) {
                // ¿Quedan otros recibos con novedad Retiro para este usuario?
                $ultimoRetiro = Recibo::where('usuario_externo_id', $usuarioId)
                    ->where('empresa_local_id', $empresaId)
                    ->where('novedad', 'Retiro')
                    ->orderByDesc('fecha_retiro')
                    ->lockForUpdate()
                    ->first();

                if ($ultimoRetiro) {
                    // Aún hay un retiro vigente en el histórico → mantener inactivo con esa fecha
                    UsuarioExterno::whereKey($usuarioId)
                        ->lockForUpdate()
                        ->update([
                            'estado'       => false,
                            'novedad'      => 'Retiro',
                            'fecha_retiro' => $ultimoRetiro->fecha_retiro,
                        ]);
                } else {
                    // Ya no hay retiros → restaurar como antes (Activo, sin fecha de retiro)
                    UsuarioExterno::whereKey($usuarioId)
                        ->lockForUpdate()
                        ->update([
                            'estado'       => true,
                            'novedad'      => 'Ingreso',
                            'fecha_retiro' => null,
                        ]);
                }
            }
            // Recalcula la marca del período para ese usuario/empresa
$this->recalcularPeriodoUsuario($empresaId, $usuarioId, $recibo->fecha, $recibo->id);

            return redirect()
                ->route('recibos') // ajusta si tu nombre de ruta es distinto
                ->with('success', 'Recibo eliminado y estado del usuario restaurado.');
        });
    }

    public function buscarUsuario(Request $request, $numero = null)
    {
        $numero = trim($numero ?? (string) $request->input('numero', ''));

        if ($numero === '') {
            return response()->json(['message' => 'Número no proporcionado'], 400);
        }

        $empresaId = session('empresa_local_id');

        $query = UsuarioExterno::with(['eps', 'arl', 'pension', 'caja'])
            ->whereRaw('TRIM(numero) = ?', [$numero]);

        if (!empty($empresaId)) {
            $query->where('empresa_local_id', $empresaId);
        }

        $usuario = $query->first();

        if (!$usuario) {
            $msg = !empty($empresaId)
                ? 'Usuario no encontrado'
                : 'Usuario no encontrado (verifica que haya una empresa seleccionada).';

            return response()->json(['message' => $msg], 404);
        }

        return response()->json($usuario);
    }

    public function imprimir($id)
    {
        $empresaId = session('empresa_local_id');

        $recibo = Recibo::with([
                'usuarioExterno.eps',
                'usuarioExterno.arl',
                'usuarioExterno.pension',
                'usuarioExterno.caja',
            ])
            ->deEmpresa($empresaId)
            ->findOrFail($id);

        return view('recibos.imprimir', compact('recibo'));
    }

    /** Acepta retiro SOLO si está en el mes anterior al recibo y día 1..30 (base-30). */
    private function validarRetiroMesAnteriorBase30(string $fechaRecibo, string $fechaRetiro): void
    {
        // Forzar formato exacto YYYY-MM-DD (input type="date" manda así)
        $fr   = \Carbon\Carbon::createFromFormat('Y-m-d', $fechaRecibo)->startOfDay();
        $fret = \Carbon\Carbon::createFromFormat('Y-m-d', $fechaRetiro)->startOfDay();

        // Mes anterior al recibo (sin desbordes)
        $prev = $fr->copy()->subMonthNoOverflow();

        // Regla 1: mismo año/mes que el mes anterior
        if ($fret->format('Y-m') !== $prev->format('Y-m')) {
            throw new \InvalidArgumentException('La fecha de retiro debe estar dentro del mes anterior al recibo (1..30).');
        }

        // Regla 2: día 1..30 (base 30)
        $dia = (int) $fret->day;
        if ($dia < 1 || $dia > 30) {
            throw new \InvalidArgumentException('La fecha de retiro debe estar dentro del mes anterior al recibo (1..30).');
        }
    }

    public function exportarPendientes(Request $request)
    {
        $empresaId = (int) session('empresa_local_id');
        abort_if(!$empresaId, 422, 'No hay empresa seleccionada.');

        if (!Schema::hasColumn('recibos', 'export_batch_id')) {
            return back()->with('warning', 'Falta la columna export_batch_id. Ejecuta las migraciones.');
        }

        $codigo = trim((string) $request->input('codigo', '')); // <-- tu código libre (opcional)

        // Crear el lote y marcar recibos en una transacción
        $batch = DB::transaction(function () use ($empresaId, $codigo) {
            // Bloquear ids pendientes
            $ids = Recibo::where('empresa_local_id', $empresaId)
                ->whereNull('export_batch_id')
                ->lockForUpdate()
                ->pluck('id');

            if ($ids->isEmpty()) {
                return null;
            }

            // Agregados
            $agg = Recibo::whereIn('id', $ids)
                ->selectRaw('COUNT(*) c, COALESCE(SUM(total),0) s')
                ->first();

            // Detectar período (si todos los recibos del lote son del mismo mes)
            $months = Recibo::whereIn('id', $ids)
                ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') as ym")
                ->distinct()
                ->pluck('ym');

            $periodo = $months->count() === 1 ? $months->first() : null;

            // Crear lote con tu código (si no lo envías, lo dejamos null)
            $batch = ExportBatch::create([
                'empresa_local_id' => $empresaId,
                'codigo'           => $codigo !== '' ? $codigo : null,
                'periodo'          => $periodo,
                'recibos_count'    => (int) $agg->c,
                'total'            => (float) $agg->s,
            ]);

            // Marcar recibos
            Recibo::whereIn('id', $ids)->update(['export_batch_id' => $batch->id]);

            return $batch;
        });

        if (!$batch) {
            return redirect()->route('recibos')->with('info', 'No hay recibos pendientes para exportar.');
        }

        // YA NO descargo de inmediato: voy al listado de exportaciones
        return redirect()->route('exportaciones.index')
            ->with('success', 'Exportación creada correctamente.');
    }

    public function descargarLote(ExportBatch $batch)
    {
        // Re-generar Excel de un lote ya existente (no toca BD)
        $empresa = \App\Models\EmpresaLocal::with('documento')->findOrFail($batch->empresa_local_id);

        $recibos = \App\Models\Recibo::with([
                'usuarioExterno.documento',
                'usuarioExterno.eps',
                'usuarioExterno.arl',
                'usuarioExterno.pension',
                'usuarioExterno.caja',
                'usuarioExterno.subtipoCotizante',
                'empresaLocal',
            ])
            ->where('export_batch_id', $batch->id)
            ->orderBy('fecha')
            ->get();

        if ($recibos->isEmpty()) {
            return redirect()->route('recibos')->with('warning', 'El lote no tiene recibos.');
        }

        $templatePath = storage_path('app/templates/Libro1.xlsx');
        if (!is_file($templatePath)) {
            return redirect()->route('recibos')->with('warning', 'No se encontró la plantilla Libro1.xlsx en storage/app/templates.');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet       = $spreadsheet->getSheetByName('Liquidaciones');
        if (!$sheet) {
            return redirect()->route('recibos')->with('warning', "La hoja 'Liquidaciones' no existe en la plantilla.");
        }

        // Encabezado
        $docSigla = $empresa->documento->nombre ?? 'NIT';
        $sheet->setCellValue('K1', $empresa->nombre);
        $sheet->setCellValue('K2', "{$docSigla} {$empresa->numero_documento}");
        $sheet->setCellValue('K3', 'SUCURSAL PRINCIPAL: PRINCIPAL');
        $sheet->setCellValue('K4', 'TIPO EMPLEADOR: EMPRESA');
        $sheet->setCellValue('K5', 'PERFIL: NOMINA/TESORERIA');
        $months       = $recibos->map(fn ($r) => \Carbon\Carbon::parse($r->fecha)->format('Y/m'))->unique()->values();
        $periodoTexto = $months->count() === 1 ? $months->first() : 'VARIOS';
        $sheet->setCellValue('K6', 'ÚLTIMO ACCESO: ' . now()->format('Y/m/d H:i:s'));
        $sheet->setCellValue('B9', $periodoTexto);

        // Parámetros
        $tplRow   = 19;
        $startCol = 'A';
        $endCol   = 'CT';
        $fila     = $tplRow;
        $contador = 1;
        $novText  = 'Todos los sistemas (ARL, AFP, CCF, EPS)';

        $inputCols = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I',
            'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S',
            'AX', 'AY', 'AZ', 'BB', 'BI',
            'BK', 'BL', 'BX', 'BO',
            'BV', 'BY', 'BZ', 'BW', 'CC',
            'CD', 'CE', 'CF', 'CG', 'CH',
            'AU',
        ];

        $mapCajaUbicacion   = [
            'comfandi' => ['VALLE', 'CALI'],
            'comfiar'  => ['ARAUCA', 'ARAUCA'],
        ];
        $valUbicacionDefault = ['', ''];

        $toFloat = function ($raw): ?float {
            if ($raw === null) return null;
            $s = trim((string) $raw);
            if ($s === '') return null;
            $s = str_replace(['%', ' '], '', $s);
            $s = str_replace(',', '.', $s);
            return is_numeric($s) ? (float) $s : null;
        };

        $nivelFromAny = function ($nivel): ?int {
            if ($nivel === null) return null;
            if (is_numeric($nivel)) return (int) $nivel;
            if (preg_match('/(\d+)/', (string) $nivel, $m)) return (int) $m[1];
            return null;
        };

        $arlTarifaMap = [
            1 => 0.00522, 2 => 0.01044, 3 => 0.02440, 4 => 0.04350, 5 => 0.06960,
        ];

        $toUpperTrim = function ($s): string {
            $s = is_null($s) ? '' : trim((string) $s);
            return mb_strtoupper($s, 'UTF-8');
        };

        $actividadByNivel = function (?int $nivel): string {
            $map = [1 => '1711001', 2 => '2741001', 3 => '3432101', 4 => '4466301', 5 => '5432201'];
            return $nivel && isset($map[$nivel]) ? $map[$nivel] : '';
        };

        foreach ($recibos as $r) {
            $u = $r->usuarioExterno;
            if (!$u) continue;

            if ($fila > $tplRow) {
                $this->cloneRowFromTemplate($sheet, $tplRow, $fila, $startCol, $endCol, $inputCols);
            }

            // ====== SNAPSHOTS / NOMBRES ======
            $pensionNombre = $r->pension_nombre ?? ($u->pension->nombre ?? null);
            $epsNombre     = $r->eps_nombre ?? ($u->eps->nombre ?? null);
            $arlNombre     = $r->arl_nombre ?? ($u->arl->nombre ?? null);
            $cajaNombreRaw = $r->caja_nombre ?? ($u->caja->nombre ?? null);

            $cajaNombreCE = $toUpperTrim($cajaNombreRaw);
            $cajaKey      = strtolower($cajaNombreCE);

            $arlNivel   = $r->arl_nivel_riesgo ?? ($u->arl->nivel_riesgo ?? null);
            $arlPorcRaw = $r->arl_tarifa ?? ($u->arl->porcentaje ?? null);
            $arlTarifa  = null;

            if (($flt = $toFloat($arlPorcRaw)) !== null) {
                $arlTarifa = $flt / 100.0;
            } else {
                $arlNivelInt = $nivelFromAny($arlNivel);
                if ($arlNivelInt && isset($arlTarifaMap[$arlNivelInt])) {
                    $arlTarifa = $arlTarifaMap[$arlNivelInt];
                }
            }

            [$depto, $ciudad] = $mapCajaUbicacion[$cajaKey] ?? $valUbicacionDefault;

            // ====== BASES / IBC ======
            $dias       = (int) ($r->dias_liquidar ?? 0);
            // Usar snapshot si existe; si no, el sueldo actual del usuario
            $salarioMes = (float) ($r->sueldo_base ?? $u->sueldo ?? 0);
            $ibc        = round($salarioMes * ($dias / 30), 2);
            $horas      = $dias * 8;

            // ====== NOVEDADES (P y R) — FIX ======
            $novRawDb = (string) $r->getRawOriginal('novedad');
            $ingCampo = ($novRawDb === 'INGRESO') ? $novText : 'NO';
            $retCampo = ($novRawDb === 'RETIRO') ? $novText : 'NO';

            // Identificación
            $sheet->setCellValue("A{$fila}", $contador);
            $sheet->setCellValue("B{$fila}", $u->documento->nombre ?? 'CC');
            $sheet->setCellValueExplicit("C{$fila}", (string) $u->numero, DataType::TYPE_STRING);
            $sheet->setCellValue("D{$fila}", $u->primer_apellido);
            $sheet->setCellValue("E{$fila}", $u->segundo_apellido);
            $sheet->setCellValue("F{$fila}", $u->primer_nombre);
            $sheet->setCellValue("G{$fila}", $u->segundo_nombre);
            $sheet->setCellValue("H{$fila}", $depto);
            $sheet->setCellValue("I{$fila}", $ciudad);
            $sheet->setCellValue("J{$fila}", '1. DEPENDIENTE');
            $sheet->setCellValue("K{$fila}", $u->subtipoCotizante->nombre ?? '');
            $sheet->setCellValue("L{$fila}", $horas);
            $sheet->setCellValue("M{$fila}", 'NO');
            $sheet->setCellValue("N{$fila}", 'NO');
            $sheet->setCellValue("O{$fila}", '');
            $sheet->setCellValue("P{$fila}", $ingCampo); // <<< P
            $sheet->setCellValue("Q{$fila}", '');
            $sheet->setCellValue("R{$fila}", $retCampo); // <<< R
            $sheet->setCellValue("S{$fila}", '');

            // ====== ESCRITURAS IBC EN SISTEMAS ======
            // Pensión
            $sheet->setCellValue("AX{$fila}", $pensionNombre);
            $sheet->setCellValue("AY{$fila}", $dias);
            $sheet->setCellValue("AZ{$fila}", $ibc);
            $sheet->setCellValue("BB{$fila}", '');
            $sheet->setCellValue("BI{$fila}", '');

            // === OVERRIDE SI NO HAY FONDO (AX="NINGUNA") ===
            if (mb_strtoupper(trim((string) $pensionNombre), 'UTF-8') === 'NINGUNA') {
                $sheet->setCellValue("AY{$fila}", 0);
                $sheet->setCellValue("AZ{$fila}", 0);
                $sheet->setCellValue("BA{$fila}", 0);
                $sheet->getStyle("BA{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                $sheet->setCellValue("BB{$fila}", 0);
                $sheet->setCellValue("BI{$fila}", 0);
            }

            // EPS
            $sheet->setCellValue("BK{$fila}", $epsNombre);
            $sheet->setCellValue("BL{$fila}", $dias);
            $sheet->setCellValue("BX{$fila}", $ibc);
            $sheet->setCellValue("BO{$fila}", '');

            // ARL
            $sheet->setCellValue("BV{$fila}", $arlNombre);
            $sheet->setCellValue("BW{$fila}", $dias);
            $sheet->setCellValue("BX{$fila}", $ibc);
            if (($niv = $nivelFromAny($arlNivel)) !== null) {
                $sheet->setCellValue("BZ{$fila}", $niv);
            }
            if ($arlTarifa !== null) {
                $sheet->setCellValue("BY{$fila}", (float) $arlTarifa);
                $sheet->getStyle("BY{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
            } else {
                $sheet->setCellValue("BY{$fila}", '');
            }
            $sheet->setCellValue("CB{$fila}", $actividadByNivel($nivelFromAny($arlNivel)));
            $sheet->setCellValue("CC{$fila}", '');

            // CAJA
            $sheet->setCellValue("CE{$fila}", $cajaNombreCE);
            $ibcCcf = ($cajaNombreCE === 'COMFIAR') ? 1000.0 : $ibc;
            $sheet->setCellValue("CF{$fila}", $ibcCcf);
            $sheet->setCellValue("CD{$fila}", $dias);
            $sheet->setCellValue("CG{$fila}", 0.04);
            $sheet->getStyle("CG{$fila}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
            $sheet->setCellValue("CH{$fila}", '');

            // Otros (salario base mostrado)
            $sheet->setCellValue("AU{$fila}", $salarioMes);

            $contador++;
            $fila++;
        }

        $filename = sprintf('Recibos_Lote_%d.xlsx', $batch->id);
        $writer   = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Clona la fila plantilla (igual a tu helper actual) */
    private function cloneRowFromTemplate(
        Worksheet $sheet,
        int $fromRow,
        int $toRow,
        string $startCol = 'A',
        string $endCol = 'CT',
        array $skipCols = []
    ): void {
        $start = Coordinate::columnIndexFromString($startCol);
        $end   = Coordinate::columnIndexFromString($endCol);

        for ($c = $start; $c <= $end; $c++) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            if (in_array($colLetter, $skipCols, true)) {
                continue;
            }

            $srcAddr = $colLetter . $fromRow;
            $dstAddr = $colLetter . $toRow;

            $srcCell = $sheet->getCell($srcAddr);
            $sheet->setCellValue($dstAddr, $srcCell->getValue());

            $sheet->duplicateStyle($sheet->getStyle($srcAddr), $dstAddr);

            $srcDv = $sheet->getCell($srcAddr)->getDataValidation();
            if ($srcDv && $srcDv->getType() !== '') {
                $dstDv = clone $srcDv;
                $sheet->getCell($dstAddr)->setDataValidation($dstDv);
            }
        }
    }

    // app/Http/Controllers/ReciboController.php (añade al final de la clase)
private function yM(string|\Carbon\Carbon $fecha): string {
    $f = $fecha instanceof \Carbon\Carbon ? $fecha : \Carbon\Carbon::parse($fecha);
    return $f->format('Y-m');
}

/** El “siguiente período” del recibo es Y-m de su fecha (ya que liquidas el mes anterior). */
/** El período "activo" es SIEMPRE el mes SIGUIENTE a la fecha del recibo */
private function periodoSiguienteDeRecibo(string $fechaRecibo): string
{
    return \Carbon\Carbon::parse($fechaRecibo)
        ->addMonthNoOverflow()
        ->format('Y-m');
}


/** Aplica marca de período según la novedad del recibo. */
private function upsertPeriodoUsuario(\App\Models\Recibo $recibo): void {
    $periodo = $this->periodoSiguienteDeRecibo($recibo->fecha);
    $estado  = ($recibo->novedad === 'Retiro') ? 'Retirado' : 'Activo';

    \App\Models\PeriodoUsuario::updateOrCreate(
        [
            'empresa_local_id'   => $recibo->empresa_local_id,
            'usuario_externo_id' => $recibo->usuario_externo_id,
            'periodo'            => $periodo,
        ],
        [
            'estado'    => $estado,
            'recibo_id' => $recibo->id,
        ]
    );
}

/**
 * Si un recibo se edita o elimina, hay que recalcular la marca del período:
 * - Busca si existe OTRO recibo del mismo usuario/empresa con la MISMA "fecha->Y-m" (siguiente período),
 *   elige el más reciente y deriva su estado; si no hay ninguno, elimina la marca.
 */
private function recalcularPeriodoUsuario(int $empresaId, int $usuarioId, string $fechaRecibo, ?int $ignorarReciboId = null): void {
    $periodo = $this->periodoSiguienteDeRecibo($fechaRecibo);

    $otro = \App\Models\Recibo::where('empresa_local_id', $empresaId)
        ->where('usuario_externo_id', $usuarioId)
        ->when($ignorarReciboId, fn($q)=>$q->where('id','<>',$ignorarReciboId))
        ->whereRaw("DATE_FORMAT(fecha, '%Y-%m') = ?", [$periodo]) // mismo período siguiente
        ->orderByDesc('fecha')
        ->first();

    if ($otro) {
        $estado = ($otro->novedad === 'Retiro') ? 'Retirado' : 'Activo';
        \App\Models\PeriodoUsuario::updateOrCreate(
            [
                'empresa_local_id'   => $empresaId,
                'usuario_externo_id' => $usuarioId,
                'periodo'            => $periodo,
            ],
            [
                'estado'    => $estado,
                'recibo_id' => $otro->id,
            ]
        );
    } else {
        \App\Models\PeriodoUsuario::where([
            'empresa_local_id'   => $empresaId,
            'usuario_externo_id' => $usuarioId,
            'periodo'            => $periodo,
        ])->delete();
    }
}
















public function retirosMasivosForm(Request $request)
{
    $empresaId = (int)($request->input('empresa_local_id') ?: session('empresa_local_id'));
    $periodo   = $request->input('periodo') ?: now()->format('Y-m');

    // Cuántos candidatos hay hoy (estimación)
    $candidatos = $this->usuariosPendientesDeReciboQuery($empresaId, $periodo)->count();

    $empresas = \App\Models\EmpresaLocal::orderBy('nombre')->get();

    return view('recibos.retiros_masivos', compact('empresaId','empresas','periodo','candidatos'));
}

/**
 * Genera Excel de retiros 1 día para TODOS los usuarios sin recibo del período
 * y los marca inactivos (novedad=Retiro, fecha_retiro=día 1 del mes base).
 */
public function retirosMasivosExport(Request $request)
{
    $data = $request->validate([
        'periodo'          => 'required|date_format:Y-m',
        'empresa_local_id' => 'nullable|exists:empresa_local,id',
        'confirm'          => 'nullable|in:1',
    ]);

    $empresaId = (int)($data['empresa_local_id'] ?? session('empresa_local_id'));
    abort_if(!$empresaId, 422, 'No hay empresa seleccionada.');

    // Período y mes base
    $periodo      = $data['periodo'];
    $anchor       = \Carbon\Carbon::createFromFormat('Y-m-d', "{$periodo}-01");
    $baseIni      = $anchor->copy()->subMonthNoOverflow()->startOfMonth();
    $baseFin      = $anchor->copy()->subMonthNoOverflow()->endOfMonth();
    $fechaRetiro  = $baseIni->copy()->day(1)->toDateString();

    // Usuarios pendientes
    $usuarios = $this->usuariosPendientesDeReciboQuery($empresaId, $periodo)
        ->with(['documento','eps','arl','pension','caja','subtipoCotizante'])
        ->orderBy('id')
        ->get();

    if ($usuarios->isEmpty()) {
        return back()->with('info', 'No hay usuarios pendientes de recibo para ese período.');
    }

    // Plantilla Excel
    $empresa  = \App\Models\EmpresaLocal::with('documento')->findOrFail($empresaId);
    $tplPath  = storage_path('app/templates/Libro1.xlsx');
    if (!is_file($tplPath)) {
        return back()->with('warning', 'No se encontró la plantilla Libro1.xlsx en storage/app/templates.');
    }

    $spreadsheet = IOFactory::load($tplPath);
    $sheet       = $spreadsheet->getSheetByName('Liquidaciones');
    if (!$sheet) return back()->with('warning', "La hoja 'Liquidaciones' no existe en la plantilla.");

    // Encabezado
    $docSigla = $empresa->documento->nombre ?? 'NIT';
    $sheet->setCellValue('K1', $empresa->nombre);
    $sheet->setCellValue('K2', "{$docSigla} {$empresa->numero_documento}");
    $sheet->setCellValue('K6', 'ÚLTIMO ACCESO: ' . now()->format('Y/m/d H:i:s'));
    $sheet->setCellValue('B9', $anchor->format('Y/m'));

    // === Helpers (ponlos aquí) ===
    $roundUp100 = static function (float $v): float {
        return $v <= 0 ? 0.0 : (float) (ceil($v / 100) * 100);
    };
    $normKey = static function (?string $s): string {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $s = preg_replace('/\s+/u', '', $s);
        $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
        return preg_replace('/\pM/u', '', $s);
    };

    // Mapeo Caja → ubicación
    $mapCajaUbicacion = [
        'comfandi'            => ['VALLE', 'CALI'],
        'comfiar'             => ['ARAUCA', 'ARAUCA'],
        'comfenalcoantioquia' => ['ANTIOQUIA', 'MEDELLÍN'],
        'compensar'           => ['BOGOTÁ D.C.', 'BOGOTÁ'],
    ];

    // Parámetros
    $tplRow    = 19;
    $startCol  = 'A';
    $endCol    = 'CT';
    $fila      = $tplRow;
    $contador  = 1;
    $novText   = 'Todos los sistemas (ARL, AFP, CCF, EPS)';

    // Columnas excluidas de clonado
    $inputCols = [/* tu lista larga de columnas excluidas */];

    // === Loop ===
    foreach ($usuarios as $u) {
        if ($fila > $tplRow) {
            $this->cloneRowFromTemplate($sheet, $tplRow, $fila, $startCol, $endCol, $inputCols);
        }

        $dias       = 1;
        $salarioMes = (float) ($u->sueldoEfectivoParaFecha($baseIni->toDateString()) ?? $u->sueldo ?? 0);
        $ibc        = round($salarioMes * ($dias / 30), 2);
        $horas      = $dias * 8;

        // Ubicación segun Caja
        $cajaNombre      = $u->caja->nombre ?? '';
        $k               = $normKey($cajaNombre);
        [$depto, $ciudad]= $mapCajaUbicacion[$k] ?? ['', ''];

        // ARL
        $arlNombre  = $u->arl->nombre ?? 'NINGUNA';
        $arlNivel   = null;
        if (($u->arl->nivel ?? null) && preg_match('/\d+/', (string)$u->arl->nivel, $m)) {
            $arlNivel = max(1, min(5, (int)$m[0]));
        }
        $arlTarifa = null;
        if (($u->arl->porcentaje ?? null) !== null) {
            $s = str_replace([',','%',' '], ['.','',''], (string)$u->arl->porcentaje);
            if (is_numeric($s)) $arlTarifa = ((float)$s) / 100.0;
        }

        $pensionNombre = $u->pension->nombre ?? 'NINGUNA';
        $epsNombre     = $u->eps->nombre ?? 'NINGUNA';
        $cajaNombreUp  = $cajaNombre ? mb_strtoupper($cajaNombre, 'UTF-8') : 'NINGUNA';

        // ======= Escritura =======
        $sheet->setCellValue("A{$fila}", $contador);
        $sheet->setCellValue("B{$fila}", $u->documento->nombre ?? 'CC');
        $sheet->setCellValueExplicit("C{$fila}", (string)$u->numero, DataType::TYPE_STRING);
        $sheet->setCellValue("D{$fila}", trim((string)$u->primer_apellido));
        $sheet->setCellValue("E{$fila}", trim((string)$u->segundo_apellido));
        $sheet->setCellValue("F{$fila}", trim((string)$u->primer_nombre));
        $sheet->setCellValue("G{$fila}", trim((string)$u->segundo_nombre));
        $sheet->setCellValue("H{$fila}", $depto);
        $sheet->setCellValue("I{$fila}", $ciudad);
        $sheet->setCellValue("J{$fila}", '1. DEPENDIENTE');
        $subNombre = trim((string)($u->subtipoCotizante->nombre ?? ''));
        $sheet->setCellValue("K{$fila}", $subNombre !== '' ? $subNombre : 'NINGUNO');
        $sheet->setCellValue("L{$fila}", $horas);
        $sheet->setCellValue("M{$fila}", 'NO');
        $sheet->setCellValue("N{$fila}", 'NO');
        $sheet->setCellValue("P{$fila}", 'NO');
        $sheet->setCellValue("R{$fila}", $novText);

        // === Pensión ===
        $sheet->setCellValue("AX{$fila}", $pensionNombre);
        $sheet->setCellValue("AY{$fila}", $dias);
        $sheet->setCellValue("AZ{$fila}", $ibc);
        $sheet->setCellValue("BA{$fila}", 0.16);
        $sheet->getStyle("BA{$fila}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        $valorPension = $roundUp100($ibc * 0.16);
        $sheet->setCellValue("BB{$fila}", $valorPension);
        $sheet->setCellValue("BI{$fila}", $valorPension);

        if (mb_strtoupper(trim((string)$pensionNombre), 'UTF-8') === 'NINGUNA') {
            $sheet->setCellValue("AY{$fila}", 0);
            $sheet->setCellValue("AZ{$fila}", 0);
            $sheet->setCellValue("BA{$fila}", 0);
            $sheet->setCellValue("BB{$fila}", 0);
            $sheet->setCellValue("BI{$fila}", 0);
        }

        // === EPS ===
        $sheet->setCellValue("BK{$fila}", $epsNombre);
        $sheet->setCellValue("BL{$fila}", $dias);
        $sheet->setCellValue("BM{$fila}", $ibc);
        $sheet->setCellValue("BN{$fila}", 0.04);
        $sheet->getStyle("BN{$fila}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        $sheet->setCellValue("BO{$fila}", $roundUp100($ibc * 0.04));

        // === ARL ===
        $sheet->setCellValue("BV{$fila}", $arlNombre);
        $sheet->setCellValue("BW{$fila}", $dias);
        $sheet->setCellValue("BX{$fila}", $ibc);
        if ($arlTarifa !== null) {
            $sheet->setCellValue("BY{$fila}", (float)$arlTarifa);
            $sheet->getStyle("BY{$fila}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        }
        $sheet->setCellValue("BZ{$fila}", $arlNivel ?: '');
        $sheet->setCellValueExplicit("CB{$fila}", (string)($u->arl->actividad_economica ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValue("CC{$fila}", $roundUp100($ibc * (float)($arlTarifa ?? 0)));

        // === CCF ===
        $sheet->setCellValue("CD{$fila}", $dias);
        $sheet->setCellValueExplicit("CE{$fila}", $cajaNombreUp, DataType::TYPE_STRING);
        $sheet->setCellValue("CF{$fila}", $ibc);
        $sheet->setCellValue("CG{$fila}", 0.04);
        $sheet->getStyle("CG{$fila}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
        $sheet->setCellValue("CH{$fila}", $roundUp100($ibc * 0.04));

        $sheet->setCellValue("CR{$fila}", 'SI');
        $sheet->setCellValue("AU{$fila}", $salarioMes);

        $fila++;
        $contador++;
    }

    $filename = sprintf('RetirosMasivos_%s_%s.xlsx', $empresa->id, $periodo);
    $writer   = IOFactory::createWriter($spreadsheet, 'Xlsx');

    // Actualizar BD
    DB::transaction(function () use ($usuarios, $empresaId, $periodo, $fechaRetiro) {
        foreach ($usuarios as $u) {
            $u->update([
                'estado'       => false,
                'novedad'      => 'Retiro',
                'fecha_retiro' => $fechaRetiro,
            ]);
            PeriodoUsuario::updateOrCreate(
                ['empresa_local_id' => $empresaId, 'usuario_externo_id' => $u->id, 'periodo' => $periodo],
                ['estado' => 'Retirado', 'recibo_id' => null]
            );
        }
    });

    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}

/**
 * Query base: usuarios que NO tienen recibo en el período Y-m.
 * Reutiliza la lógica de "pendientes".
 */
private function usuariosPendientesDeReciboQuery(int $empresaId, string $periodo)
{
    $anchor    = \Carbon\Carbon::createFromFormat('Y-m-d', "{$periodo}-01");
    $prevStart = $anchor->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
    $prevEnd   = $anchor->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

    $idsA = \App\Models\PeriodoUsuario::query()
        ->where('empresa_local_id', $empresaId)
        ->where('periodo', $periodo)
        ->where('estado', 'Activo')
        ->pluck('usuario_externo_id');

    $idsB = \App\Models\UsuarioExterno::query()
        ->where('empresa_local_id', $empresaId)
        ->whereDate('fecha_afiliacion', '<=', $prevEnd)
        ->where(function ($q) use ($prevStart) {
            $q->whereNull('fecha_retiro')
              ->orWhereDate('fecha_retiro', '>=', $prevStart);
        })
        ->pluck('id');

    $ids = $idsA->merge($idsB)->unique();

    return \App\Models\UsuarioExterno::query()
        ->where('empresa_local_id', $empresaId)
        ->whereIn('id', $ids)
        ->whereNotExists(function ($sub) use ($periodo) {
            $sub->selectRaw(1)
                ->from('recibos as r')
                ->whereColumn('r.empresa_local_id', 'usuario_externos.empresa_local_id')
                ->whereColumn('r.usuario_externo_id', 'usuario_externos.id')
                ->whereRaw("DATE_FORMAT(r.fecha, '%Y-%m') = ?", [$periodo]);
        });
}

    private function assertFechaReciboCorrespondeAlMesSiguiente(UsuarioExterno $u, string $fechaRecibo, ?string $fechaRetiro = null): void
    {
    $fr       = Carbon::parse($fechaRecibo);
    $baseIni  = $fr->copy()->subMonthNoOverflow()->startOfMonth();
    $baseFin  = $fr->copy()->subMonthNoOverflow()->endOfMonth();

    $af = $u->fecha_afiliacion instanceof Carbon ? $u->fecha_afiliacion : Carbon::parse($u->fecha_afiliacion);
    $rt = $fechaRetiro ? Carbon::parse($fechaRetiro) : null;

    // Si afiliación y/o retiro están en el mismo mes (YYYY-MM), exige que la fecha del recibo sea el MES SIGUIENTE
    $mesAf = $af->format('Y-m');
    $mesRt = $rt ? $rt->format('Y-m') : null;
    $mesRecibo = $fr->format('Y-m');
    $mesEsperado = $fr->copy()->subMonthNoOverflow()->addMonthNoOverflow()->format('Y-m'); // = $fr->format('Y-m') (solo para claridad)

    // Si el evento (afiliación o retiro) cae DENTRO del mes base (baseIni..baseFin) pero la fecha del recibo también está en ese mismo mes,
    // entonces están intentando liquidar el mismo mes, no el anterior → error guía.
    $eventoEnBase = $af->betweenIncluded($baseIni, $baseFin) || ($rt && $rt->betweenIncluded($baseIni, $baseFin));

    if ($eventoEnBase && $mesRecibo === $baseIni->format('Y-m')) {
        throw ValidationException::withMessages([
            'fecha' => "Para liquidar {$baseIni->format('Y-m')} la fecha del recibo debe estar en {$baseIni->copy()->addMonthNoOverflow()->format('Y-m')} (el mes siguiente).",
        ]);
    }
    }
    /**
 * Verifica que la fecha de retiro esté en el MES ANTERIOR al recibo (1..30)
 * y que NO sea anterior a la fecha de afiliación cuando esta cae dentro del mes base.
 */
private function validarRetiroConAfiliacion(
    \App\Models\UsuarioExterno $u,
    string $fechaRecibo,
    string $fechaRetiro
): void {
    // 1) Tu validación existente de rango base-30
    $this->validarRetiroMesAnteriorBase30($fechaRecibo, $fechaRetiro);

    // 2) No permitir retiro < afiliación si la afiliación cae dentro del mes base
    $fr      = \Carbon\Carbon::parse($fechaRecibo);
    $baseIni = $fr->copy()->subMonthNoOverflow()->startOfMonth();
    $baseFin = $fr->copy()->subMonthNoOverflow()->endOfMonth();

    $af  = $u->fecha_afiliacion instanceof \Carbon\Carbon ? $u->fecha_afiliacion : \Carbon\Carbon::parse($u->fecha_afiliacion);
    $ret = \Carbon\Carbon::parse($fechaRetiro);

    if ($af->betweenIncluded($baseIni, $baseFin) && $ret->lt($af)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'fecha_retiro' => "La fecha de retiro ({$ret->toDateString()}) no puede ser anterior a la fecha de afiliación ({$af->toDateString()}) en el mes a liquidar.",
        ]);
    }
}



}
