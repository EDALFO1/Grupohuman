@extends('layouts.main')


@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>Usuarios por Período</h1></div>

  <form class="row g-3 mb-3" method="get">
    <div class="col-md-3">
      <label class="form-label">Empresa</label>
      <select name="empresa_local_id" class="form-select" onchange="this.form.submit()">
        <option value="">(Sesión)</option>
        @foreach($empresas as $e)
          <option value="{{ $e->id }}" {{ (int)$empresaId === $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Período</label>
      <input type="month" name="periodo" value="{{ $periodo }}" class="form-control" onchange="this.form.submit()">
    </div>
    <div class="col-md-3">
      <label class="form-label">Estado</label>
      <select name="estado" class="form-select" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="Activo"   {{ $estado==='Activo'?'selected':'' }}>Activo</option>
        <option value="Retirado" {{ $estado==='Retirado'?'selected':'' }}>Retirado</option>
      </select>
    </div>

    {{-- NUEVO: Por página --}}
    <div class="col-md-2">
      <label class="form-label">Por página</label>
      <select name="per_page" class="form-select" onchange="this.form.submit()">
        @foreach([10,20,50,100] as $n)
          <option value="{{ $n }}" {{ (int)request('per_page', $perPage ?? 20) === $n ? 'selected' : '' }}>
            {{ $n }}
          </option>
        @endforeach
      </select>
    </div>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Período</th>
            <th>Empresa</th>
            <th>Documento</th>
            <th>Nombre</th>
            <th>Estado</th>
            <th>Recibo</th>
            <th>Creado</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $it)
            <tr>
              <td>{{ $it->periodo }}</td>
              <td>{{ $it->empresaLocal?->nombre }}</td>
              <td>{{ $it->usuarioExterno?->numero }}</td>
              <td>{{ $it->usuarioExterno?->full_name }}</td>
              <td>
                <span class="badge {{ $it->estado==='Activo'?'bg-success':'bg-secondary' }}">
                  {{ $it->estado }}
                </span>
              </td>
              <td>
                @if($it->recibo_id)
                  <a href="{{ route('recibos') }}?id={{ $it->recibo_id }}">#{{ $it->recibo_id }}</a>
                @else
                  —
                @endif
              </td>
              <td>{{ $it->created_at?->format('Y-m-d H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center py-4">Sin datos</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    
        {{-- Footer de paginación inline (sin componente) --}}
    @php
        $first = $items->firstItem() ?? 0;
        $last  = $items->lastItem()  ?? 0;
        $total = method_exists($items, 'total') ? (int) $items->total() : null;
        $nf    = fn($n) => number_format((int) $n, 0, ',', '.');
    @endphp

    @if($items instanceof \Illuminate\Pagination\Paginator || $items instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
          @if(!is_null($total))
            Mostrando <strong>{{ $nf($first) }}</strong>–<strong>{{ $nf($last) }}</strong>
            de <strong>{{ $nf($total) }}</strong> registros
          @else
            Página <strong>{{ $items->currentPage() }}</strong>
          @endif
        </div>
        <div>
          {{ $items->onEachSide(1)->links() }}
        </div>
      </div>
    @endif


  </div>
</main>
@endsection
