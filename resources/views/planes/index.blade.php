@extends('layouts.main')

@section('titulo', 'Planes (valores actuales)')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>Planes</h1></div>
<!--
  <div class="alert alert-info">
    <strong>Parámetros:</strong>
    SMMLV: <b>${{ number_format($componentes['smmlv'], 0, ',', '.') }}</b>,
    Administración: <b>${{ number_format($componentes['admin'], 0, ',', '.') }}</b>.
    <br>
    <small>Para actualizar: edita <code>.env</code> (PLANES_SMMLV, PLANES_ADMIN) o <code>config/planes.php</code> y ejecuta <code>php artisan optimize:clear</code>.</small>
  </div>
-->
  <div class="row">
    <div class="col-md-4 mb-3">
      <div class="card h-100">
        <div class="card-header">Componentes (redondeados ↑ al 100)</div>
        <div class="card-body">
          <ul class="mb-0">
            <li>EPS (4%): <b>${{ number_format($componentes['eps'], 0, ',', '.') }}</b></li>
            <li>CAJA (4%): <b>${{ number_format($componentes['caja'], 0, ',', '.') }}</b></li>
            <li>PENSIÓN (16%): <b>${{ number_format($componentes['pension'], 0, ',', '.') }}</b></li>
            @foreach($componentes['arl'] as $nivel => $val)
              <li>ARL {{ $nivel }}: <b>${{ number_format($val, 0, ',', '.') }}</b></li>
            @endforeach
            <li>ADMIN: <b>${{ number_format($componentes['admin'], 0, ',', '.') }}</b></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      @foreach($planes as $grupo => $items)
        <div class="card mb-3">
          <div class="card-header">{{ $grupo }}</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm mb-0 align-middle">
                <thead>
                  <tr>
                    <th>Plan</th>
                    <th class="text-end">Valor</th>
                    <th>Detalle</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($items as $pl)
                    <tr>
                      <td>{{ $pl['label'] }}</td>
                      <td class="text-end"><b>${{ number_format($pl['valor'], 0, ',', '.') }}</b></td>
                      <td>
                        @php $d = $pl['breakdown']; @endphp
                        @foreach($d as $k => $v)
                          <span class="badge bg-light text-dark me-1">{{ strtoupper($k) }}: ${{ number_format($v, 0, ',', '.') }}</span>
                        @endforeach
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</main>
@endsection
