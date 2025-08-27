@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>{{ $titulo }}</h1></div>

  <section class="section">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('valores.store') }}" method="POST" class="mt-3">
          @csrf

          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
              </ul>
            </div>
          @endif

          @include('valores._form', ['valor' => $valor, 'empresaActual' => $empresaActual])

          <div class="text-end">
            <a href="{{ route('valores.index') }}" class="btn btn-secondary">Volver</a>
            <button class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>
@endsection
