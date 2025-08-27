@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Panel Administración De Empresa Local</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <hr>

                        <!-- Botón crear nueva empresa -->
                        <a href="{{ route('empresa_local.create') }}" class="btn btn-primary mb-3">
                            <i class="fa-solid fa-circle-plus"></i> Crear Nueva Empresa
                        </a>    

                        <!-- Mensaje de éxito -->
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <hr>

                        <!-- Tabla de empresas locales -->
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Número</th>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Contacto</th>
                                    <th>Activo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($empresas as $empresa)
                                    <tr>
                                        <td>{{ $empresa->documento->nombre ?? 'Sin documento' }}</td>
                                        <td>{{ $empresa->numero_documento }}</td>
                                        <td>{{ $empresa->nombre }}</td>
                                        <td>{{ $empresa->direccion }}</td>
                                        <td>{{ $empresa->telefono }}</td>
                                        <td>{{ $empresa->contacto }}</td>
                                        <td>{{ $empresa->activo ? 'Sí' : 'No' }}</td>
                                        <td>
                                            <a href="{{ route('empresa_local.edit', $empresa->id) }}" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="{{ route('empresa_local.destroy', $empresa->id) }}" method="POST" style="display:inline;">
                                                @csrf 
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminarla?')">
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
