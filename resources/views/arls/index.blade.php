@extends('layouts.main')

@section('contenido')

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Panel Administración ARL</h1>
  </div>
  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <div class="card">
          <div class="card-body">
            <hr>

            <a href="{{ route('arls.create') }}" class="btn btn-primary mb-3">
              <i class="fa-solid fa-circle-plus"></i> Crear Nueva ARL
            </a>

            @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
              <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <hr>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Código</th>
                    <th>Nivel</th>
                    <th>Actividad Económica</th>
                    <th>Porcentaje</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($arls as $arl)
                    <tr>
                      <td>{{ $arl->nombre }}</td>
                      <td>{{ $arl->codigo }}</td>
                      <td>{{ $arl->nivel }}</td>
                      <td>{{ $arl->actividad_economica ?? '—' }}</td>
                      <td>{{ number_format($arl->porcentaje, 4) }}%</td>
                      <td class="d-flex gap-1">
                        <a href="{{ route('arls.edit', $arl) }}" class="btn btn-warning btn-sm">Editar</a>
                        <form action="{{ route('arls.destroy', $arl) }}" method="POST" onsubmit="return confirm('¿Deseas eliminar esta ARL?')" >
                          @csrf
                          @method('DELETE')
                          <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="mt-3">
              {{ $arls->links() }}
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>
@endsection
