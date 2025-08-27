@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Usuario</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Editar Usuario</h5>

                        <form action="{{ route('usuarios.update', $item->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre del Usuario</label>
                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="form-control"
                                    value="{{ old('name', $item->name) }}"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email de Usuario</label>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="form-control"
                                    value="{{ old('email', $item->email) }}"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol del Usuario</label>
                                <select name="rol" id="rol" class="form-select" required>
                                    <option value="">Selecciona el rol</option>
                                    <option value="admin" {{ old('rol', $item->rol) === 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="usuario" {{ old('rol', $item->rol) === 'usuario' ? 'selected' : '' }}>Invitado</option>
                                </select>
                            </div>

                            <button class="btn btn-warning mt-2" type="submit">Actualizar</button>
                            <a href="{{ route('usuarios') }}" class="btn btn-info mt-2">Cancelar</a>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </section>
</main>
@endsection
