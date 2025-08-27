@extends('layouts.main')
@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>{{ $titulo }}</h1></div>

  <section class="section">
    <div class="card">
      <div class="card-body">

        <a href="{{ route('valores.create') }}" class="btn btn-primary my-3">Crear Valores</a>

        @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Empresa</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Salario</th>
                <th>Administración</th>
                <th>Activa</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($valores as $v)
                <tr>
                  <td>{{ $v->empresaLocal->nombre ?? '—' }}</td>
                  <td>{{ $v->fecha_inicio?->format('Y-m-d') }}</td>
                  <td>{{ $v->fecha_fin?->format('Y-m-d') ?? '—' }}</td>
                  <td>${{ number_format((float)$v->salario, 2) }}</td>
                  <td>${{ number_format((float)$v->administracion, 2) }}</td>
                  <td>
                    <span class="badge {{ $v->activa ? 'bg-success' : 'bg-secondary' }}">
                      {{ $v->activa ? 'Sí' : 'No' }}
                    </span>
                  </td>
                  <td class="d-flex gap-1">
                    <a href="{{ route('valores.edit',$v) }}" class="btn btn-warning btn-sm">Editar</a>
                    <form action="{{ route('valores.destroy',$v) }}" method="POST"
                          onsubmit="return confirm('¿Eliminar este registro?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-danger btn-sm">Eliminar</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="mt-3">{{ $valores->links() }}</div>
      </div>
    </div>
  </section>
</main>
@endsection
