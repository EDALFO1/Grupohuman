@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>Crear Servicio Externo</h1></div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Nuevo Servicio</h5>

            <form action="{{ route('servicios-externos.store') }}" method="POST">
              @csrf
              @include('servicios_externos.form', ['servicio' => $servicio])
              <button type="submit" class="btn btn-success">Guardar</button>
              <a href="{{ route('servicios-externos.index') }}" class="btn btn-secondary">Volver</a>
            </form>

          </div>
        </div>
      </div>
    </div>
  </section>
</main>
@endsection
