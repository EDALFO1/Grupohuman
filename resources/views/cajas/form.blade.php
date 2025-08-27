<div class="mb-3">
    <label for="nombre" class="form-label fw-bold">Nombre</label>
    <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $caja->nombre ?? '') }}">
    @error('nombre')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="codigo" class="form-label fw-bold">Código</label>
    <input type="text" name="codigo" id="codigo" class="form-control" value="{{ old('codigo', $caja->codigo ?? '') }}">
    @error('codigo')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="porcentaje" class="form-label fw-bold">Porcentaje</label>
    <input type="number" name="porcentaje" id="porcentaje" class="form-control" step="0.0001" value="{{ old('porcentaje', $caja->porcentaje ?? '') }}">
    @error('porcentaje')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
