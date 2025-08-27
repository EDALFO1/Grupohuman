@extends('layouts.main')
@section('titulo', $titulo)
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Nueva Empresa Local</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Agregar Empresa</h5>
                 <form action="{{ route('empresa_local.store') }}" method="POST">
                 @csrf
                 @include('empresa_local.form')
                 <button type="submit" class="btn btn-primary">Guardar</button>
                   <a href="{{ route('empresa_local') }}" class="btn btn-secondary">Cancelar</a>
                </form>             
              </div>
            </div>
  
          </div>
        </div>
      </section>
</main>
@endsection