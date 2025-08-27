@extends('layouts.main')
@section('titulo', $titulo)
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
                   <form action="{{ route('cajas.update', $caja) }}" method="POST">
                   @csrf
                   @method('PUT')
                   @include('cajas.form')
                   <button type="submit" class="btn btn-primary">Actualizar</button>
                   <a href="{{ route('cajas') }}" class="btn btn-secondary">Cancelar</a>
                   </form>
              </div>
            </div>
  
          </div>
        </div>
      </section>

</main>
@endsection