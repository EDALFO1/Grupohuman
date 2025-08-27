@extends('layouts.main')
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Asesor</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Agregar Asesor</h5>
                <form action="{{ route('asesores.store') }}" method="POST">
                   @csrf
                   @include('asesores.form')
                    
                </form>           
              </div>
            </div>
  
          </div>
        </div>
      </section>
</main>
@endsection