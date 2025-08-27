@extends('layouts.main')

@section('contenido')

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Panel Administración Facturas</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">

            <div class="card">
              <div class="card-body">
                <hr>

                <a href="{{ route('facturas.create') }}" class="btn btn-primary mb-3">
                    <i class="fa-solid fa-circle-plus"></i> Crear Nueva Factura
                </a>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <hr>
                <table class="table datatable">
                  <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Subtotal</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($facturas as $factura)
                        <tr>
                            <td>{{ $factura->numero }}</td>
                            <td>{{ $factura->fecha_emision->format('Y-m-d') }}</td>
                            <td>{{ $factura->cliente->nombre }}</td>
                            <td>${{ number_format($factura->subtotal, 0, ',', '.') }}</td>
                            <td>${{ number_format($factura->total, 0, ',', '.') }}</td>
                            <td>{{ $factura->estado_envio }}</td>
                            <td>
                                <a href="{{ route('facturas.edit', $factura) }}" class="btn btn-warning btn-sm">Editar</a>
                                <form action="{{ route('facturas.destroy', $factura) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('¿Deseas eliminar esta factura?')">Eliminar</button>
                                </form>
                                <a href="{{ route('facturas.imprimir', $factura) }}" target="_blank" class="btn btn-info btn-sm">Imprimir</a>

                            </td>
                        </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
    </section>
</main>
@endsection
