<div class="mb-3">
  <label for="nombre" class="form-label fw-bold">Nombre del Servicio</label>
  <input type="text" id="nombre" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
         value="{{ old('nombre', $servicio->nombre ?? '') }}" required>
  @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="tipo" class="form-label fw-bold">Tipo</label>
  <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
    <option value="">Seleccione tipo...</option>
    @foreach(['EPS', 'ARL', 'CAJA', 'PLATAFORMA', 'OTRO'] as $tipo)
      <option value="{{ $tipo }}" @selected(old('tipo', $servicio->tipo ?? '') == $tipo)>{{ $tipo }}</option>
    @endforeach
  </select>
  @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="url_login" class="form-label fw-bold">URL de Login</label>
  <input type="url" id="url_login" name="url_login" class="form-control @error('url_login') is-invalid @enderror"
         value="{{ old('url_login', $servicio->url_login ?? '') }}"
         placeholder="https://...">
  @error('url_login') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
