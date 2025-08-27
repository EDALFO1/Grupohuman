@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Pension</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
  
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Editar Pension</h5>
                
                    <form action="{{ route('pensions.update', $pension) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('pensions.form', ['pension' => $pension])

                            <button type="submit" class="btn btn-primary">Actualizar</button>
                            <a href="{{ route('pensions') }}" class="btn btn-secondary">Volver</a>
                    </form>
              </div>
            </div>
  
          </div>
        </div>
      </section>

</main>
@endsection
