<?php

namespace App\Http\Controllers;

use App\Models\EmpresaLocal;
use App\Models\ExportBatch;
use App\Models\Recibo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\Response;


use ZipArchive;
use Illuminate\Support\Str;

class LiquidacionesExcelController extends Controller
{
    public function descargar(Request $request): Response
    {
        $request->validate([
            'empresa_local_id' => ['required', 'integer', 'exists:empresa_local,id'],
            'periodo'          => ['required', 'regex:/^\d{4}-\d{2}($|-\d{2}$)/'],
        ]);

        $empresa = EmpresaLocal::with('documento')
            ->findOrFail((int) $request->empresa_local_id);

        $dt = strlen($request->periodo) === 7
            ? Carbon::createFromFormat('Y-m', $request->periodo)->startOfMonth()
            : Carbon::parse($request->periodo)->startOfMonth();

        $inicio = $dt->copy()->startOfMonth();
        $fin    = $dt->copy()->endOfMonth();

        $recibos = Recibo::with([
                'usuarioExterno.documento',
                'usuarioExterno.eps',
                'usuarioExterno.arl',
                'usuarioExterno.pension',
                'usuarioExterno.caja',
                'usuarioExterno.subtipoCotizante',
            ])
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->where('empresa_local_id', $empresa->id)
            ->orderBy('fecha')
            ->get();

        if ($recibos->isEmpty()) {
            return back()->with('warning', "No hay recibos para {$empresa->nombre} en el periodo {$request->periodo}.");
        }

        $spreadsheet = $this->loadTemplate();
        $sheet       = $this->getLiquidacionesSheet($spreadsheet);

        $this->llenarEncabezado($sheet, $empresa, $dt);
        $this->llenarFilasDesdeRecibos($sheet, $recibos);

        return $this->streamXlsx($spreadsheet, 'Liquidaciones_' . $dt->format('Y-m') . '.xlsx');
    }

    public function descargarLote(Request $request, ExportBatch $batch): Response
    {
        $empresa = EmpresaLocal::with('documento')
            ->findOrFail((int) $batch->empresa_local_id);

        $recibos = Recibo::with([
                'usuarioExterno.documento',
                'usuarioExterno.eps',
                'usuarioExterno.arl',
                'usuarioExterno.pension',
                'usuarioExterno.caja',
                'usuarioExterno.subtipoCotizante',
            ])
            ->where('empresa_local_id', $empresa->id)
            ->where('export_batch_id', $batch->id)
            ->orderBy('fecha')
            ->get();

        if ($recibos->isEmpty()) {
            return back()->with('warning', "No hay recibos en el lote #{$batch->id}.");
        }

        $spreadsheet = $this->loadTemplate();
        $sheet       = $this->getLiquidacionesSheet($spreadsheet);

        // Si el batch no trae período, usamos el del primer recibo
        $dt = $batch->periodo
            ? Carbon::createFromFormat('Y-m', $batch->periodo)->startOfMonth()
            : optional($recibos->first()?->fecha)?->copy()->startOfMonth() ?? now()->startOfMonth();

        $this->llenarEncabezado($sheet, $empresa, $dt);
        $this->llenarFilasDesdeRecibos($sheet, $recibos);

        return $this->streamXlsx($spreadsheet, "Liquidaciones_lote_{$batch->id}.xlsx");
    }

    /* ==================== helpers ==================== */

    private function loadTemplate()
    {
        $paths = [
            storage_path('app/templates/Libro1.xlsx'),
            storage_path('app/templates/libro1.xlsx'),
        ];

        foreach ($paths as $p) {
            if (is_file($p)) {
                return IOFactory::load($p);
            }
        }

        abort(404, 'No se encontró la plantilla Libro1.xlsx/libro1.xlsx en storage/app/templates.');
    }

    private function getLiquidacionesSheet($spreadsheet): Worksheet
    {
        $sheet = $spreadsheet->getSheetByName('Liquidaciones');

        if (!$sheet) {
            abort(500, "La hoja 'Liquidaciones' no existe en la plantilla.");
        }

        return $sheet;
    }

    /**
     * Encabezado exacto como la planilla que sí valida:
     *  - B9 = período de Pensión (mes anterior)
     *  - C9 = período de Salud (mes actual)
     *  - E9 = tipo de planilla (E)
     *  - K1..K6 = bloque de empresa
     */
    private function llenarEncabezado(Worksheet $sheet, EmpresaLocal $empresa, Carbon $dt): void
    {
        $docSigla = $empresa->documento->nombre ?? 'NIT';

        // Cabecera superior (columna K)
        $sheet->setCellValue('K1', $empresa->nombre);
        $sheet->setCellValue('K2', "{$docSigla} {$empresa->numero_documento}");
        $sheet->setCellValue('K3', 'SUCURSAL PRINCIPAL: PRINCIPAL');
        $sheet->setCellValue('K4', 'TIPO EMPLEADOR: EMPRESA');
        $sheet->setCellValue('K5', 'PERFIL: NOMINA/TESORERIA');
        $sheet->setCellValue('K6', 'ÚLTIMO ACCESO: ' . now()->format('Y/m/d H:i:s'));

        // Períodos (fila 9)
        $periodoPension = $dt->copy()->subMonthNoOverflow()->format('Y-m');
        $periodoSalud   = $dt->format('Y-m');

        $sheet->setCellValue('B9', $periodoPension); // Pensión
        $sheet->setCellValue('C9', $periodoSalud);   // Salud
        $sheet->setCellValue('E9', 'E');             // Tipo planilla
    }

    private function llenarFilasDesdeRecibos(Worksheet $sheet, $recibos): void
    {
        // === Config de mapeos/normalizaciones ===
        $mapCajaUbicacion = [
            'comfandi' => ['VALLE', 'CALI'],
            'comfiar'  => ['ARAUCA', 'ARAUCA'],
        ];

        // Si tu planilla "que pasa" trae un espacio al final del código CCF, pon esto en true
        $addSpaceAfterCE = false;

        // Actividad económica por nivel (fallback si no hay config/arl.php)
        $actividadPorNivelFallback = [
            1 => '1711001',
            2 => '2741001',
            3 => '3432101',
            4 => '4466301',
            5 => '5432201',
        ];

        // Redondeo a múltiplos de 100 hacia arriba (valor de cotizaciones)
        $roundUp100 = static function (float $v): float {
            return $v <= 0 ? 0.0 : (float) (ceil($v / 100) * 100);
        };

        // Normaliza espacios repetidos
        $norm = static function (?string $s): string {
            $s = (string) $s;
            $s = preg_replace('/\s+/u', ' ', $s);

            return trim($s ?? '');
        };

        $tplRow   = 19;
        $startCol = 'A';
        $endCol   = 'CT';

        // Columnas que sobreescribimos (no se clonan desde el template)
        $inputCols = [
            // básicos
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S',
            // novedades y banderas
            'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ',
            // pensión
            'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ',
            // eps
            'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR',
            // arl
            'BV', 'BW', 'BX', 'BY', 'BZ', 'CB', 'CC',
            // ccf
            'CD', 'CE', 'CF', 'CG', 'CH',
            // otros
            'AU', 'CR',
        ];

        $contador = 1;
        $fila     = $tplRow;
        $novText  = 'Todos los sistemas (ARL, AFP, CCF, EPS)';

        foreach ($recibos as $r) {
            $u = $r->usuarioExterno;
            if (!$u) {
                continue;
            }

            // Clonar la fila base de la plantilla
            if ($fila > $tplRow) {
                $this->cloneRowFromTemplate($sheet, $tplRow, $fila, $startCol, $endCol, $inputCols);
            }

            // Snapshots con fallback
            $pensionNombre = $r->pension_nombre ?: ($u->pension->nombre ?? null) ?: 'NINGUNA';
            $epsNombre     = $r->eps_nombre ?: ($u->eps->nombre ?? null) ?: 'NINGUNA';
            $arlNombre     = $r->arl_nombre ?: ($u->arl->nombre ?? null) ?: 'NINGUNA';
            $cajaNombre    = $r->caja_nombre ?: ($u->caja->nombre ?? null);

            // Nivel ARL (1..5)
            $arlNivelRaw = $r->arl_nivel_riesgo ?? $r->arl_nivel ?? ($u->arl->nivel ?? $u->arl->nivel_riesgo ?? null);
            $arlNivel    = null;

            if ($arlNivelRaw !== null && preg_match('/\d+/', (string) $arlNivelRaw, $m)) {
                $arlNivel = (int) $m[0];
                if ($arlNivel < 1 || $arlNivel > 5) {
                    $arlNivel = null;
                }
            }

            // Tarifa ARL → ratio Excel
            $arlTarifa = null;

            if ($r->arl_tarifa !== null) {
                $arlTarifa = ((float) $r->arl_tarifa) / 100.0;
            } elseif (($u->arl->porcentaje ?? null) !== null) {
                $arlTarifa = ((float) $u->arl->porcentaje) / 100.0;
            }

            // Actividad económica (texto 7 dígitos exactos)
            $mapConfig     = (array) config('arl.actividad_por_nivel', []);
            $arlActividad  = $r->arl_actividad
                ?? ($u->arl->actividad_economica ?? null)
                ?? ($arlNivel ? ($mapConfig[$arlNivel] ?? $actividadPorNivelFallback[$arlNivel] ?? null) : null);

            // Ubicación por caja → H/I
            $nombreCaja        = strtolower(trim((string) $cajaNombre));
            [$depto, $ciudad]  = $mapCajaUbicacion[$nombreCaja] ?? ['', ''];

            // Bases
            $dias       = (int) ($r->dias_liquidar ?? 0);
            $salarioMes = (float) ($u->sueldo ?? 0);
            $ibc        = round($salarioMes * ($dias / 30), 2);
            $horas      = $dias * 8;

            // ===== Novedades desde BD (valor crudo y sin defaults) =====
            $novRawDb = (string) $r->getRawOriginal('novedad'); // 'Ingreso' | 'Retiro' | null
            $ing      = (strcasecmp($novRawDb, 'Ingreso') === 0);
            $ret      = (strcasecmp($novRawDb, 'Retiro') === 0);

            // =================== Escritura ===================
            // Identificación (forzamos C como texto)
            $sheet->setCellValue("A{$fila}", $contador);
            $sheet->setCellValue("B{$fila}", $u->documento->nombre ?? 'CC');
            $sheet->setCellValueExplicit("C{$fila}", (string) $u->numero, DataType::TYPE_STRING);
            $sheet->setCellValue("D{$fila}", $norm($u->primer_apellido));
            $sheet->setCellValue("E{$fila}", $norm($u->segundo_apellido));
            $sheet->setCellValue("F{$fila}", $norm($u->primer_nombre));
            $sheet->setCellValue("G{$fila}", $norm($u->segundo_nombre));

            $sheet->setCellValue("H{$fila}", $depto);
            $sheet->setCellValue("I{$fila}", $ciudad);

            $sheet->setCellValue("J{$fila}", '1. DEPENDIENTE');

            // ✅ K = solo NOMBRE del Subtipo de Cotizante (fallback: "NINGUNO")
            $subNombre = $norm($u->subtipoCotizante->nombre ?? '');
            $sheet->setCellValue("K{$fila}", $subNombre !== '' ? $subNombre : 'NINGUNO');

            $sheet->setCellValue("L{$fila}", $horas);

            $sheet->setCellValue("M{$fila}", 'NO');
            $sheet->setCellValue("N{$fila}", 'NO');
            $sheet->setCellValue("O{$fila}", '');

            // Novedades y banderas (ajustadas a “NO”/vacío)
            $sheet->setCellValue("P{$fila}", $ing ? $novText : 'NO'); // Ingreso
            $sheet->setCellValue("Q{$fila}", '');
            $sheet->setCellValue("R{$fila}", $ret ? $novText : 'NO'); // Retiro
            $sheet->setCellValue("S{$fila}", '');

            foreach (['T', 'U', 'V', 'W', 'X', 'Z', 'AA', 'AD', 'AG', 'AJ', 'AM', 'AN'] as $c) {
                $sheet->setCellValue($c . $fila, 'NO');
            }

            foreach (['Y', 'AB', 'AC', 'AE', 'AF', 'AH', 'AI', 'AK', 'AL', 'AO', 'AP'] as $c) {
                $sheet->setCellValue($c . $fila, '');
            }

            $sheet->setCellValue("AQ{$fila}", 0);

            // Pensión
            $sheet->setCellValue("AX{$fila}", $pensionNombre);
            $sheet->setCellValue("AY{$fila}", $dias);
            $sheet->setCellValue("AZ{$fila}", $ibc);
            $sheet->setCellValue("BA{$fila}", 0.16);
            $sheet->getStyle("BA{$fila}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

            $valorPension = $roundUp100($ibc * 0.16);

            $sheet->setCellValue("BB{$fila}", $valorPension);
            $sheet->setCellValue("BC{$fila}", 'Sin Riesgo');

            foreach (['BD', 'BE', 'BF', 'BG', 'BH'] as $c) {
                $sheet->setCellValue($c . $fila, 0);
            }

            $sheet->setCellValue("BI{$fila}", $valorPension);
            $sheet->setCellValue("BJ{$fila}", 'NINGUNA');

            // === OVERRIDE SOLO SI AX = "NINGUNA" ===
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
            $sheet->setCellValue("BM{$fila}", $ibc);
            $sheet->setCellValue("BN{$fila}", 0.04);
            $sheet->getStyle("BN{$fila}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

            $sheet->setCellValue("BO{$fila}", $roundUp100($ibc * 0.04));
            $sheet->setCellValue("BP{$fila}", 0);
            $sheet->setCellValue("BQ{$fila}", str_repeat(' ', 15)); // como en planilla “que pasa”
            $sheet->setCellValue("BR{$fila}", 0);

            // ARL
            $sheet->setCellValue("BV{$fila}", $arlNombre);
            $sheet->setCellValue("BW{$fila}", $dias);
            $sheet->setCellValue("BX{$fila}", $ibc);

            if ($arlTarifa !== null) {
                $sheet->setCellValue("BY{$fila}", (float) $arlTarifa);
                $sheet->getStyle("BY{$fila}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
            } else {
                $sheet->setCellValue("BY{$fila}", '');
            }

            $sheet->setCellValue("BZ{$fila}", $arlNivel ?? '');

            if ($arlActividad) {
                $sheet->setCellValueExplicit("CB{$fila}", (string) $arlActividad, DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue("CB{$fila}", '');
            }

            $sheet->setCellValue("CC{$fila}", $roundUp100($ibc * (float) ($arlTarifa ?? 0.0)));

            // CCF
            $sheet->setCellValue("CD{$fila}", $dias);

            // ✅ CE = NOMBRE de la caja (MAYÚSCULAS)
            $cajaNombreUpper = $cajaNombre ? mb_strtoupper($cajaNombre, 'UTF-8') : 'NINGUNA';
            $sheet->setCellValueExplicit("CE{$fila}", $cajaNombreUpper, DataType::TYPE_STRING);

            $ibcCcf = ($nombreCaja === 'comfiar') ? 1000.0 : $ibc;

            $sheet->setCellValue("CF{$fila}", $ibcCcf);
            $sheet->setCellValue("CG{$fila}", 0.04);
            $sheet->getStyle("CG{$fila}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

            $sheet->setCellValue("CH{$fila}", $roundUp100($ibcCcf * 0.04));

            // Parafiscales
            $sheet->setCellValue("CR{$fila}", 'SI');

            // Salario referencia
            $sheet->setCellValue("AU{$fila}", $salarioMes);

            $contador++;
            $fila++;
        }
    }

    private function streamXlsx($spreadsheet, string $filename): Response
    {
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

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

            $src = $colLetter . $fromRow;
            $dst = $colLetter . $toRow;

            $sheet->setCellValue($dst, $sheet->getCell($src)->getValue());
            $sheet->duplicateStyle($sheet->getStyle($src), $dst);

            $dv = $sheet->getCell($src)->getDataValidation();

            if ($dv && $dv->getType() !== '') {
                $sheet->getCell($dst)->setDataValidation(clone $dv);
            }
        }
    }
    // use al inicio del archivo:


public function descargarPorCaja(Request $request): \Symfony\Component\HttpFoundation\Response
{
    $request->validate([
        'empresa_local_id' => ['required', 'integer', 'exists:empresa_local,id'],
        'periodo'          => ['required', 'regex:/^\d{4}-\d{2}($|-\d{2}$)/'],
    ]);

    $empresa = \App\Models\EmpresaLocal::with('documento')
        ->findOrFail((int) $request->empresa_local_id);

    // Período (mismo criterio que "descargar")
    $dt     = strlen($request->periodo) === 7
        ? \Carbon\Carbon::createFromFormat('Y-m', $request->periodo)->startOfMonth()
        : \Carbon\Carbon::parse($request->periodo)->startOfMonth();
    $inicio = $dt->copy()->startOfMonth();
    $fin    = $dt->copy()->endOfMonth();

    // === TRAER SOLO PENDIENTES (export_batch_id NULL) ===
    if (!\Illuminate\Support\Facades\Schema::hasColumn('recibos', 'export_batch_id')) {
        return back()->with('warning', 'Falta la columna export_batch_id. Ejecuta las migraciones.');
    }

    $recibosPendientes = \App\Models\Recibo::with([
            'usuarioExterno.documento',
            'usuarioExterno.eps',
            'usuarioExterno.arl',
            'usuarioExterno.pension',
            'usuarioExterno.caja',
            'usuarioExterno.subtipoCotizante',
        ])
        ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
        ->where('empresa_local_id', $empresa->id)
        ->whereNull('export_batch_id')        // <<<<<< SOLO PENDIENTES
        ->orderBy('fecha')
        ->get();

    if ($recibosPendientes->isEmpty()) {
        return back()->with('info', "No hay recibos PENDIENTES para {$empresa->nombre} en el periodo {$request->periodo}.");
    }

    // Particionar por caja
    $norm = fn(?string $s) => $this->normalizeCaja($s);
    $esComfiar = function ($r) use ($norm) {
        $nombre = $r->caja_nombre ?? ($r->usuarioExterno->caja->nombre ?? null);
        return $norm($nombre) === 'comfiar';
    };

    $grupoComfiar = $recibosPendientes->filter($esComfiar)->values();
    $grupoOtros   = $recibosPendientes->reject($esComfiar)->values(); // Comfandi u otras

    // Si por partición no quedó nada, avisar
    if ($grupoComfiar->isEmpty() && $grupoOtros->isEmpty()) {
        return back()->with('info', 'No hay pendientes para exportar por caja.');
    }

    // Crear los XLSX en memoria por grupo
    $files = [];

    if ($grupoComfiar->isNotEmpty()) {
        $spreadsheet = $this->loadTemplate();
        $sheet       = $this->getLiquidacionesSheet($spreadsheet);
        $this->llenarEncabezado($sheet, $empresa, $dt);
        $this->llenarFilasDesdeRecibos($sheet, $grupoComfiar);
        $files[] = [
            'name' => sprintf('Liquidaciones_%s_%s_COMFIAR.xlsx', $empresa->id, $dt->format('Y-m')),
            'data' => $this->xlsxToString($spreadsheet),
        ];
    }

    if ($grupoOtros->isNotEmpty()) {
        $spreadsheet = $this->loadTemplate();
        $sheet       = $this->getLiquidacionesSheet($spreadsheet);
        $this->llenarEncabezado($sheet, $empresa, $dt);
        $this->llenarFilasDesdeRecibos($sheet, $grupoOtros);
        $files[] = [
            'name' => sprintf('Liquidaciones_%s_%s_OTRAS.xlsx', $empresa->id, $dt->format('Y-m')),
            'data' => $this->xlsxToString($spreadsheet),
        ];
    }

    if (empty($files)) {
        return back()->with('info', 'No se encontraron pendientes para exportar por caja.');
    }

    // Armar ZIP en memoria
    $zipName = sprintf('Liquidaciones_%s_%s_por_caja.zip', $empresa->id, $dt->format('Y-m'));
    $tmpZip  = tempnam(sys_get_temp_dir(), 'zip');

    $zip = new \ZipArchive();
    if ($zip->open($tmpZip, \ZipArchive::OVERWRITE) !== true) {
        abort(500, 'No fue posible crear el ZIP.');
    }
    foreach ($files as $f) {
        $zip->addFromString($f['name'], $f['data']);
    }
    $zip->close();

    // === Crear lote y marcar SOLO los ids pendientes exportados ===
    $idsExportados = collect()
        ->merge($grupoComfiar->pluck('id'))
        ->merge($grupoOtros->pluck('id'))
        ->unique()
        ->values();

    \Illuminate\Support\Facades\DB::transaction(function () use ($idsExportados, $empresa, $dt) {
        // Agregados del set a marcar (aún NULL)
        $agg = \App\Models\Recibo::whereIn('id', $idsExportados)
            ->whereNull('export_batch_id')
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(total),0) as s')
            ->first();

        // Período: si todos son del mismo mes, úsalo; si no, guarda el del form
        $months = \App\Models\Recibo::whereIn('id', $idsExportados)
            ->whereNull('export_batch_id')
            ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') as ym")
            ->distinct()
            ->pluck('ym');

        $periodo = $months->count() === 1 ? $months->first() : $dt->format('Y-m');

        // Crear ExportBatch
        $batch = \App\Models\ExportBatch::create([
            'empresa_local_id' => $empresa->id,
            'codigo'           => 'ZIP-CAJA-' . now()->format('YmdHis'),
            'periodo'          => $periodo,
            'recibos_count'    => (int) ($agg->c ?? 0),
            'total'            => (float) ($agg->s ?? 0),
        ]);

        // Marcar recibos como exportados (asignar batch)
        \App\Models\Recibo::whereIn('id', $idsExportados)
            ->whereNull('export_batch_id')
            ->update(['export_batch_id' => $batch->id]);
    });

    // Descargar y eliminar archivo temporal
    return response()->download($tmpZip, $zipName)->deleteFileAfterSend(true);
}


/** Normaliza nombre de caja: minúsculas, sin tildes, sin espacios extras */
private function normalizeCaja(?string $s): string
{
    if ($s === null) return '';
    $s = trim(mb_strtolower($s, 'UTF-8'));
    $s = preg_replace('/\s+/u', ' ', $s);
    if (class_exists('\Normalizer')) {
        $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
        $s = preg_replace('/\pM/u', '', $s); // quita tildes
    }
    return $s;
}

/** Convierte Spreadsheet a string (.xlsx) en memoria */
private function xlsxToString(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): string
{
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    ob_start();
    $writer->save('php://output');
    return (string) ob_get_clean();
}

}
