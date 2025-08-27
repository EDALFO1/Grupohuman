<div class="mb-3">
    <label for="codigo" class="form-label fw-bold">Código</label>
    <input type="text" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
           value="{{ old('codigo', $producto->codigo ?? '') }}">
    @error('codigo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="nombre" class="form-label fw-bold">Nombre</label>
    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
           value="{{ old('nombre', $producto->nombre ?? '') }}">
    @error('nombre')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="precio_unitario" class="form-label fw-bold">Precio Unitario</label>
    <input type="number" step="0.01" name="precio_unitario" class="form-control @error('precio_unitario') is-invalid @enderror"
           value="{{ old('precio_unitario', $producto->precio_unitario ?? '') }}">
    @error('precio_unitario')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="iva" class="form-label fw-bold">IVA (%)</label>
    <input type="number" step="0.01" name="iva" class="form-control @error('iva') is-invalid @enderror"
           value="{{ old('iva', $producto->iva ?? 0) }}">
    @error('iva')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="descripcion" class="form-label fw-bold">Descripción</label>
    <textarea name="descripcion" class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
    @error('descripcion')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
