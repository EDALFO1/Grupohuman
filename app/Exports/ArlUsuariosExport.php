<?php

namespace App\Exports;

use App\Models\ArlUsuario;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ArlUsuariosExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    protected int $empresaLocalId;
    protected ?string $q;
    protected $estado;

    public function __construct(int $empresaLocalId, ?string $q = null, $estado = null)
    {
        $this->empresaLocalId = $empresaLocalId;
        $this->q = $q;
        $this->estado = $estado;
    }

    public function query()
    {
        return ArlUsuario::with(['documento','arl','empresaExterna'])
            ->deEmpresa($this->empresaLocalId)
            ->buscar($this->q)
            ->estado($this->estado)
            ->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'Tipo Doc',
            'Número',
            'Nombre',
            'Fecha Ingreso',
            'ARL (Nivel)',
            'Empresa Externa',
            'Base Cotización',
            'Administración',
            'Valor (ARL+Adm)',
            'Estado',
            'Fecha Retiro',
        ];
    }

    public function map($u): array
    {
        return [
            optional($u->documento)->nombre ?? 'N/A',
            $u->numero,
            $u->nombre,
            optional($u->fecha_ingreso)->format('Y-m-d'),
            trim(((optional($u->arl)->nombre ?? 'N/A').' '.(optional($u->arl)->nivel ? '(Nivel '.optional($u->arl)->nivel.')' : ''))),
            optional($u->empresaExterna)->nombre ?? 'N/A',
            (float)$u->base_cotizacion,
            (float)$u->administracion,
            (float)$u->valor, // accessor ya devuelve entero redondeado
            $u->estado ? 'Activo' : 'Inactivo',
            optional($u->fecha_retiro)->format('Y-m-d'),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_00, // base_cotizacion
            'H' => NumberFormat::FORMAT_NUMBER_00, // administracion
            'I' => NumberFormat::FORMAT_NUMBER,    // valor (entero)
        ];
    }
}
