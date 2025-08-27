@extends('layouts.main')
@section('titulo', $titulo)
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Empresa</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Editar Empresa</h5>
                  <form action="{{ route('empresa_local.update', $empresa->id)}}" method="POST">
                  @csrf @method('PUT')
                  @include('empresa_local.form')
                  <button type="submit" class="btn btn-primary">Actualizar</button>
                       <a href="{{ route('empresa_local') }}" class="btn btn-secondary">Volver</a>
                  </form>
               
              </div>
            </div>
  
          </div>
        </div>
      </section>

</main>
@endsection

