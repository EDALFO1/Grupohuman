@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>Usuarios solo ARL</h1></div>

  <section class="section">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center pt-3">
          <form class="d-flex" method="GET" action="{{ route('arl-usuarios.index') }}">
            <input type="text" name="q" class="form-control me-2" value="{{ request('q') }}" placeholder="Buscar por número o nombre">
            <select name="per_page" class="form-select me-2" onchange="this.form.submit()">
              @foreach([10,25,50,100,200] as $n)
                <option value="{{ $n }}" {{ (int)request('per_page',10) === $n ? 'selected' : '' }}>{{ $n }}</option>
              @endforeach
            </select>
            <button class="btn btn-outline-secondary">Filtrar</button>
          </form>

          <a href="{{ route('arl-usuarios.create') }}" class="btn btn-primary">Nuevo</a>
        </div>

        @if(session('success'))
          <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif

        <div class="table-responsive mt-3">
          <table class="table">
            <thead>
              <tr>
                <th>Tipo Doc</th>
                <th>Número</th>
                <th>Nombre</th>
                <th>Fecha Ingreso</th>
                <th>Nivel ARL</th>
                <th>Empresa Externa</th>
                <th>Valor (ARL + Adm)</th>
                <th>Estado</th>
                <th>Retiro</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($usuarios as $u)
                <tr>
                  <td>{{ $u->documento->nombre ?? 'N/A' }}</td>
                  <td>{{ $u->numero }}</td>
                  <td>{{ $u->nombre }}</td>
                  <td>{{ optional($u->fecha_ingreso)->format('Y-m-d') }}</td>
                  <td>
                    {{ $u->arl->nombre ?? 'N/A' }}
                    @if(optional($u->arl)->nivel) (Nivel {{ $u->arl->nivel }}) @endif
                  </td>
                  <td>{{ $u->empresaExterna->nombre ?? 'N/A' }}</td>
                  <td>{{ number_format($u->valor, 0, ',', '.') }}</td>
                  <td>
                    <span class="badge {{ $u->estado ? 'bg-success' : 'bg-danger' }}">
                      {{ $u->estado ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td>{{ optional($u->fecha_retiro)->format('Y-m-d') }}</td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-warning" href="{{ route('arl-usuarios.edit', $u) }}">Editar</a>
                    <form class="d-inline" method="POST" action="{{ route('arl-usuarios.destroy', $u) }}"
                          onsubmit="return confirm('¿Eliminar registro?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-danger">Eliminar</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="10" class="text-center py-4">Sin registros</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{ $usuarios->onEachSide(1)->links() }}

      </div>
    </div>
  </section>
</main>
@endsection
