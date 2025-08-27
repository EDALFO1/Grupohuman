<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\EmpresaLocal;
use App\Models\EmpresaExterna;
use App\Models\Producto;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    public function index()
    {
        $facturas = Factura::with(['empresaLocal', 'cliente'])->latest()->paginate(10);
        return view('facturas.index', compact('facturas'));
    }

    public function create()
    {
        $empresasLocales = EmpresaLocal::all();
        $clientes = EmpresaExterna::all();
        $productos = Producto::all();
        return view('facturas.create', compact('empresasLocales', 'clientes', 'productos'));
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'numero' => 'required|unique:facturas',
        'fecha_emision' => 'required|date',
        'moneda' => 'required|string',
        'tipo' => 'required|in:Factura,Nota Crédito,Nota Débito',
        'empresa_local_id' => 'required|exists:empresa_local,id',
        'cliente_id' => 'required|exists:empresa_externas,id',
        'subtotal' => 'required|numeric',
        'iva' => 'required|numeric',
        'retencion' => 'nullable|numeric',
        'descuento' => 'nullable|numeric',
        'total' => 'required|numeric',
    ]);

    $productos = $request->input('productos', []);
    $retencion = $request->input('retencion', 0);
    $descuento = $request->input('descuento', 0);

    // Validación de totales desde productos
    $totales = $this->recalcularTotalesDesdeProductos($productos, $retencion, $descuento);

    if (
        round($totales['subtotal'], 2) != round($data['subtotal'], 2) ||
        round($totales['iva'], 2) != round($data['iva'], 2) ||
        round($totales['total'], 2) != round($data['total'], 2)
    ) {
        return back()->withErrors(['total' => 'Los valores calculados no coinciden con los enviados.'])->withInput();
    }

    // Crear la factura
    $factura = Factura::create($data);

    // Asociar productos
    if (is_array($productos)) {
        foreach ($productos as $item) {
            if (!empty($item['producto_id']) && is_numeric($item['producto_id'])) {
                $cantidad = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
                $precio = isset($item['precio_unitario']) ? (float)$item['precio_unitario'] : 0;
                $subtotal = isset($item['subtotal']) ? (float)$item['subtotal'] : ($cantidad * $precio);

                if ($cantidad > 0 && $precio >= 0) {
                    $factura->productos()->attach($item['producto_id'], [
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                    ]);
                }
            }
        }
    }

    return redirect()->route('facturas')->with('success', 'Factura creada correctamente.');
}


private function recalcularTotalesDesdeProductos($productos, $retencion = 0, $descuento = 0)
{
    $subtotal = 0;
    $iva = 0;

    foreach ($productos as $item) {
        if (!empty($item['producto_id']) && is_numeric($item['producto_id'])) {
            $producto = Producto::find($item['producto_id']);
            $cantidad = (int) $item['cantidad'];
            $precio = (float) $item['precio_unitario'];

            $subtotal += $cantidad * $precio;

            if ($producto && $producto->iva > 0) {
                $iva += $cantidad * $precio * ($producto->iva / 100);
            }
        }
    }

    $total = $subtotal + $iva - (float)$retencion - (float)$descuento;

    return compact('subtotal', 'iva', 'total');
}

private function adjuntarProductosAFactura(Factura $factura, $productos)
{
    $productos = is_array($productos) ? $productos : [];

    foreach ($productos as $item) {
        if (!empty($item['producto_id']) && is_numeric($item['producto_id'])) {
            $cantidad = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
            $precio = isset($item['precio_unitario']) ? (float)$item['precio_unitario'] : 0;
            $subtotal = isset($item['subtotal']) ? (float)$item['subtotal'] : ($cantidad * $precio);

            if ($cantidad > 0 && $precio >= 0) {
                $factura->productos()->attach($item['producto_id'], [
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                ]);
            }
        }
    }
}


    public function show(Factura $factura)
    {
        return view('facturas.show', compact('factura'));
    }

    public function edit(Factura $factura)
    {
        $empresasLocales = EmpresaLocal::all();
        $clientes = EmpresaExterna::all();
        $productos = Producto::all();
        return view('facturas.edit', compact('factura', 'empresasLocales', 'clientes', 'productos'));
    }

   public function update(Request $request, Factura $factura)
{
    $data = $request->validate([
        'numero' => 'required|unique:facturas,numero,' . $factura->id,
        'fecha_emision' => 'required|date',
        'moneda' => 'required|string',
        'tipo' => 'required|in:Factura,Nota Crédito,Nota Débito',
        'empresa_local_id' => 'required|exists:empresa_local,id',
        'cliente_id' => 'required|exists:empresa_externas,id',
        'subtotal' => 'required|numeric',
        'iva' => 'required|numeric',
        'retencion' => 'nullable|numeric',
        'descuento' => 'nullable|numeric',
        'total' => 'required|numeric',
    ]);

    $productos = $request->input('productos', []);
    $retencion = $request->input('retencion', 0);
    $descuento = $request->input('descuento', 0);

    // Validación de totales desde productos
    $totales = $this->recalcularTotalesDesdeProductos($productos, $retencion, $descuento);

    if (
        round($totales['subtotal'], 2) != round($data['subtotal'], 2) ||
        round($totales['iva'], 2) != round($data['iva'], 2) ||
        round($totales['total'], 2) != round($data['total'], 2)
    ) {
        return back()->withErrors(['total' => 'Los valores calculados no coinciden con los enviados.'])->withInput();
    }

    // Actualizar la factura
    $factura->update($data);

    // Limpiar productos anteriores
    $factura->productos()->detach();

    // Asociar productos nuevos
    if (is_array($productos)) {
        foreach ($productos as $item) {
            if (!empty($item['producto_id']) && is_numeric($item['producto_id'])) {
                $cantidad = isset($item['cantidad']) ? (int)$item['cantidad'] : 1;
                $precio = isset($item['precio_unitario']) ? (float)$item['precio_unitario'] : 0;
                $subtotal = isset($item['subtotal']) ? (float)$item['subtotal'] : ($cantidad * $precio);

                if ($cantidad > 0 && $precio >= 0) {
                    $factura->productos()->attach($item['producto_id'], [
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                    ]);
                }
            }
        }
    }

    return redirect()->route('facturas')->with('success', 'Factura actualizada correctamente.');
}


    public function destroy(Factura $factura)
    {
        $factura->delete();
        return redirect()->route('facturas')->with('success', 'Factura eliminada.');
    }

    public function imprimir(Factura $factura)
    {
        $factura->load(['cliente', 'empresaLocal', 'productos']);
        return view('facturas.imprimir', compact('factura'));
    }

    /**
     * Función privada para recalcular totales desde productos.
     */
  

    /**
     * Función privada para asociar productos a una factura.
     */
    
}
