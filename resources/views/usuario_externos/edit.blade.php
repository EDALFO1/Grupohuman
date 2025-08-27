@extends('layouts.main')
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Caja</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Editar Caja</h5>
                   <form action="{{ route('usuario_externos.update', $usuarioExterno->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            {{-- Bloque de errores --}}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @include('usuario_externos.form', ['usuarioExterno' => $usuarioExterno])

                            <div class="text-end mt-3">
                                <a href="{{ route('usuario_externos') }}" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Actualizar</button>
                            </div>
                    </form>
              </div>
            </div>
  
          </div>
        </div>
      </section>

</main>
@endsection
