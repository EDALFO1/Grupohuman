@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Panel Administración EPS</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <hr>

                        <!-- Botón crear nueva EPS -->
                        <a href="{{ route('eps.create') }}" class="btn btn-primary mb-3">
                            <i class="fa-solid fa-circle-plus"></i> Crear Nueva EPS
                        </a>    

                        <!-- Mensaje de éxito -->
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <hr>

                        <!-- Tabla de EPS -->
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Código</th>
                                    <th>Porcentaje</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($eps as $item)
                                    <tr>
                                        <td>{{ $item->nombre }}</td>
                                        <td>{{ $item->codigo }}</td>
                                        <td>{{ $item->porcentaje }}</td>
                                        <td>
                                            <a href="{{ route('eps.edit', $item->id) }}" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="{{ route('eps.destroy', $item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta EPS?')">
                                                    Eliminar
                                                </button>
                                            </form>
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
