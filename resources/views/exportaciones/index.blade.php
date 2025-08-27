@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Exportaciones</h1>
  </div>

  <section class="section">
    <div class="container-fluid">

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
      @endif
      @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
      @endif

      @forelse ($grouped as $periodo => $items)
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Período:</strong> <span>{{ $periodo }}</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Recibos</th>
                    <th>Total</th>
                    <th>Creado</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($items as $batch)
                    <tr>
                      <td>{{ $batch->id }}</td>
                      <td>{{ $batch->codigo ?? '—' }}</td>
                      <td>{{ $batch->recibos_count }}</td>
                      <td>${{ number_format($batch->total, 0, ',', '.') }}</td>
                      <td>{{ optional($batch->created_at)->format('Y-m-d H:i') }}</td>
                      <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary"
                           href="{{ route('exportaciones.descargar', $batch) }}">
                          Descargar Excel
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @empty
        <div class="alert alert-info">Aún no hay exportaciones.</div>
      @endforelse

      <a href="{{ route('recibos') }}" class="btn btn-secondary">Volver a recibos</a>
    </div>
  </section>
</main>
@endsection
