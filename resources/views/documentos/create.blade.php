@extends('layouts.main')
@section('titulo', $titulo)
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Nuevo Documento</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Agregar Documento</h5>
                <form action="{{ route('documentos.store') }}" method="POST">
                @csrf
                @include('documentos.form')
                <button type="submit" class="btn btn-success mt-2">Guardar</button>
                     <a href="{{ route('documentos') }}" class="btn btn-secondary mt-2">Cancelar</a>
                </form>               
              </div>
            </div>
  
          </div>
        </div>
      </section>
</main>
@endsection