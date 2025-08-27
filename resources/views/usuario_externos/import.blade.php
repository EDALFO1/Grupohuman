@extends('layouts.main')



@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Importar Usuarios Externos</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('partial_success'))
        <div class="alert alert-warning">{{ session('partial_success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Errores:</strong>
            <ul>
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session()->has('failures'))
        <div class="alert alert-danger">
            <strong>Filas con errores:</strong>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Fila</th>
                        <th>Columna</th>
                        <th>Mensaje</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (session('failures') as $failure)
                        <tr>
                            <td>{{ $failure->row() }}</td>
                            <td>{{ $failure->attribute() }}</td>
                            <td>{{ implode(', ', $failure->errors()) }}</td>
                            <td>{{ $failure->values()[$failure->attribute()] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form action="{{ route('usuario_externos.import.do') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="archivo" class="form-label">Archivo Excel</label>
            <input type="file" name="archivo" id="archivo" class="form-control" required>
        </div>
        <button class="btn btn-primary">Importar</button>
    </form>
</main>
@endsection
