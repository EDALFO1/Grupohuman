@extends('layouts.main')

@section('contenido')
<div class="container">
    <h3>Detalle de Recibo</h3>

    <a href="{{ route('recibos') }}" class="btn btn-secondary mb-3">Volver</a>

    <div class="card">
        <div class="card-body">
            <p><strong>Número Recibo:</strong> {{ $recibo->numero }}</p>
            <p><strong>Fecha:</strong> {{ $recibo->fecha->format('Y-m-d') }}</p>

            <h5>Usuario</h5>
            <ul>
                <li><strong>Nombre:</strong>
                    {{ $recibo->usuarioExterno->primer_nombre }}
                    {{ $recibo->usuarioExterno->segundo_nombre }}
                    {{ $recibo->usuarioExterno->primer_apellido }}
                </li>
                <li><strong>Documento:</strong> {{ $recibo->usuarioExterno->numero }}</li>
                <li><strong>Correo:</strong> {{ $recibo->usuarioExterno->correo_electronico }}</li>
                <li><strong>Teléfono:</strong> {{ $recibo->usuarioExterno->telefono }}</li>
            </ul>

            <h5>Valores</h5>
            <ul>
                <li><strong>Días a liquidar:</strong> {{ $recibo->dias_liquidar }}</li>
                <li><strong>EPS:</strong> ${{ number_format($recibo->valor_eps, 0, ',', '.') }} ({{ $recibo->eps_nombre }})</li>
                <li><strong>ARL:</strong> ${{ number_format($recibo->valor_arl, 0, ',', '.') }} ({{ $recibo->arl_nombre }})</li>
                <li><strong>Pensión:</strong> ${{ number_format($recibo->valor_pension, 0, ',', '.') }} ({{ $recibo->pension_nombre }})</li>
                <li><strong>Caja:</strong> ${{ number_format($recibo->valor_caja, 0, ',', '.') }} ({{ $recibo->caja_nombre }})</li>
                <li><strong>Administración:</strong> ${{ number_format($recibo->valor_admon, 0, ',', '.') }}</li>
                <li><strong>Exequial:</strong> ${{ number_format($recibo->valor_exequial, 0, ',', '.') }}</li>
                <li><strong>Mora:</strong> ${{ number_format($recibo->valor_mora, 0, ',', '.') }}</li>
                <li><strong>Otros Servicios:</strong> ${{ number_format($recibo->otros_servicios, 0, ',', '.') }}</li>
                <li><strong>Total:</strong> <span class="text-success">${{ number_format($recibo->total, 0, ',', '.') }}</span></li>
            </ul>

            <h5>Base del período</h5>
            <ul>
                <li><strong>Sueldo base:</strong> ${{ number_format($recibo->sueldo_base ?? 0, 0, ',', '.') }}</li>
                <li><strong>Admon base:</strong> ${{ number_format($recibo->admon_base ?? 0, 0, ',', '.') }}</li>
                <li><strong>Override parámetros:</strong> {{ $recibo->override_parametros ? 'Sí' : 'No' }}</li>
            </ul>

            <h5>Novedad</h5>
            <ul>
                <li><strong>Tipo:</strong> {{ $recibo->novedad ?? 'Ingreso' }}</li>
                @if($recibo->novedad === 'Retiro')
                    <li><strong>Fecha retiro:</strong> {{ optional($recibo->fecha_retiro)->format('Y-m-d') }}</li>
                @endif
            </ul>
        </div>
    </div>
</div>
@endsection
