@extends('layouts.main')
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Asesor</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Editar Asesor</h5>
                    <form action="{{ route('asesores.update', $asesor) }}" method="POST">
                     @csrf
                     @method('PUT')
                     @include('asesores.form', ['asesor' => $asesor])
                    </form>
              </div>
            </div>
  
          </div>
        </div>
      </section>

</main>
@endsection
