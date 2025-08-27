<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsuarioExternoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cambia si usas policies
    }

    public function rules(): array
    {
        return [
            'documento_id'          => 'required|exists:documentos,id',
            'asesor_id'             => 'required|exists:asesores,id',
            'numero'                => 'required|string|unique:usuario_externos,numero',
            'fecha_expedicion'      => 'required|date',
            'primer_apellido'       => 'required|string',
            'segundo_apellido'      => 'nullable|string',
            'primer_nombre'         => 'required|string',
            'segundo_nombre'        => 'nullable|string',
            'fecha_nacimiento'      => 'required|date|before:today',
            'correo_electronico'    => 'nullable|email',
            'direccion'             => 'required|string',
            'telefono'              => 'required|string',
            'fecha_afiliacion'      => 'required|date',
            'sexo'                  => 'required|in:M,F,Otro',
            'eps_id'                => 'required|exists:eps,id',
            'arl_id'                => 'required|exists:arls,id',
            'pension_id'            => 'required|exists:pensions,id',
            'caja_id'               => 'required|exists:cajas,id',
            'subtipo_cotizantes_id' => 'required|exists:subtipo_cotizantes,id',
            'empresa_externa_id'    => 'required|exists:empresa_externas,id',
            'sueldo'                => 'required|numeric|min:0',
            'admon'                 => 'required|numeric|min:0',
            'seg_exequial'          => 'nullable|numeric|min:0',
            'mora'                  => 'nullable|numeric|min:0',
            'otros_servicios'       => 'nullable|numeric|min:0',
            'cargo'                 => 'required|string',
            'estado'                => 'required|boolean',
            'override_parametros'   => 'sometimes|boolean',
            'empresa_local_id' => 'required|exists:empresa_local,id',

        ];
    }
}
