@extends('layouts.main')

@section('contenido')

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Producto</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">

            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Nuevo Producto</h5>
                <form action="{{ route('productos.store') }}" method="POST">
                    @csrf
                    @include('productos.form')
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <a href="{{ route('productos') }}" class="btn btn-secondary">Cancelar</a>
                </form>
              </div>
            </div>

          </div>
        </div>
    </section>
</main>
@endsection
