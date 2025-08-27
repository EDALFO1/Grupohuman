@extends('layouts.main')

@section('contenido')
<div class="container">
    <h3>Detalle de Remisión</h3>

    <a href="{{ route('remisiones') }}" class="btn btn-secondary mb-3">Volver</a>

    <div class="card">
        <div class="card-body">
            <p><strong>Número Remisión:</strong> {{ $remision->numero }}</p>
            <p><strong>Fecha:</strong> {{ $remision->fecha }}</p>

            <h5>Usuario</h5>
            <ul>
                <li><strong>Nombre:</strong> {{ $remision->usuario->primer_nombre }} {{ $remision->usuario->segundo_nombre }} {{ $remision->usuario->primer_apellido }}</li>
                <li><strong>Correo:</strong> {{ $remision->usuario->correo_electronico }}</li>
                <li><strong>Teléfono:</strong> {{ $remision->usuario->telefono }}</li>
            </ul>

            <h5>Valores</h5>
            <ul>
                <li><strong>Sueldo:</strong> ${{ number_format($remision->sueldo, 2) }}</li>
                <li><strong>Administración:</strong> ${{ number_format($remision->admon, 2) }}</li>
                <li><strong>Seguro Exequial:</strong> ${{ number_format($remision->seg_exequial ?? 0, 2) }}</li>
                <li><strong>Mora:</strong> ${{ number_format($remision->mora ?? 0, 2) }}</li>
                <li><strong>Otros Servicios:</strong> ${{ number_format($remision->otros_servicios ?? 0, 2) }}</li>
                <li><strong>Total:</strong> <span class="text-success">${{ number_format($remision->total, 2) }}</span></li>
            </ul>
        </div>
    </div>
</div>
@endsection

