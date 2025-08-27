<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Arl extends Model
{
    protected $table = 'arls';

    protected $fillable = [
        'nombre',
        'codigo',
        'nivel',
        'porcentaje',
        'actividad_economica',
    ];

    protected $casts = [
        'nivel' => 'integer',
        'porcentaje' => 'float',
    ];

    public static function rules($id = null)
    {
        // Tomamos el nivel del request (create/update) o, si no viene, del registro actual (update)
        $nivelInput   = request('nivel');
        $nivelFromDb  = null;

        if ($id && is_null($nivelInput)) {
            $nivelFromDb = optional(
                static::query()->select('nivel')->find($id)
            )->nivel;
        }

        $nivelParaUnique = $nivelInput ?? $nivelFromDb;

        // Regla unique base
        $uniqueCodigo = Rule::unique('arls', 'codigo')->ignore($id);

        // Si conocemos el nivel, aplicamos unicidad compuesta (codigo + nivel)
        if (!is_null($nivelParaUnique)) {
            $uniqueCodigo = $uniqueCodigo->where(
                fn($q) => $q->where('nivel', $nivelParaUnique)
            );
        }

        return [
            'nombre'              => ['required', 'regex:/^[\pL\pN\s\-]+$/u'],
            // Unicidad compuesta: mismo código puede repetirse si cambia el nivel
            'codigo'              => ['required', 'alpha_num', $uniqueCodigo],
            'nivel'               => ['required', 'integer', 'between:1,5'],
            // hasta 4 decimales (coherente con DECIMAL(6,4))
            'porcentaje'          => [
                'required',
                'numeric',
                'regex:/^\d+(\.\d{1,4})?$/',
                'between:0,100',
            ],
            'actividad_economica' => ['nullable', 'regex:/^\d{7}$/'],
        ];
    }

    public function usuarioExterno()
    {
        return $this->hasMany(UsuarioExterno::class);
    }

    protected static function booted()
    {
        static::saving(function (Arl $arl) {
            $map = config('arl.actividad_por_nivel', []);

            // Normaliza nivel a entero si viniera como 'Nivel 3'
            if (!is_null($arl->nivel) && !is_numeric($arl->nivel)) {
                if (preg_match('/(\d+)/', (string) $arl->nivel, $m)) {
                    $arl->nivel = (int) $m[1];
                }
            }

            // Autocompleta actividad_economica si está vacía y hay mapeo
            if (
                $arl->nivel &&
                isset($map[$arl->nivel]) &&
                empty($arl->actividad_economica)
            ) {
                $arl->actividad_economica = $map[$arl->nivel];
            }
        });
    }

    public function scopeNivel($query, int $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    public function scopeActiva($query)
    {
        // si usas SoftDeletes puedes poner ->whereNull('deleted_at')
        return $query;
    }
}
