<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'precio_unitario', 'iva'];

    public function facturas()
    {
        return $this->belongsToMany(Factura::class)
                    ->withPivot('cantidad', 'precio_unitario', 'subtotal')
                    ->withTimestamps();
    }
}
