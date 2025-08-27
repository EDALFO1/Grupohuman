<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo #{{ $recibo->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 20px; }
        h1, h2, h3 { margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 8px; text-align: left; }
        .total { font-weight: bold; font-size: 16px; }
        .text-center { text-align: center; }
    </style>
</head>
<body onload="window.print()">

    <h1 class="text-center">RECIBO DE PAGO</h1>
    <h3 class="text-center">Recibo #{{ $recibo->numero }}</h3>

    <hr>

    <table>
        <tr>
            <th>Documento</th>
            <td>{{ $recibo->usuarioExterno->numero }}</td>
            <th>Nombre</th>
            <td>{{ $recibo->usuarioExterno->primer_nombre }} {{ $recibo->usuarioExterno->primer_apellido }}</td>
        </tr>
        <tr>
            <th>Fecha Afiliación</th>
            <td>{{ $recibo->usuarioExterno->fecha_afiliacion }}</td>
            <th>Fecha Recibo</th>
            <td>{{ $recibo->fecha->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <th>Días a Liquidar</th>
            <td>{{ $recibo->dias_liquidar }}</td>
            <th>Novedad</th>
            <td>{{ $recibo->novedad }}</td>
        </tr>
        @if($recibo->novedad === 'Retiro')
        <tr>
            <th>Fecha Retiro</th>
            <td colspan="3">{{ $recibo->fecha_retiro ? $recibo->fecha_retiro->format('Y-m-d') : '' }}</td>
        </tr>
        @endif
    </table>

    <h3>Detalle de valores</h3>
    <table>
        <tr>
            <th>EPS</th>
            <td>${{ number_format($recibo->valor_eps, 0, ',', '.') }}</td>
            <th>ARL</th>
            <td>${{ number_format($recibo->valor_arl, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Pensión</th>
            <td>${{ number_format($recibo->valor_pension, 0, ',', '.') }}</td>
            <th>Caja</th>
            <td>${{ number_format($recibo->valor_caja, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Administración</th>
            <td>${{ number_format($recibo->valor_admon, 0, ',', '.') }}</td>
            <th>Exequial</th>
            <td>${{ number_format($recibo->valor_exequial, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Mora</th>
            <td>${{ number_format($recibo->valor_mora, 0, ',', '.') }}</td>
            <th>Otros Servicios</th>
            <td>${{ number_format($recibo->otros_servicios, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th colspan="3" class="total">TOTAL</th>
            <td class="total">${{ number_format($recibo->total, 0, ',', '.') }}</td>
        </tr>
    </table>

</body>
</html>
