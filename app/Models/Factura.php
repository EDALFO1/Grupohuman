<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $fillable = [
        'numero',
        'fecha_emision',
        'moneda',
        'tipo',
        'empresa_local_id',
        'cliente_id',
        'subtotal',
        'iva',
        'retencion',
        'descuento',
        'total',
        'xml_ubl',
        'cufe',
        'estado_envio',
        'respuesta_dian',
    ];
    protected $casts = [
    'fecha_emision' => 'date',
];


    public function empresaLocal()
    {
        return $this->belongsTo(EmpresaLocal::class);
    }

    public function cliente()
    {
        return $this->belongsTo(EmpresaExterna::class);
    }
    public function productos()
{
    return $this->belongsToMany(Producto::class)
                ->withPivot('cantidad', 'precio_unitario', 'subtotal')
                ->withTimestamps();
}

}

