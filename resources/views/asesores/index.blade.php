@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Panel Administración Asesor</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <hr>

                        <!-- Botón crear nuevo asesor -->
                        <a href="{{ route('asesores.create') }}" class="btn btn-primary mb-3">
                            <i class="fa-solid fa-circle-plus"></i> Crear Nuevo Asesor
                        </a>    

                        <!-- Mensaje de éxito -->
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <hr>

                        <!-- Tabla de asesores -->
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>Tipo Documento</th>
                                    <th>Número</th>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($asesores as $asesor)
                                    <tr>
                                        <td>{{ $asesor->documento->nombre }}</td>
                                        <td>{{ $asesor->numero_documento }}</td>
                                        <td>{{ $asesor->nombre }}</td>
                                        <td>{{ $asesor->direccion }}</td>
                                        <td>{{ $asesor->telefono }}</td>
                                        <td>{{ $asesor->email }}</td>
                                        <td>
                                            <a href="{{ route('asesores.edit', $asesor) }}" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="{{ route('asesores.destroy', $asesor) }}" method="POST" style="display:inline-block;">
                                                @csrf 
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar asesor?')">Eliminar</button>
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
