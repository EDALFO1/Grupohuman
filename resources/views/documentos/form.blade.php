<div class="mb-3">
    <label for="nombre" class="form-label fw-bold">Nombre del documento</label>
    <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $documento->nombre ?? '') }}">
    @error('nombre')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>




