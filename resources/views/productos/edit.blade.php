@extends('layouts.main')

@section('contenido')

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Producto</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">

            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Actualizar Producto</h5>
                <form action="{{ route('productos.update', $producto) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('productos.form', ['producto' => $producto])
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                    <a href="{{ route('productos') }}" class="btn btn-secondary">Volver</a>
                </form>
              </div>
            </div>

          </div>
        </div>
    </section>
</main>
@endsection
