<?php

namespace App\Exports;

use App\Models\UsuarioExterno;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsuarioExternosExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return UsuarioExterno::with([
            'documento','asesor','eps','arl','pension','caja',
            'empresaLocal','empresaExterna','subtipoCotizante'
        ])->get()->map(function ($u) {
            return [
                'Documento'        => $u->documento->nombre ?? '',
                'Numero'           => $u->numero,
                'Nombre Completo'  => $u->full_name,
                'Fecha Nacimiento' => $u->fecha_nacimiento,
                'Correo'           => $u->correo_electronico,
                'Telefono'         => $u->telefono,
                'Direccion'        => $u->direccion,
                'Fecha Afiliacion' => $u->fecha_afiliacion,
                'Sexo'             => $u->sexo,

                'EPS'              => $u->eps->nombre ?? '',
                'ARL'              => $u->arl->nombre ?? '',
                'Pension'          => $u->pension->nombre ?? '',
                'Caja'             => $u->caja->nombre ?? '',
                'Subtipo'          => $u->subtipoCotizante->nombre ?? '',

                'Empresa Local'    => $u->empresaLocal->nombre ?? '',
                'Empresa Externa'  => $u->empresaExterna->nombre ?? '',
                'Asesor'           => $u->asesor->nombre ?? '',

                'Sueldo'           => $u->sueldo,
                'Administracion'   => $u->admon,
                'Otros Servicios'  => $u->otros_servicios,

                'Estado'           => $u->estado ? 'Activo' : 'Inactivo',
                'Novedad'          => $u->novedad,
                'Fecha Retiro'     => $u->fecha_retiro,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Documento',
            'Numero',
            'Nombre Completo',
            'Fecha Nacimiento',
            'Correo',
            'Telefono',
            'Direccion',
            'Fecha Afiliacion',
            'Sexo',

            'EPS',
            'ARL',
            'Pension',
            'Caja',
            'Subtipo',

            'Empresa Local',
            'Empresa Externa',
            'Asesor',

            'Sueldo',
            'Administracion',
            'Otros Servicios',

            'Estado',
            'Novedad',
            'Fecha Retiro',
        ];
    }
}