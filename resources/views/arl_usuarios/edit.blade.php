@extends('layouts.main')
@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>Editar usuario solo ARL</h1></div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-3">
        <form action="{{ route('arl-usuarios.update', $arlUsuario) }}" method="POST">
          @csrf @method('PUT')

          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
          @endif

          @include('arl_usuarios.form', ['arlUsuario' => $arlUsuario])

          <div class="text-end mt-3">
            <a href="{{ route('arl-usuarios.index') }}" class="btn btn-secondary">Volver</a>
            <button class="btn btn-primary" type="submit">Actualizar</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>
@endsection
