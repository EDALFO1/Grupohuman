@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Subtipos de Cotizantes</h1>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <hr>

                <a href="{{ route('subtipo_cotizantes.create') }}" class="btn btn-primary mb-3">
                    <i class="fa-solid fa-circle-plus"></i> Crear Nuevo
                </a>

                @if(session('success'))
                    <div class="alert alert-success mt-2">{{ session('success') }}</div>
                @endif

                <hr>

                <table class="table datatable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subtipos as $subtipo)
                            <tr>
                                <td>{{ $subtipo->codigo }}</td>
                                <td>{{ $subtipo->nombre }}</td>
                                <td class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('subtipo_cotizantes.edit', $subtipo) }}" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>
                                    <form action="{{ route('subtipo_cotizantes.destroy', $subtipo) }}" method="POST" onsubmit="return confirm('¿Eliminar?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </section>
</main>
@endsection
