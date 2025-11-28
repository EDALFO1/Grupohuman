@extends('layouts.main')

@section('titulo', $titulo ?? 'Notas')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Notas / Tareas</h1>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <div class="card">
          <div class="card-body">
            <hr>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <form class="d-flex" method="GET" action="{{ route('notas.index') }}">
                <input type="text" name="buscar" class="form-control me-2"
                       value="{{ request('buscar') }}" placeholder="Buscar...">

                <select name="estado" class="form-select me-2">
                  <option value="">Todos</option>
                  <option value="pendiente"   @selected(request('estado')=='pendiente')>Pendiente</option>
                  <option value="en_proceso"  @selected(request('estado')=='en_proceso')>En proceso</option>
                  <option value="resuelto"    @selected(request('estado')=='resuelto')>Resuelto</option>
                  <option value="cancelado"   @selected(request('estado')=='cancelado')>Cancelado</option>
                </select>

                <button class="btn btn-primary">Filtrar</button>
              </form>

              <a href="{{ route('notas.create') }}" class="btn btn-success">
                <i class="fa-solid fa-circle-plus"></i> Nueva Nota
              </a>
            </div>

            @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
              <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <hr>

            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Vence</th>
                    <th>Creado por</th>
                    <th>Creación</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($notas as $nota)
                    <tr>
                      <td>{{ $nota->titulo }}</td>
                      <td>{{ ucfirst(str_replace('_', ' ', $nota->tipo)) }}</td>
                      <td>
                        @php
                          $badge = match($nota->estado) {
                            'pendiente'   => 'warning',
                            'en_proceso'  => 'info',
                            'resuelto'    => 'success',
                            'cancelado'   => 'secondary',
                            default       => 'secondary',
                          };
                        @endphp
                        <span class="badge bg-{{ $badge }}">
                          {{ ucfirst(str_replace('_', ' ', $nota->estado)) }}
                        </span>
                      </td>
                      <td>{{ $nota->fecha_vencimiento?->format('Y-m-d') ?? '—' }}</td>
                      <td>{{ $nota->creador->name ?? '—' }}</td>
                      <td>{{ $nota->created_at?->format('Y-m-d H:i') }}</td>
                      <td class="d-flex gap-1">
                        <a href="{{ route('notas.edit', $nota) }}" class="btn btn-warning btn-sm">
                          Editar
                        </a>
                        <form action="{{ route('notas.destroy', $nota) }}" method="POST"
                              onsubmit="return confirm('¿Deseas eliminar esta nota?')">
                          @csrf
                          @method('DELETE')
                          <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="text-center">No hay notas registradas.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="mt-3">
              {{ $notas->links() }}
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>
@endsection
