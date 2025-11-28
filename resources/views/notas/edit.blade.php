@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Editar Nota</h1>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Editar Nota / Tarea</h5>

            <form action="{{ route('notas.update', $nota) }}" method="POST">
              @csrf
              @method('PUT')

              @include('notas.form', ['nota' => $nota])

              <button type="submit" class="btn btn-primary">Actualizar</button>
              <a href="{{ route('notas.index') }}" class="btn btn-secondary">Volver</a>
            </form>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>
@endsection
