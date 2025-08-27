@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Panel Administración De Empresa Externa</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <hr>

                        <!-- Botón crear nueva empresa -->
                        <a href="{{ route('empresa_externas.create') }}" class="btn btn-primary mb-3">
                            <i class="fa-solid fa-circle-plus"></i> Crear Nueva Empresa
                        </a>    

                        <!-- Mensaje de éxito -->
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <hr>

                        <!-- Tabla de empresas externas -->
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Contacto</th>
                                    <th>Activo</th>
                                    <th>Documento</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($empresa_externas as $empresa)
                                    <tr>
                                        <td>{{ $empresa->numero }}</td>
                                        <td>{{ $empresa->nombre }}</td>
                                        <td>{{ $empresa->direccion }}</td>
                                        <td>{{ $empresa->telefono }}</td>
                                        <td>{{ $empresa->contacto }}</td>
                                        <td>{{ $empresa->activo ? 'Sí' : 'No' }}</td>
                                        <td>{{ $empresa->documento->nombre ?? 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('empresa_externas.edit', $empresa->id) }}" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="{{ route('empresa_externas.destroy', $empresa->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">
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
