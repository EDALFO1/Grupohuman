@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Subtipo Cotizante</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Editar Subtipo</h5>

                <form action="{{ route('subtipo_cotizantes.update', $subtipo_cotizante) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('subtipo_cotizantes.form', ['subtipo_cotizante' => $subtipo_cotizante])
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                        <a href="{{ route('subtipo_cotizantes') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>

              </div>
            </div>
          </div>
        </div>
    </section>
</main>
@endsection
