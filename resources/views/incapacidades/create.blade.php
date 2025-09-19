@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>{{ $titulo }}</h1></div>

  <div class="card">
    <div class="card-body">
      <form class="mt-3" method="post" action="{{ route('incapacidades.store') }}">
        @csrf
        @include('incapacidades.form', [
          'mode' => 'create',
          'incapacidad' => null,
          'estados' => $estados,
          'entidadTipos' => $entidadTipos,
          'epsList' => $epsList,
          'arlList' => $arlList,
        ])
      </form>
    </div>
  </div>
</main>
@endsection
