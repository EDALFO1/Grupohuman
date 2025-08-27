<div class="mb-3">
    <label for="documento_id" class="form-label fw-bold">Tipo de Documento</label>
    <select name="documento_id" class="form-select">
        <option value="">Seleccione un documento</option>
        @foreach($documentos as $doc)
            <option value="{{ $doc->id }}" {{ (old('documento_id', $empresa->documento_id ?? '') == $doc->id) ? 'selected' : '' }}>
                {{ $doc->nombre }}
            </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label for="numero_documento" class="form-label fw-bold">Numero</label>
    <input type="text" name="numero_documento" class="form-control" value="{{ old('numero_documento', $empresa->numero_documento ?? '') }}">
    @error('numero_documento')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="mb-3">
    <label for="nombre" class="form-label fw-bold">Nombre</label>
    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $empresa->nombre ?? '') }}">
    @error('nombre')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="mb-3">
    <label for="direccion" class="form-label fw-bold">Dirección</label>
    <input type="text" name="direccion" class="form-control" value="{{ old('direccion', $empresa->direccion ?? '') }}">
    @error('direccion')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="mb-3">
    <label for="telefono" class="form-label fw-bold">Teléfono</label>
    <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $empresa->telefono ?? '') }}">
    @error('telefono')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="mb-3">
    <label for="contacto" class="form-label fw-bold">Contacto</label>
    <input type="text" name="contacto" class="form-control" value="{{ old('contacto', $empresa->contacto ?? '') }}">
    @error('contacto')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

<div class="mb-3">
    <label for="activo" class="form-label fw-bold">Activo</label>
    <select name="activo" class="form-select">
        <option value="1" {{ old('activo', $empresa->activo ?? '') == 1 ? 'selected' : '' }}>Sí</option>
        <option value="0" {{ old('activo', $empresa->activo ?? '') == 0 ? 'selected' : '' }}>No</option>
    </select>
</div>
