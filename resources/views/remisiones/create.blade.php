@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>Crear Remisión</h1></div>

  <section class="section">
    <div class="card">
      <div class="card-body">

        <form action="{{ route('remisiones.store') }}" method="POST" class="mt-3">
          @csrf

          {{-- Errores --}}
          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $err)
                  <li>{{ $err }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          {{-- Aquí pega tu snippet de campos (numero, buscar, datosUsuario, etc.) --}}
          @include('remisiones._form') {{-- crea este partial con tu gran bloque HTML+JS --}}

          <div class="text-end mt-3">
            <a href="{{ route('remisiones') }}" class="btn btn-secondary">Volver</a>
            <button class="btn btn-primary">Guardar</button>
          </div>
        </form>

      </div>
    </div>
  </section>
</main>
@endsection


