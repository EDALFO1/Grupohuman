@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Arl</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Agregar Arl</h5>

                        <form action="{{ route('arls.store') }}" method="POST">
                            @csrf
                            @include('arls.form', ['arl' => $arl])

                            <button type="submit" class="btn btn-success">Guardar</button>
                            <a href="{{ route('arls') }}" class="btn btn-secondary">Volver</a>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>
</main>
@endsection
