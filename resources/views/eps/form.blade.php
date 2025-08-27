<div class="mb-3">
    <label for="nombre" class="form-label fw-bold">Nombre</label>
    <input type="text"  class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $eps->nombre ?? '') }}">
    @error('nombre')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="codigo" class="form-label fw-bold">Código</label>
    <input type="text"  class="form-control @error('codigo') is-invalid @enderror" id="codigo" name="codigo" value="{{ old('codigo', $eps->codigo ?? '') }}">
    @error('codigo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="porcentaje" class="form-label fw-bold">Porcentaje</label>
    <input type="number" step="0.0001" min="0" max="100" class="form-control @error('porcentaje') is-invalid @enderror" id="porcentaje"  name="porcentaje" value="{{ old('porcentaje', $eps->porcentaje ?? '') }}">
    @error('porcentaje')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
