<div class="mb-3">
    <label for="documento_id" class="form-label fw-bold">Documento</label>
    <select name="documento_id" id="documento_id" class="form-select">
        <option value="">Seleccione un documento</option>
        @foreach ($documentos as $documento)
            <option value="{{ $documento->id }}" {{ old('documento_id', $empresa_externa->documento_id ?? '') == $documento->id ? 'selected' : '' }}>
                {{ $documento->nombre }}
            </option>
        @endforeach
    </select>
    @error('documento_id')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="numero" class="form-label fw-bold">Número</label>
    <input type="text" name="numero" id="numero" class="form-control" value="{{ old('numero', $empresa_externa->numero ?? '') }}">
    @error('numero')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="nombre" class="form-label fw-bold">Nombre</label>
    <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $empresa_externa->nombre ?? '') }}">
    @error('nombre')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="direccion" class="form-label fw-bold">Dirección</label>
    <input type="text" name="direccion" id="direccion" class="form-control" value="{{ old('direccion', $empresa_externa->direccion ?? '') }}">
    @error('direccion')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="telefono" class="form-label fw-bold">Teléfono</label>
    <input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono', $empresa_externa->telefono ?? '') }}">
    @error('telefono')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="contacto" class="form-label fw-bold">Contacto</label>
    <input type="text" name="contacto" id="contacto" class="form-control" value="{{ old('contacto', $empresa_externa->contacto ?? '') }}">
    @error('contacto')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="activo" class="form-label fw-bold">¿Activo?</label>
    <select name="activo" id="activo" class="form-select">
        <option value="1" {{ old('activo', $empresa_externa->activo ?? '') == 1 ? 'selected' : '' }}>Sí</option>
        <option value="0" {{ old('activo', $empresa_externa->activo ?? '') == 0 ? 'selected' : '' }}>No</option>
    </select>
    @error('activo')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>




