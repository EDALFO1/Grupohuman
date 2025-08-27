@extends('layouts.main')
@section('contenido')

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Listado de Remisiones</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <hr>

                        <a href="{{ route('remisiones.create') }}" class="btn btn-primary mb-3">
                            <i class="fa-solid fa-circle-plus"></i> Crear Nueva Remisión
                        </a>

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <hr>

                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Número Doc</th>
                                    <th>Días</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($remisiones as $remision)
                                    <tr>
                                        <td>{{ $remision->numero }}</td>
                                        <td>{{ \Carbon\Carbon::parse($remision->fecha)->format('d/m/Y') }}</td>
                                        <td>{{ $remision->usuarioExterno->primer_nombre }} {{ $remision->usuarioExterno->primer_apellido }}</td>
                                        <td>{{ $remision->usuarioExterno->numero }}</td>
                                        <td>{{ $remision->dias_liquidar }}</td>
                                        <td>${{ number_format($remision->total, 2, ',', '.') }}</td>
                                        <td class="d-flex flex-wrap gap-1">
                                            <a href="{{ route('remisiones.edit', $remision->id) }}" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="{{ route('remisiones.imprimir', $remision->id) }}" class="btn btn-info btn-sm" target="_blank">
                                                <i class="fas fa-print"></i> Imprimir
                                            </a>
                                            <form action="{{ route('remisiones.destroy', $remision->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta remisión?')" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Paginación --}}
                        <div class="d-flex justify-content-center mt-3">
                            {{ $remisiones->links() }}
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>
</main>

@endsection
