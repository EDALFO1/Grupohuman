@extends('layouts.main')


@section('contenido')
<main id="main" class="main">
    <div class="pagetitle d-print-none">
        <h1>Imprimir Remisión</h1>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> Imprimir</button>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body p-5">

                <div class="text-center mb-4">
                    <h2 class="fw-bold">REMISIÓN DE SERVICIOS</h2>
                    <p><strong>N°:</strong> {{ $remision->numero }}</p>
                    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($remision->fecha)->format('d/m/Y') }}</p>
                </div>

                <h5>Información del Usuario</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Nombre completo</th>
                        <td>{{ $remision->usuario_externo->primer_nombre }} {{ $remision->usuario_externo->segundo_nombre }} {{ $remision->usuario_externo->primer_apellido }} {{ $remision->usuario_externo->segundo_apellido }}</td>
                        <th>Documento</th>
                        <td>{{ $remision->usuario_externo->numero }}</td>
                    </tr>
                    <tr>
                        <th>Fecha de afiliación</th>
                        <td>{{ $remision->usuario_externo->fecha_afiliacion }}</td>
                        <th>Cargo</th>
                        <td>{{ $remision->usuario_externo->cargo }}</td>
                    </tr>
                    <tr>
                        <th>Empresa Local</th>
                        <td>{{ $remision->usuario_externo->empresaLocal->nombre }}</td>
                        <th>Empresa Externa</th>
                        <td>{{ $remision->usuario_externo->empresaExterna->nombre }}</td>
                    </tr>
                    <tr>
                        <th>EPS</th>
                        <td>{{ $remision->usuario_externo->eps->nombre }}</td>
                        <th>ARL</th>
                        <td>{{ $remision->usuario_externo->arl->nombre }}</td>
                    </tr>
                </table>

                <h5>Detalle de Valores</h5>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Días a Liquidar</th>
                            <th>EPS</th>
                            <th>ARL</th>
                            <th>Pensión</th>
                            <th>Caja</th>
                            <th>Administración</th>
                            <th>Exequial</th>
                            <th>Mora</th>
                            <th>Otros</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $remision->dias_liquidar }}</td>
                            <td>${{ number_format($remision->valor_eps, 2) }}</td>
                            <td>${{ number_format($remision->valor_arl, 2) }}</td>
                            <td>${{ number_format($remision->valor_pension, 2) }}</td>
                            <td>${{ number_format($remision->valor_caja, 2) }}</td>
                            <td>${{ number_format($remision->valor_admon, 2) }}</td>
                            <td>${{ number_format($remision->valor_exequial, 2) }}</td>
                            <td>${{ number_format($remision->valor_mora, 2) }}</td>
                            <td>${{ number_format($remision->otros_servicios, 2) }}</td>
                            <td class="fw-bold">${{ number_format($remision->total, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <p><strong>Total en letras:</strong> {{ \NumberFormatter::create('es', \NumberFormatter::SPELLOUT)->format($remision->total) }} pesos</p>

                <br><br>
                <div class="row mt-5">
                    <div class="col text-center">
                        <p>_________________________________</p>
                        <p><strong>Responsable</strong></p>
                    </div>
                    <div class="col text-center">
                        <p>_________________________________</p>
                        <p><strong>Asesor</strong></p>
                    </div>
                </div>

            </div>
        </div>
    </section>
</main>
@endsection
