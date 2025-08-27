@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Panel Administración Caja</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <hr>

                        <!-- Botón crear nueva caja -->
                        <a href="{{ route('cajas.create') }}" class="btn btn-primary mb-3">
                            <i class="fa-solid fa-circle-plus"></i> Crear Nueva Caja
                        </a>    

                        <!-- Mensaje de éxito -->
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <hr>

                        <!-- Tabla de cajas -->
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
                                @foreach ($cajas as $caja)
                                    <tr>
                                        <td>{{ $caja->nombre }}</td>
                                        <td>{{ $caja->codigo }}</td>
                                        <td>{{ $caja->porcentaje }}</td>
                                        <td>
                                            <a href="{{ route('cajas.edit', $caja) }}" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="{{ route('cajas.destroy', $caja) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta caja?')">
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
