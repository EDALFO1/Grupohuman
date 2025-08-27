<div class="form-group">
    <label for="nombre" class="form-label fw-bold">Nombre</label>
    <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $pension->nombre ?? '') }}" class="form-control" required>
    @error('nombre')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="form-group">
    <label for="codigo" class="form-label fw-bold">Código</label>
    <input type="text" name="codigo" id="codigo" value="{{ old('codigo', $pension->codigo ?? '') }}" class="form-control" required>
    @error('codigo')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="form-group">
    <label for="porcentaje" class="form-label fw-bold">Porcentaje</label>
    <input type="number" step="0.0001" name="porcentaje" id="porcentaje" value="{{ old('porcentaje', $pension->porcentaje ?? '') }}" class="form-control" required>
    @error('porcentaje')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>
