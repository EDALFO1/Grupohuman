@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Crear Nota</h1>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Nueva Nota / Tarea</h5>

            <form action="{{ route('notas.store') }}" method="POST">
              @csrf

              @include('notas.form', ['nota' => $nota])

              <button type="submit" class="btn btn-success">Guardar</button>
              <a href="{{ route('notas.index') }}" class="btn btn-secondary">Volver</a>
            </form>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>
@endsection
