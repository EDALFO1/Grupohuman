<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;


use App\Models\{PeriodoUsuario, UsuarioExterno, EmpresaLocal};
use Carbon\Carbon;
use Illuminate\Http\Request;

class PeriodoUsuarioController extends Controller
{
   public function index(Request $request)
    {
        $empresaId = (int) ($request->input('empresa_local_id') ?: session('empresa_local_id'));
        $periodo   = $request->input('periodo') ?: now()->addMonthNoOverflow()->format('Y-m');
        $estado    = $request->input('estado');

        // NUEVO: per_page
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
    // app/Http/Controllers/PeriodoUsuarioController.php

     public function pendientes(Request $request)
    {
        $empresaId = (int)($request->input('empresa_local_id') ?: session('empresa_local_id'));
        $periodo   = $request->input('periodo') ?: now()->format('Y-m');

        // NUEVO: per_page
        $allowed  = [10, 20, 50, 100];
        $perPage  = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowed, true)) { $perPage = 20; }

        $anchor    = \Carbon\Carbon::createFromFormat('Y-m-d', "{$periodo}-01");
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



}

