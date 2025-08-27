@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Recibo</h1>
    </div>

    <form action="{{ route('recibos.update', $recibo->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Número de Documento</label>
            <input type="text" class="form-control" value="{{ $recibo->usuarioExterno->numero }}" disabled>
            <input type="hidden" name="usuario_externo_id" value="{{ $recibo->usuarioExterno->id }}">
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre Completo</label>
                <input type="text" class="form-control"
                       value="{{ $recibo->usuarioExterno->primer_nombre }} {{ $recibo->usuarioExterno->primer_apellido }}"
                       disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label">Fecha de Afiliación</label>
                <input type="text" class="form-control" value="{{ $recibo->usuarioExterno->fecha_afiliacion }}" disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label">Salario</label>
                <input type="text" class="form-control" value="{{ number_format($recibo->usuarioExterno->sueldo, 0) }}" disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label">Días a Liquidar</label>
                <input type="text" class="form-control" value="{{ $recibo->dias_liquidar }}" disabled>
            </div>
        </div>

        <hr>
        <h5>Valores Calculados</h5>

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">EPS</label>
                <input type="text" class="form-control" value="{{ $recibo->valor_eps }}" disabled>
            </div>
            <div class="col-md-3">
                <label class="form-label">ARL</label>
                <input type="text" class="form-control" value="{{ $recibo->valor_arl }}" disabled>
            </div>
            <div class="col-md-3">
                <label class="form-label">Pensión</label>
                <input type="text" class="form-control" value="{{ $recibo->valor_pension }}" disabled>
            </div>
            <div class="col-md-3">
                <label class="form-label">Caja</label>
                <input type="text" class="form-control" value="{{ $recibo->valor_caja }}" disabled>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-3">
                <label class="form-label">Administración</label>
                <input type="text" class="form-control" value="{{ $recibo->valor_admon }}" disabled>
            </div>
            <div class="col-md-3">
                <label class="form-label">Exequial</label>
                <input type="text" class="form-control" value="{{ $recibo->valor_exequial }}" disabled>
            </div>
            <div class="col-md-3">
                <label class="form-label">Mora</label>
                <input type="text" class="form-control" value="{{ $recibo->valor_mora }}" disabled>
            </div>
            <div class="col-md-3">
                <label class="form-label">Otros Servicios</label>
                <input type="number" step="100" min="0" name="otros_servicios" class="form-control"
                       value="{{ old('otros_servicios', $recibo->otros_servicios) }}" required>
            </div>
        </div>

        <div class="mt-4">
            <label class="form-label"><strong>Total</strong></label>
            <input type="text" class="form-control form-control-lg fw-bold" value="{{ $recibo->total }}" disabled>
        </div>

        <div class="mt-4 row">
            <div class="col-md-6">
                <label for="fecha" class="form-label">Fecha del Recibo</label>
                <input type="date" name="fecha" id="fecha" class="form-control"
                       value="{{ old('fecha', $recibo->fecha->format('Y-m-d')) }}" required>
            </div>

            <div class="col-md-6">
                <label for="novedad" class="form-label">Novedad</label>
                <select name="novedad" id="novedad" class="form-control">
                    <option value="">— Sin novedad —</option>
                    <option value="Ingreso" {{ old('novedad', $recibo->novedad) === 'Ingreso' ? 'selected' : '' }}>Ingreso</option>
                    <option value="Retiro"  {{ old('novedad', $recibo->novedad) === 'Retiro'  ? 'selected' : '' }}>Retiro</option>
                </select>
            </div>
        </div>

        <div class="mt-3" id="campoFechaRetiro" style="{{ old('novedad', $recibo->novedad) === 'Retiro' ? '' : 'display: none;' }}">
            <label for="fecha_retiro" class="form-label">Fecha de Retiro</label>
            <input type="date" name="fecha_retiro" id="fecha_retiro" class="form-control"
                   value="{{ old('fecha_retiro', optional($recibo->fecha_retiro)->format('Y-m-d')) }}"
                   {{ old('novedad', $recibo->novedad) === 'Retiro' ? '' : 'disabled' }}>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Actualizar Recibo</button>
            <a href="{{ route('recibos') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectNovedad = document.getElementById('novedad');
    const campo = document.getElementById('campoFechaRetiro');
    const campoRetiro = document.getElementById('fecha_retiro');

    function toggleRetiro() {
        if (selectNovedad.value === 'Retiro') {
            campo.style.display = 'block';
            campoRetiro.removeAttribute('disabled');
        } else {
            campo.style.display = 'none';
            campoRetiro.value = '';
            campoRetiro.setAttribute('disabled', true);
        }
    }

    // Inicializa según el valor actual y escucha cambios
    toggleRetiro();
    selectNovedad.addEventListener('change', toggleRetiro);
});
</script>
@endpush
