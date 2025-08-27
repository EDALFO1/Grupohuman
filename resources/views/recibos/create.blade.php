@extends('layouts.main')


@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Recibo</h1>
    </div>

    <section class="section">
        <div class="row">
          <div class="col-lg-12">

            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Nuevo Recibo</h5>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('recibos.store') }}">
                  @csrf
                  
                  @include('recibos.form')

                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="{{ route('recibos') }}" class="btn btn-secondary">Cancelar</a>
                </form>

              </div>
            </div>

          </div>
        </div>
    </section>
</main>
@endsection
