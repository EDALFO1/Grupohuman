@extends('layouts.main')


@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Seleccionar Empresa</h1>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body pt-4">

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route('guardar.empresa') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="empresa_local_id" class="form-label">Seleccione la empresa con la que desea trabajar:</label>
                        <select name="empresa_local_id" id="empresa_local_id" class="form-select" required>
                            <option value="">-- Seleccione --</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Ingresar</button>
                </form>

            </div>
        </div>
    </section>
</main>
@endsection
