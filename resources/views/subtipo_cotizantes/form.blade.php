<div class="mb-3">
    <label for="codigo" class="form-label">Código</label>
    <input type="text" name="codigo" id="codigo" class="form-control"
           value="{{ old('codigo', $subtipo_cotizante->codigo ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="nombre" class="form-label">Nombre</label>
    <input type="text" name="nombre" id="nombre" class="form-control"
           value="{{ old('nombre', $subtipo_cotizante->nombre ?? '') }}" required>
</div>



