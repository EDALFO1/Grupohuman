<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsuarioExternosTemplateExport implements FromArray, WithHeadings
{
    // Si quieres dejar filas de ejemplo, puedes devolverlas en array()
    public function array(): array
    {
        return []; // sin datos, solo la cabecera
    }

    public function headings(): array
    {
        // Estos títulos deben coincidir (o ser alias aceptables) con lo que espera tu Import
        return [
            'documento',           // tipo de documento (ej: Cedula)
            'asesor',              // nombre o número de asesor
            'numero',              // número de documento
            'fecha_expedicion',    // YYYY-MM-DD o excel date
            'primer_apellido',
            'segundo_apellido',
            'primer_nombre',
            'segundo_nombre',
            'fecha_nacimiento',
            'correo_electronico',
            'direccion',
            'telefono',
            'fecha_afiliacion',
            'sexo',                // M,F,Otro
            'eps',
            'arl',
            'pension',
            'caja',
            'subtipo_cotizante',
            'empresa_local',       // nombre o id (si no forzas empresa)
            'empresa_externa',
            'sueldo',
            'admon',
            'seg_exequial',
            'mora',
            'otros_servicios',
            'cargo',
            'estado',              // 1 o 0
            'novedad',             // Ingreso o Retiro
            'fecha_retiro'
        ];
    }
}
