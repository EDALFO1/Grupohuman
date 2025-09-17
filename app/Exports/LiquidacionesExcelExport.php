<?php

namespace App\Exports;

use App\Models\EmpresaLocal;
use App\Models\UsuarioExterno;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class LiquidacionesExcelExport implements WithEvents, WithTitle
{
    use Exportable;

    protected Spreadsheet $spreadsheet;

    public function __construct(
        protected int $empresaLocalId,
        protected string $periodoYmd
    ) {
        // Cargar la plantilla al crear el export
        $this->spreadsheet = IOFactory::load(storage_path('app/templates/libro1.xlsx'));
    }

    public function title(): string
    {
        return 'Liquidaciones';
    }

    protected function periodo(): array
    {
        $dt = strlen($this->periodoYmd) === 7
            ? Carbon::createFromFormat('Y-m', $this->periodoYmd)->startOfMonth()
            : Carbon::parse($this->periodoYmd)->startOfMonth();

        return [
            'inicio' => $dt->copy(),
            'fin'    => $dt->copy()->endOfMonth(),
            'texto'  => $dt->format('Y/m'),
        ];
    }

    protected function diasALiquidar(UsuarioExterno $u, Carbon $inicio, Carbon $fin): int
    {
        $desde = $u->fecha_afiliacion->gt($inicio) ? $u->fecha_afiliacion->copy() : $inicio->copy();
        $hasta = $fin->copy();

        if ($u->novedad === 'Retiro' && $u->fecha_retiro) {
            $ret = Carbon::parse($u->fecha_retiro);
            if ($ret->between($inicio, $fin)) {
                $hasta = $ret->copy();
            }
        }

        $dias = $desde->diffInDaysFiltered(fn($d) => true, $hasta) + 1;
        if ($dias < 0) $dias = 0;
        if ($dias > 30) $dias = 30;

        return $dias;
    }

    protected function hojaLiquidaciones(Worksheet $sheet, EmpresaLocal $empresa, Collection $usuarios): void
{
    $periodo  = $this->periodo();

    // Encabezado
    $sheet->setCellValue('K1', $empresa->nombre);
    $sheet->setCellValue('K2', ($empresa->documento->nombre ?? 'NIT') . ' ' . $empresa->numero_documento);
    $sheet->setCellValue('K3', 'SUCURSAL PRINCIPAL: PRINCIPAL');
    $sheet->setCellValue('K4', 'TIPO EMPLEADOR: EMPRESA');
    $sheet->setCellValue('K5', 'PERFIL: NOMINA/TESORERIA');
    $sheet->setCellValue('K6', 'ÚLTIMO ACCESO: ' . now()->format('Y/m/d H:i:s'));
    $sheet->setCellValue('B9', $periodo['texto']); // Y/m

    // ✅ Fila 10: A–B = pensión (mes anterior), C = salud (mes actual) en formato Y-m
    $dt = $periodo['inicio']; // inicio del mes que estás exportando (Carbon)
    $periodoPension = $dt->copy()->subMonthNoOverflow()->format('Y-m');
    $periodoSalud   = $dt->format('Y-m');

    $sheet->setCellValue('A10', $periodoPension);
    $sheet->setCellValue('B10', $periodoPension);
    $sheet->setCellValue('C10', $periodoSalud);

    $fila = 19;
    $contador = 1;
    $inicio = $periodo['inicio'];
    $fin    = $periodo['fin'];

    foreach ($usuarios as $u) {
        $dias = $this->diasALiquidar($u, $inicio, $fin);
        if ($dias <= 0) continue;

        $salarioMes = (float)$u->sueldo;
        $ibc = round($salarioMes * ($dias / 30), 2);
        $horas = $dias * 8;

        $sheet->setCellValue("A{$fila}", $contador);
        $sheet->setCellValue("B{$fila}", $u->documento->nombre ?? 'CC');
        $sheet->setCellValue("C{$fila}", $u->numero);
        $sheet->setCellValue("D{$fila}", $u->primer_apellido);
        $sheet->setCellValue("E{$fila}", $u->segundo_apellido);
        $sheet->setCellValue("F{$fila}", $u->primer_nombre);
        $sheet->setCellValue("G{$fila}", $u->segundo_nombre);
        $sheet->setCellValue("H{$fila}", '');
        $sheet->setCellValue("I{$fila}", '');
        $sheet->setCellValue("J{$fila}", '1. DEPENDIENTE');
        $sheet->setCellValue("K{$fila}", $u->subtipoCotizante?->codigo . ' ' . $u->subtipoCotizante?->nombre);
        $sheet->setCellValue("L{$fila}", $horas);
        $sheet->setCellValue("AU{$fila}", $salarioMes);
        $sheet->setCellValue("AX{$fila}", $u->pension?->nombre);
        $sheet->setCellValue("AY{$fila}", $dias);
        $sheet->setCellValue("AZ{$fila}", $ibc);

        $fila++;
        $contador++;
    }
}


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $this->spreadsheet->getSheetByName('Liquidaciones');
                $empresa = EmpresaLocal::with('documento')->findOrFail($this->empresaLocalId);
                $usuarios = UsuarioExterno::with([
                    'documento', 'eps', 'arl', 'pension', 'caja', 'subtipoCotizante'
                ])->where('empresa_local_id', $empresa->id)->where('estado', true)->get();

                $this->hojaLiquidaciones($sheet, $empresa, $usuarios);

                // Reemplazar la hoja en el writer
                $event->getDelegate()->setActiveSheetIndex(
                    $this->spreadsheet->getIndex($sheet)
                );
            },
        ];
    }

    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }
}
