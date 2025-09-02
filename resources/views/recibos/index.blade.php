@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Listado de recibos</h1>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <div class="card">
          <div class="card-body">

            {{-- Alertas --}}
            @if (session('success'))
              <div class="alert alert-success mt-3">{{ session('success') }}</div>
            @endif
            @if (session('info'))
              <div class="alert alert-info mt-3">{{ session('info') }}</div>
            @endif
            @if (session('warning'))
              <div class="alert alert-warning mt-3">{{ session('warning') }}</div>
            @endif

            <hr>

            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
              <a href="{{ route('recibos.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-circle-plus"></i> Crear Nuevo Recibo
              </a>
              {{-- Exportar usuarios vigentes --}}
              <form action="{{ route('excel.usuarios_vigentes.descargar') }}" method="GET" class="d-inline">
                <input type="hidden" name="empresa_local_id" value="{{ $empresaIdActual }}">
                <input type="month"  name="periodo"          value="{{ $periodoActual }}" required>
                <button type="submit" class="btn btn-success">
                  <i class="bi bi-file-earmark-excel"></i> Exportar usuarios vigentes
                </button>
              </form>

              {{-- Preparar exportación por caja (crea el batch, marca y resetea contador) --}}
             @php $pend = (int)($pendientesCount ?? 0); @endphp

<form method="POST"
      action="{{ route('exportaciones.descargarPorCaja') }}"
      onsubmit="return confirm('¿Exportar por caja SOLO los recibos del mes seleccionado y marcarlos como exportados?');"
      class="d-flex flex-wrap align-items-center gap-2">
  @csrf
  <input type="hidden" name="empresa_local_id" value="{{ $empresaIdActual }}">
  <input type="month" name="periodo" class="form-control form-control-sm w-auto"
         value="{{ $periodoActual }}" required>
  <button type="submit" class="btn btn-success btn-sm" {{ ($pendientesCount ?? 0) == 0 ? 'disabled' : '' }}>
    Exportar por Caja (ZIP)
    @if(($pendientesCount ?? 0) > 0)
      <span class="badge bg-light text-dark">{{ $pendientesCount }}</span>
    @endif
  </button>
  <a href="{{ route('exportaciones.index') }}" class="btn btn-outline-secondary btn-sm">Ver exportaciones</a>
</form>




              

              
            </div>

            <hr>

            <table class="table datatable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>Número Doc</th>
                  <th>Días</th>
                  <th>Total</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($recibos as $recibo)
                  <tr>
                    <td>{{ $recibo->numero }}</td>
                    <td>{{ \Carbon\Carbon::parse($recibo->fecha)->format('d/m/Y') }}</td>
                    <td>{{ $recibo->usuarioExterno->primer_nombre }} {{ $recibo->usuarioExterno->primer_apellido }}</td>
                    <td>{{ $recibo->usuarioExterno->numero }}</td>
                    <td>{{ $recibo->dias_liquidar }}</td>
                    <td>${{ number_format($recibo->total, 2, ',', '.') }}</td>
                    <td class="d-flex flex-wrap gap-1">
                      <a href="{{ route('recibos.edit', $recibo->id) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Editar
                      </a>
                      <a href="{{ route('recibos.imprimir', $recibo->id) }}" class="btn btn-info btn-sm" target="_blank">
                        <i class="fas fa-print"></i> Imprimir
                      </a>
                      <form action="{{ route('recibos.destroy', $recibo->id) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm"
                                onclick="return confirm('¿Eliminar este recibo?');">
                          <i class="fas fa-trash-alt"></i> Eliminar
                        </button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>

            {{-- Paginación --}}
            <div class="d-flex justify-content-center mt-3">
              {{ $recibos->links() }}
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>
</main>
@endsection
