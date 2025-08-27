@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Usuario</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Agregar Usuario</h5>

                        <form action="{{ route('usuario_externos.store') }}" method="POST">
                            @csrf
                            {{-- Bloque de errores --}}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                         @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                         @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @include('usuario_externos.form')

                            <div class="text-end mt-3">
                                <button class="btn btn-primary" type="submit">Guardar</button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </section>
</main>
@endsection
