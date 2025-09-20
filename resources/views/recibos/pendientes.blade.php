@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Pendientes de recibo</h1>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <div class="card">
          <div class="card-body">

            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3 mb-2">
              <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="empresa_local_id" value="{{ $empresaId }}">
                <input type="month" name="periodo" value="{{ $periodo }}" class="form-control form-control-sm w-auto">
                <select name="per_page" class="form-select form-select-sm w-auto">
                  @foreach([10,20,50,100] as $pp)
                    <option value="{{ $pp }}" @selected(request('per_page',$items->perPage())==$pp)>{{ $pp }}</option>
                  @endforeach
                </select>
                <button class="btn btn-outline-primary btn-sm">Filtrar</button>
              </form>
              <a href="{{ route('recibos') }}" class="btn btn-secondary btn-sm">Volver a recibos</a>
            </div>

            <table class="table">
              <thead>
                <tr>
                  <th>Período</th>                  
                  <th>Documento</th>
                  <th>Nombre</th>
                  <th>Estado</th>
                  <th class="text-end">Valor estimado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                @foreach($items as $u)
                  <tr>
                    <td>{{ $periodo }}</td>                   
                    <td>{{ $u->numero }}</td>
                    <td>{{ ($u->primer_nombre ?? '') }} {{ ($u->primer_apellido ?? '') }}</td>
                    <td><span class="badge bg-success">Activo</span></td>
                    <td class="text-end">${{ number_format((int)($u->valor_pendiente ?? 0), 0, ',', '.') }}</td>
                    <td>
                      <a href="{{ route('recibos.create', ['usuario' => $u->id, 'periodo' => $periodo]) }}"
                         class="btn btn-primary btn-sm">Crear recibo</a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>

            <div class="d-flex justify-content-between align-items-center mt-2">
              <div class="small text-muted">
                Mostrando {{ $items->firstItem() }}–{{ $items->lastItem() }} de {{ $items->total() }} registros
              </div>
              {{ $items->links() }}
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>
</main>
@endsection
