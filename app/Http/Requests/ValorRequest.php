<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Valor;

class ValorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('valor')?->id;

        return [
            'empresa_local_id' => ['required','exists:empresa_local,id'],
            'fecha_inicio'     => [
                'required','date',
                Rule::unique('valores','fecha_inicio')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('empresa_local_id', $this->empresa_local_id)),
            ],
            'fecha_fin'        => ['nullable','date','after_or_equal:fecha_inicio'],
            'salario'          => ['required','numeric','min:0'],
            'administracion'   => ['required','numeric','min:0'],
            'activa'           => ['required','boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $activa = filter_var($this->input('activa', true), FILTER_VALIDATE_BOOLEAN);
            if (!$activa) return;

            $empresaId = (int) $this->input('empresa_local_id');
            $inicio    = $this->input('fecha_inicio');
            $fin       = $this->input('fecha_fin');
            $id        = $this->route('valor')?->id;

            $solapa = Valor::deEmpresa($empresaId)
                ->activa()
                ->when($id, fn($q) => $q->where('id','!=',$id))
                ->where(function ($q) use ($inicio, $fin) {
                    $q->whereDate('fecha_inicio', '<=', $fin ?? '9999-12-31')
                      ->where(function ($qq) use ($inicio) {
                          $qq->whereNull('fecha_fin')
                             ->orWhereDate('fecha_fin', '>=', $inicio);
                      });
                })
                ->exists();

            if ($solapa) {
                $v->errors()->add('fecha_inicio', 'El rango de vigencia solapa con otro registro activo de la misma empresa.');
                $v->errors()->add('fecha_fin', 'El rango de vigencia solapa con otro registro activo de la misma empresa.');
            }
        });
    }
}
