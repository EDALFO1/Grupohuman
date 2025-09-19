@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>{{ $titulo }}</h1></div>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif

  <div class="card">
    <div class="card-body">
      <form class="row g-2 mt-3 mb-3" method="get" action="{{ route('incapacidades.index') }}">
        <div class="col-md-3">
          <input type="text" name="documento" class="form-control" placeholder="Documento" value="{{ request('documento') }}">
        </div>
        <div class="col-md-3">
          <select name="estado" class="form-select">
            <option value="">-- Estado --</option>
            @foreach(['transcrita','radicada','aprobada','liquidada','rechazada','pagada'] as $es)
              <option value="{{ $es }}" @selected(request('estado')===$es)>{{ ucfirst($es) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <select name="vigencia" class="form-select">
            <option value="">-- Vigencia --</option>
            <option value="activas"  @selected(request('vigencia')==='activas')>Activas</option>
            <option value="cerradas" @selected(request('vigencia')==='cerradas')>Cerradas</option>
          </select>
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary">Filtrar</button>
          <a href="{{ route('incapacidades.create') }}" class="btn btn-success">Nueva</a>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Documento</th>
              <th>Nombre</th>
              <th>Empresa Local</th>
              <th>Empresa Externa</th>
              <th>Entidad</th>
              <th>Inicio</th>
              <th>Fin</th>
              <th>Días</th>
              <th>Estado</th>
              <th>Vigencia</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($incapacidades as $inc)
              <tr>
                <td>{{ $inc->id }}</td>
                <td>{{ $inc->documento }}</td>
                <td>{{ $inc->nombre }}</td>
                <td>{{ data_get($inc, 'empresaLocal.nombre') }}</td>
                <td>{{ data_get($inc, 'empresaExterna.nombre') }}</td>
                <td>{{ $inc->entidad_tipo }} - {{ $inc->entidad_nombre }}</td>
                <td>{{ $inc->fecha_inicio?->format('Y-m-d') }}</td>
                <td>{{ $inc->fecha_fin?->format('Y-m-d') }}</td>
                <td>{{ $inc->dias_solicitados }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($inc->estado) }}</span></td>
                <td>
                  @if($inc->cerrada)
                    <span class="badge bg-success">Cerrada</span>
                    @if($inc->fecha_cierre)<br><small>{{ $inc->fecha_cierre->format('Y-m-d') }}</small>@endif
                  @else
                    <span class="badge bg-warning text-dark">Activa</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('incapacidades.edit', $inc) }}" class="btn btn-sm btn-primary">Editar</a>
                  @if(!$inc->cerrada)
                    <form action="{{ route('incapacidades.cerrar', $inc) }}" method="post" class="d-inline" onsubmit="return confirm('¿Cerrar incapacidad?');">
                      @csrf
                      <button class="btn btn-sm btn-outline-success">Cerrar</button>
                    </form>
                  @endif
                  <form action="{{ route('incapacidades.destroy', $inc) }}" method="post" class="d-inline" onsubmit="return confirm('¿Eliminar?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Eliminar</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="12" class="text-center">Sin registros</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{ $incapacidades->links() }}
    </div>
  </div>
</main>
@endsection
