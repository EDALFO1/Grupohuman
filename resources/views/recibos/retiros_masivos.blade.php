@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>Retiros masivos (pendientes de recibo)</h1></div>

  @if(session('info'))    <div class="alert alert-info">{{ session('info') }}</div>@endif
  @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div>@endif

  <form class="row g-3" method="get">
    <div class="col-md-3">
      <label class="form-label">Empresa</label>
      <select name="empresa_local_id" class="form-select" onchange="this.form.submit()">
        <option value="">(Sesión)</option>
        @foreach($empresas as $e)
          <option value="{{ $e->id }}" {{ (int)$empresaId===$e->id?'selected':'' }}>{{ $e->nombre }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Período (YYYY-MM)</label>
      <input type="month" name="periodo" class="form-control" value="{{ $periodo }}" onchange="this.form.submit()">
    </div>
    <div class="col-md-6 d-flex align-items-end">
      <div class="alert alert-secondary w-100 mb-0">
        Candidatos detectados: <strong>{{ $candidatos }}</strong>
      </div>
    </div>
  </form>

  <hr>

  <form method="post" action="{{ route('recibos.retirosMasivos.export') }}"
        onsubmit="return confirm('¿Seguro? Esta acción marcará inactivos a todos los usuarios pendientes y descargará el Excel.');">
    @csrf
    <input type="hidden" name="empresa_local_id" value="{{ $empresaId }}">
    <input type="hidden" name="periodo" value="{{ $periodo }}">
    <input type="hidden" name="confirm" value="1">
    <button class="btn btn-danger">
      Generar Excel de Retiros 1 día y marcar inactivos
    </button>
  </form>
</main>
@endsection
