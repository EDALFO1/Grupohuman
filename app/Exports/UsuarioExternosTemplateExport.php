<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UsuarioExternosTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'documento','asesor','numero','fecha_expedicion',
            'primer_apellido','segundo_apellido','primer_nombre','segundo_nombre',
            'fecha_nacimiento','correo_electronico','direccion','telefono',
            'fecha_afiliacion','sexo','eps','arl','pension','caja',
            'subtipo_cotizante','empresa_local','empresa_externa',
            'sueldo','admon','seg_exequial','mora','otros_servicios',
            'cargo','estado','novedad','fecha_retiro',
        ];
    }

    public function array(): array
    {
        // Fila de ejemplo (opcional). Deja valores vacíos si no quieres ejemplo.
        return [[
            'CC','(nombre asesor o documento)','1234567890','2015-03-12',
            'Perez','Gomez','Carlos','Andres',
            '1990-06-01','carlos@example.com','Calle 123 #45-67','3001234567',
            '2025-01-01','M','Sanitas','riesgo 1','Proteccion','Comfenalco',
            '01','', // empresa_local vacío => toma de sesión
            '(Nombre Empresa Externa)',
            1300000,50000,0,0,0,
            'Auxiliar',1,'Ingreso','',
        ]];
    }
}
