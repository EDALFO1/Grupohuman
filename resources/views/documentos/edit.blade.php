@extends('layouts.main')
@section('titulo', $titulo)
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Documento</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Editar Documento</h5>
                  <form action="{{ route('documentos.update', $documento) }}" method="POST">
                 @csrf @method('PUT')
                 @include('documentos.form')
                 <button type="submit" class="btn btn-primary">Actualizar</button>
                     <a href="{{ route('documentos') }}" class="btn btn-secondary">Volver</a>
                 </form>
               
              </div>
            </div>
  
          </div>
        </div>
      </section>

</main>
@endsection
