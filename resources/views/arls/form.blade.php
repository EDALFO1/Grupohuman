<div class="mb-3">
  <label for="nombre" class="form-label fw-bold">Nombre</label>
  <input type="text" id="nombre" name="nombre"
         class="form-control @error('nombre') is-invalid @enderror"
         value="{{ old('nombre', $arl->nombre ?? '') }}">
  @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="codigo" class="form-label fw-bold">Código</label>
  <input type="text" id="codigo" name="codigo"
         class="form-control @error('codigo') is-invalid @enderror"
         value="{{ old('codigo', $arl->codigo ?? '') }}">
  @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="nivel" class="form-label fw-bold">Nivel (1 a 5)</label>
  <select id="nivel" name="nivel" class="form-select @error('nivel') is-invalid @enderror">
    @for($i=1;$i<=5;$i++)
      <option value="{{ $i }}" @selected(old('nivel', $arl->nivel ?? '') == $i)>{{ $i }}</option>
    @endfor
  </select>
  @error('nivel') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="actividad_economica" class="form-label fw-bold">Actividad Económica (7 dígitos)</label>
  <input type="text" id="actividad_economica" name="actividad_economica"
         class="form-control @error('actividad_economica') is-invalid @enderror"
         value="{{ old('actividad_economica', $arl->actividad_economica ?? '') }}"
         placeholder="Ej: 0111000">
  @error('actividad_economica') <div class="invalid-feedback">{{ $message }}</div> @enderror
  
</div>

<div class="mb-3">
  <label for="porcentaje" class="form-label fw-bold">Porcentaje</label>
  <input type="number" id="porcentaje" name="porcentaje" step="0.0001" min="0" max="100"
         class="form-control @error('porcentaje') is-invalid @enderror"
         value="{{ old('porcentaje', $arl->porcentaje ?? '') }}">
  @error('porcentaje') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
