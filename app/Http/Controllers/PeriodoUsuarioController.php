<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\{PeriodoUsuario, UsuarioExterno, EmpresaLocal};
use Carbon\Carbon;

class PeriodoUsuarioController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = (int) ($request->input('empresa_local_id') ?: session('empresa_local_id'));
        $periodo   = $request->input('periodo') ?: now()->addMonthNoOverflow()->format('Y-m');
        $estado    = $request->input('estado');

        // 👇 NUEVO: asegura marcas "Activo" para el período consultado
        if ($empresaId && $periodo) {
            $this->syncActivosDeAfiliaciones($empresaId, $periodo);
        }

        // Paginación
        $allowed  = [10, 20, 50, 100];
        $perPage  = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowed, true)) { $perPage = 20; }

        $query = PeriodoUsuario::with(['usuarioExterno','empresaLocal'])
            ->when($empresaId, fn($q)=>$q->deEmpresa($empresaId))
            ->periodo($periodo)
            ->when($estado, fn($q)=>$q->where('estado', $estado))
            ->orderBy('estado')
            ->orderByDesc('id');

        $items    = $query->paginate($perPage)->appends($request->query());
        $empresas = EmpresaLocal::orderBy('nombre')->get();

        return view('periodos.index', compact('items','empresas','empresaId','periodo','estado','perPage'));
    }

    public function pendientes(Request $request)
    {
        $empresaId = (int)($request->input('empresa_local_id') ?: session('empresa_local_id'));
        $periodo   = $request->input('periodo') ?: now()->format('Y-m');

        // Paginación
        $allowed  = [10, 20, 50, 100];
        $perPage  = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowed, true)) { $perPage = 20; }

        $anchor    = Carbon::createFromFormat('Y-m-d', "{$periodo}-01");
        $prevStart = $anchor->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $prevEnd   = $anchor->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $idsA = PeriodoUsuario::query()
            ->when($empresaId, fn($q)=>$q->where('empresa_local_id', $empresaId))
            ->where('periodo', $periodo)
            ->where('estado', 'Activo')
            ->pluck('usuario_externo_id');

        $idsB = UsuarioExterno::query()
            ->when($empresaId, fn($q)=>$q->where('empresa_local_id', $empresaId))
            ->whereDate('fecha_afiliacion', '<=', $prevEnd)
            ->where(function ($q) use ($prevStart) {
                $q->whereNull('fecha_retiro')->orWhereDate('fecha_retiro', '>=', $prevStart);
            })
            ->pluck('id');

        $idsCandidatos = $idsA->merge($idsB)->unique()->values();

        $items = UsuarioExterno::with(['empresaLocal'])
            ->whereIn('id', $idsCandidatos)
            ->whereNotExists(function ($sub) use ($periodo) {
                $sub->selectRaw(1)->from('recibos as r')
                    ->whereColumn('r.empresa_local_id', 'usuario_externos.empresa_local_id')
                    ->whereColumn('r.usuario_externo_id', 'usuario_externos.id')
                    ->whereRaw("DATE_FORMAT(r.fecha, '%Y-%m') = ?", [$periodo]);
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query());

        $empresas = EmpresaLocal::orderBy('nombre')->get();

        return view('periodos.pendientes', compact('items','empresas','empresaId','periodo','perPage'));
    }

    /**
     * Crea la marca Activo en periodo_usuarios para quienes:
     * - se afiliaron en el mes base (período - 1 mes),
     * - no tienen marca aún en ese período,
     * - y no están retirados antes del inicio del mes base.
     *
     * Ej.: si $periodoYm = '2025-09', el mes base es '2025-08'.
     */
    private function syncActivosDeAfiliaciones(int $empresaId, string $periodoYm): void
    {
        // Calcular mes base (período - 1)
        $anchor  = Carbon::createFromFormat('Y-m-d', "{$periodoYm}-01");
        $baseIni = $anchor->copy()->subMonthNoOverflow()->startOfMonth();
        $baseFin = $anchor->copy()->subMonthNoOverflow()->endOfMonth();

        // Usuarios afiliados dentro del mes base, no retirados antes del inicio del mes base,
        // y que no tengan ya la marca en periodo_usuarios para $periodoYm
        $usuarios = UsuarioExterno::query()
            ->where('empresa_local_id', $empresaId)
            ->whereBetween('fecha_afiliacion', [$baseIni->toDateString(), $baseFin->toDateString()])
            ->where(function($q) use ($baseIni){
                $q->whereNull('fecha_retiro')
                  ->orWhereDate('fecha_retiro', '>=', $baseIni->toDateString());
            })
            ->whereNotExists(function ($sub) use ($empresaId, $periodoYm) {
                $sub->selectRaw(1)
                    ->from('periodo_usuarios as pu')
                    ->whereColumn('pu.usuario_externo_id', 'usuario_externos.id')
                    ->where('pu.empresa_local_id', $empresaId)
                    ->where('pu.periodo', $periodoYm);
            })
            ->get(['id','empresa_local_id']);

        if ($usuarios->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($usuarios, $empresaId, $periodoYm) {
            foreach ($usuarios as $u) {
                PeriodoUsuario::updateOrCreate(
                    [
                        'empresa_local_id'   => $empresaId,
                        'usuario_externo_id' => $u->id,
                        'periodo'            => $periodoYm,
                    ],
                    [
                        'estado'    => 'Activo',
                        'recibo_id' => null,
                    ]
                );
            }
        });
    }
}
