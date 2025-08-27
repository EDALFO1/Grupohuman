<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $factura->numero }}</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        .no-border { border: none; }
        .text-right { text-align: right; }
        .mt-2 { margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Factura #{{ $factura->numero }}</h2>
    <table class="no-border">
        <tr>
            <td><strong>Fecha:</strong> {{ $factura->fecha_emision->format('Y-m-d') }}</td>
            <td><strong>Tipo:</strong> {{ $factura->tipo }}</td>
        </tr>
        <tr>
            <td><strong>Empresa:</strong> {{ $factura->empresaLocal->nombre }}</td>
            <td><strong>Cliente:</strong> {{ $factura->cliente->nombre }}</td>
        </tr>
    </table>

    <h4 class="mt-2">Detalle de Productos</h4>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($factura->productos as $i => $producto)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->pivot->cantidad }}</td>
                    <td>${{ number_format($producto->pivot->precio_unitario, 2) }}</td>
                    <td>${{ number_format($producto->pivot->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h4 class="mt-2">Totales</h4>
    <table>
        <tr>
            <td class="text-right"><strong>Subtotal:</strong></td>
            <td class="text-right">${{ number_format($factura->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="text-right"><strong>IVA:</strong></td>
            <td class="text-right">${{ number_format($factura->iva, 2) }}</td>
        </tr>
        <tr>
            <td class="text-right"><strong>Retención:</strong></td>
            <td class="text-right">${{ number_format($factura->retencion, 2) }}</td>
        </tr>
        <tr>
            <td class="text-right"><strong>Descuento:</strong></td>
            <td class="text-right">- ${{ number_format($factura->descuento, 2) }}</td>
        </tr>
        <tr>
            <td class="text-right"><strong>Total:</strong></td>
            <td class="text-right"><strong>${{ number_format($factura->total, 2) }}</strong></td>
        </tr>
    </table>
</body>
</html>
