<div class="mb-3">
  <label for="empresa_local_id" class="form-label fw-bold">Empresa</label>
  <select id="empresa_local_id" name="empresa_local_id" class="form-select" required>
    <option value="">Seleccione empresa...</option>
    @foreach($empresas as $id => $nombre)
      <option value="{{ $id }}" 
        @selected(old('empresa_local_id', $empresaClave->empresa_local_id ?? '') == $id)>
        {{ $nombre }}
      </option>
    @endforeach
  </select>
</div>

<div class="mb-3">
  <label for="servicio_externo_id" class="form-label fw-bold">Servicio</label>
  <select id="servicio_externo_id" name="servicio_externo_id" class="form-select" required>
    <option value="">Seleccione servicio...</option>
    @foreach($servicios as $id => $nombre)
      <option value="{{ $id }}" 
        @selected(old('servicio_externo_id', $empresaClave->servicio_externo_id ?? '') == $id)>
        {{ $nombre }}
      </option>
    @endforeach
  </select>
</div>

<div class="mb-3">
  <label for="url" class="form-label fw-bold">URL del Servicio</label>
  <input type="url" id="url" name="url" class="form-control"
         placeholder="https://www.ejemplo.com"
         value="{{ old('url', $servicioExterno->url ?? '') }}">
</div>

<div class="mb-3">
  <label for="usuario" class="form-label fw-bold">Usuario</label>
  <input type="text" id="usuario" name="usuario"
         class="form-control @error('usuario') is-invalid @enderror"
         value="{{ old('usuario', $empresaClave->usuario ?? '') }}">
  @error('usuario') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="correo_registrado" class="form-label fw-bold">Correo Registrado</label>
  <input type="email" id="correo_registrado" name="correo_registrado"
         class="form-control @error('correo_registrado') is-invalid @enderror"
         value="{{ old('correo_registrado', $empresaClave->correo_registrado ?? '') }}">
  @error('correo_registrado') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="password" class="form-label fw-bold">Contraseña</label>
  <div class="input-group">
    <input type="text" id="password" name="password"
           class="form-control @error('password') is-invalid @enderror password-field"
           value="{{ old('password', $empresaClave->password ?? '') }}">
    <button type="button" class="btn btn-outline-secondary copy-password" title="Copiar contraseña">
      <i class="fa-solid fa-copy"></i>
    </button>
  </div>
  @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Copiar contraseña
  document.querySelectorAll('.copy-password').forEach(btn => {
    btn.addEventListener('click', async function() {
      const input = this.closest('.input-group').querySelector('.password-field');
      const text = input?.value ?? '';
      const icon = this.querySelector('i');

      if (!text.trim()) {
        alert('No hay contraseña para copiar.');
        return;
      }

      try {
        await navigator.clipboard.writeText(text);
        icon.classList.replace('fa-copy', 'fa-check');
        this.classList.replace('btn-outline-secondary', 'btn-outline-success');
        this.title = '¡Copiado!';

        setTimeout(() => {
          icon.classList.replace('fa-check', 'fa-copy');
          this.classList.replace('btn-outline-success', 'btn-outline-secondary');
          this.title = 'Copiar contraseña';
        }, 1500);
      } catch (err) {
        alert('No se pudo copiar la contraseña.');
      }
    });
  });
});
</script>
@endpush
