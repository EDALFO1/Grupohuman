@extends('layouts.main')
@section('titulo', $titulo)
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Eps</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Agregar Eps</h5>
                  <form action="{{ route('eps.store') }}" method="POST">
                     @csrf
                     @include('eps.form')
                     <button type="submit" class="btn btn-primary">Guardar</button>
                     <a href="{{ route('eps') }}" class="btn btn-secondary">Cancelar</a>
                  </form>   
              </div>
            </div>
  
          </div>
        </div>
      </section>
</main>
@endsection