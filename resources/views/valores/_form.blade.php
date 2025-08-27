<div class="row mb-3">
  <div class="col-md-6">
    <label class="form-label">Empresa Local</label>
    <input type="text" class="form-control" value="{{ $empresaActual->nombre ?? '—' }}" readonly>
    <input type="hidden" name="empresa_local_id" value="{{ $empresaActual->id ?? '' }}">
  </div>
  <div class="col-md-3">
    <label class="form-label">Fecha Inicio</label>
    <input type="date" name="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror"
           value="{{ old('fecha_inicio', $valor->fecha_inicio?->format('Y-m-d')) }}">
    @error('fecha_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="col-md-3">
    <label class="form-label">Fecha Fin (opcional)</label>
    <input type="date" name="fecha_fin" class="form-control @error('fecha_fin') is-invalid @enderror"
           value="{{ old('fecha_fin', $valor->fecha_fin?->format('Y-m-d')) }}">
    @error('fecha_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-4">
    <label class="form-label">Salario</label>
    <input type="number" step="0.01" name="salario" class="form-control @error('salario') is-invalid @enderror"
           value="{{ old('salario', $valor->salario) }}">
    @error('salario') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="col-md-4">
    <label class="form-label">Administración</label>
    <input type="number" step="0.01" name="administracion" class="form-control @error('administracion') is-invalid @enderror"
           value="{{ old('administracion', $valor->administracion) }}">
    @error('administracion') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="col-md-4">
    <label class="form-label">Activa</label>
    <select name="activa" class="form-select @error('activa') is-invalid @enderror">
      @php $activa = old('activa', (int)($valor->activa ?? 1)); @endphp
      <option value="1" {{ $activa==1 ? 'selected':'' }}>Sí</option>
      <option value="0" {{ $activa==0 ? 'selected':'' }}>No</option>
    </select>
    @error('activa') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
</div>
