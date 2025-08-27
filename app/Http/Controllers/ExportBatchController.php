<?php

namespace App\Http\Controllers;

use App\Models\ExportBatch;
use Illuminate\Http\Request;

class ExportBatchController extends Controller
{
    public function index()
    {
        $empresaId = (int) session('empresa_local_id');

        $batches = ExportBatch::with('empresaLocal')
            ->where('empresa_local_id', $empresaId)
            ->orderByDesc('created_at')
            ->paginate(20);

        // Agrupar por período (null => 'VARIOS') para pintar secciones
        $grouped = $batches->getCollection()->groupBy(fn($b) => $b->periodo ?: 'VARIOS');

        return view('exportaciones.index', [
            'batches'  => $batches, // para paginar
            'grouped'  => $grouped, // para render por grupos
            'empresaId'=> $empresaId,
        ]);
    }
}
