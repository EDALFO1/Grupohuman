@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle"></div>
    <section class="section">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('subtipo_cotizantes.store') }}" method="POST">
        @csrf
        @include('subtipo_cotizantes.form')
        <button class="btn btn-primary">Guardar</button>
    </form>
            </div>
        </div>
    </section>
</main>
@endsection
