@extends('layouts.main')
@section('titulo', $titulo)
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Nueva Pension</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Agregar Pension</h5>
                   <form action="{{ route('pensions.store') }}" method="POST">
                   @csrf
                   @include('pensions.form')
                   <button type="submit" class="btn btn-success mt-2">Guardar</button>
                     <a href="{{ route('pensions') }}" class="btn btn-secondary mt-2">Cancelar</a>
                   </form>           
              </div>
            </div>
  
          </div>
        </div>
      </section>
</main>
@endsection