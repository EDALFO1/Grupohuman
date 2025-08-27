<?php

// app/Http/Controllers/PeriodoUsuarioController.php
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
    $empresaId = (int) ($request->input('empresa_local_id') ?: session('empresa_local_id'));    // si no viene ?periodo=..., usar el SIGUIENTE mes
    $periodo   = $request->input('periodo') ?: now()->addMonthNoOverflow()->format('Y-m');           
    $estado    = $request->input('estado'); // 'Activo' | 'Retirado' | null

    $query = PeriodoUsuario::with(['usuarioExterno','empresaLocal'])
        ->when($empresaId, fn($q)=>$q->deEmpresa($empresaId))
        ->periodo($periodo)
        ->when($estado, fn($q)=>$q->where('estado', $estado))
        ->orderBy('estado')
        ->orderByDesc('id');

    $items    = $query->paginate(20)->appends($request->query());
    $empresas = EmpresaLocal::orderBy('nombre')->get();

    return view('periodos.index', compact('items','empresas','empresaId','periodo','estado'));
    }

    // app/Http/Controllers/PeriodoUsuarioController.php

    public function pendientes(Request $request)
{
    $empresaId = (int)($request->input('empresa_local_id') ?: session('empresa_local_id'));
    $periodo   = $request->input('periodo') ?: now()->format('Y-m'); // YYYY-MM

    // Rango del MES ANTERIOR al que se liquida (para inferir activos)
    // Ej: periodo=2025-09 ⇒ prevStart=2025-08-01, prevEnd=2025-08-31
    $anchor    = Carbon::createFromFormat('Y-m-d', "{$periodo}-01");
    $prevStart = $anchor->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
    $prevEnd   = $anchor->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

    // A) IDs desde periodo_usuarios
    $idsA = PeriodoUsuario::query()
        ->when($empresaId, fn($q)=>$q->where('empresa_local_id', $empresaId))
        ->where('periodo', $periodo)
        ->where('estado', 'Activo')
        ->pluck('usuario_externo_id');

    // B) IDs inferidos por fechas en usuario_externos
    //    (afiliado hasta fin del mes anterior y sin retiro previo a ese mes)
    $idsB = UsuarioExterno::query()
        ->when($empresaId, fn($q)=>$q->where('empresa_local_id', $empresaId))
        ->whereDate('fecha_afiliacion', '<=', $prevEnd)
        ->where(function ($q) use ($prevStart) {
            $q->whereNull('fecha_retiro')
              ->orWhereDate('fecha_retiro', '>=', $prevStart);
        })
        ->pluck('id');

    $idsCandidatos = $idsA->merge($idsB)->unique()->values();

    // Listado final: usuarios candidatos SIN recibo en ese Y-m
    $items = UsuarioExterno::with(['empresaLocal'])
        ->whereIn('id', $idsCandidatos)
        ->whereNotExists(function ($sub) use ($periodo) {
            $sub->selectRaw(1)
                ->from('recibos as r')
                ->whereColumn('r.empresa_local_id', 'usuario_externos.empresa_local_id')
                ->whereColumn('r.usuario_externo_id', 'usuario_externos.id')
                ->whereRaw("DATE_FORMAT(r.fecha, '%Y-%m') = ?", [$periodo]);
        })
        ->orderByDesc('id')
        ->paginate(20)
        ->appends($request->query());

    $empresas = EmpresaLocal::orderBy('nombre')->get();

    return view('periodos.pendientes', compact('items','empresas','empresaId','periodo'));
    }



}

