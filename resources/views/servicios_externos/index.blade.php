@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>{{ $titulo }}</h1>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <div class="card">
          <div class="card-body">
            <a href="{{ route('servicios-externos.create') }}" class="btn btn-primary mt-3 mb-3">
              <i class="fa-solid fa-circle-plus"></i> Nuevo Servicio
            </a>

            @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Tipo</th>
                  <th>URL de Login</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($servicios as $servicio)
                  <tr>
                    <td>{{ $servicio->nombre }}</td>
                    <td>{{ $servicio->tipo }}</td>
                    <td>
                      @if($servicio->url_login)
                        <a href="{{ $servicio->url_login }}" target="_blank">{{ $servicio->url_login }}</a>
                      @else
                        —
                      @endif
                    </td>
                    <td class="d-flex gap-1">
                      <a href="{{ route('servicios-externos.edit', $servicio) }}" class="btn btn-warning btn-sm">Editar</a>
                      <form action="{{ route('servicios-externos.destroy', $servicio) }}" method="POST" onsubmit="return confirm('¿Deseas eliminar este servicio?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">Eliminar</button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>

            <div class="mt-3">
              {{ $servicios->links() }}
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>
@endsection
