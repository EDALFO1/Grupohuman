@extends('layouts.main')

@section('titulo', 'Editar Remisión')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Remisión</h1>
    </div>

    <form action="{{ route('remisiones.update', $remision->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Número de Documento</label>
            <input type="text" class="form-control" value="{{ $remision->usuarioExterno->numero }}" disabled>
            <input type="hidden" name="usuario_externo_id" value="{{ $remision->usuarioExterno->id }}">
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" value="{{ $remision->usuarioExterno->primer_nombre }} {{ $remision->usuarioExterno->primer_apellido }}" disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label">Fecha de Afiliación</label>
                <input type="text" class="form-control" value="{{ $remision->usuarioExterno->fecha_afiliacion }}" disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label">Salario</label>
                <input type="text" class="form-control" value="{{ number_format($remision->usuarioExterno->sueldo, 0) }}" disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label">Días a Liquidar</label>
                <input type="text" class="form-control" value="{{ $remision->dias_liquidar }}" disabled>
            </div>
        </div>

        <hr>
        <h5>Valores Calculados</h5>

        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">EPS</label><input type="text" class="form-control" value="{{ $remision->valor_eps }}" disabled></div>
            <div class="col-md-3"><label class="form-label">ARL</label><input type="text" class="form-control" value="{{ $remision->valor_arl }}" disabled></div>
            <div class="col-md-3"><label class="form-label">Pensión</label><input type="text" class="form-control" value="{{ $remision->valor_pension }}" disabled></div>
            <div class="col-md-3"><label class="form-label">Caja</label><input type="text" class="form-control" value="{{ $remision->valor_caja }}" disabled></div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-3"><label class="form-label">Administración</label><input type="text" class="form-control" value="{{ $remision->valor_admon }}" disabled></div>
            <div class="col-md-3"><label class="form-label">Exequial</label><input type="text" class="form-control" value="{{ $remision->valor_exequial }}" disabled></div>
            <div class="col-md-3"><label class="form-label">Mora</label><input type="text" class="form-control" value="{{ $remision->valor_mora }}" disabled></div>
            <div class="col-md-3">
    <label class="form-label">Otros Servicios</label>
    <input type="number" step="100" min="0" name="otros_servicios" class="form-control" value="{{ old('otros_servicios', $remision->otros_servicios) }}" required>
</div>

        </div>

        <div class="mt-4">
            <label class="form-label"><strong>Total</strong></label>
            <input type="text" class="form-control form-control-lg fw-bold" value="{{ $remision->total }}" disabled>
        </div>

        <div class="mt-4 row">
            <div class="col-md-6">
                <label for="fecha" class="form-label">Fecha de la Remisión</label>
                <input type="date" name="fecha" class="form-control" value="{{ old('fecha', $remision->fecha->format('Y-m-d')) }}" required>
            </div>

            <div class="col-md-6">
                <label for="novedad" class="form-label">Novedad</label>
                <select name="novedad" id="novedad" class="form-control" required>
                    <option value="Ingreso" {{ $remision->novedad == 'Ingreso' ? 'selected' : '' }}>Ingreso</option>
                    <option value="Retiro" {{ $remision->novedad == 'Retiro' ? 'selected' : '' }}>Retiro</option>
                </select>
            </div>
        </div>

        <div class="mt-3" id="campoFechaRetiro" style="{{ $remision->novedad === 'Retiro' ? '' : 'display: none;' }}">
            <label for="fecha_retiro" class="form-label">Fecha de Retiro</label>
            <input type="date" name="fecha_retiro" id="fecha_retiro" class="form-control" value="{{ old('fecha_retiro', optional($remision->fecha_retiro)->format('Y-m-d')) }}">
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Actualizar Remisión</button>
            <a href="{{ route('remisiones') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</main>
@endsection

@push('scripts')
<script>
document.getElementById('novedad').addEventListener('change', function () {
    const campo = document.getElementById('campoFechaRetiro');
    const campoRetiro = document.getElementById('fecha_retiro');

    if (this.value === 'Retiro') {
        campo.style.display = 'block';
        campoRetiro.removeAttribute('disabled');
    } else {
        campo.style.display = 'none';
        campoRetiro.value = '';
        campoRetiro.setAttribute('disabled', true);
    }
});
</script>
@endpush
