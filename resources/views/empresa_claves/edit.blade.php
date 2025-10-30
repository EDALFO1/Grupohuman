@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>{{ $titulo }}</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Actualizar información</h5>

        <form action="{{ route('empresa-claves.update', $empresaClave->id) }}" method="POST">
          @csrf
          @method('PUT')

          {{-- Empresa --}}
          <div class="mb-3">
            <label for="empresa_local_id" class="form-label fw-bold">Empresa</label>
            <select id="empresa_local_id" name="empresa_local_id" class="form-select" required>
              <option value="">Seleccione empresa...</option>
              @foreach($empresas as $id => $nombre)
                <option value="{{ $id }}" {{ $empresaClave->empresa_local_id == $id ? 'selected' : '' }}>
                  {{ $nombre }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Servicio --}}
          <div class="mb-3">
            <label for="servicio_externo_id" class="form-label fw-bold">Servicio</label>
            <select id="servicio_externo_id" name="servicio_externo_id" class="form-select" required>
              <option value="">Seleccione servicio...</option>
              @foreach($servicios as $id => $nombre)
                <option value="{{ $id }}" {{ $empresaClave->servicio_externo_id == $id ? 'selected' : '' }}>
                  {{ $nombre }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Usuario --}}
          <div class="mb-3">
            <label for="usuario" class="form-label fw-bold">Usuario</label>
            <input type="text" id="usuario" name="usuario" class="form-control"
                   value="{{ old('usuario', $empresaClave->usuario ?? '') }}">
          </div>

          {{-- Correo --}}
          <div class="mb-3">
            <label for="correo_registrado" class="form-label fw-bold">Correo Registrado</label>
            <input type="email" id="correo_registrado" name="correo_registrado" class="form-control"
                   value="{{ old('correo_registrado', $empresaClave->correo_registrado ?? '') }}">
          </div>

          {{-- Contraseña --}}
          <div class="mb-3">
            <label for="password" class="form-label fw-bold">Contraseña</label>
            <div class="input-group">
              <input type="password" id="password" name="password"
                     class="form-control password-field"
                     value="{{ old('password', $empresaClave->password ?? '') }}">
              <button type="button" class="btn btn-outline-secondary toggle-password" title="Mostrar contraseña">
                <i class="fa-solid fa-eye"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary copy-password" title="Copiar contraseña">
                <i class="fa-solid fa-copy"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Actualizar</button>
          <a href="{{ route('empresa-claves.resumen') }}" class="btn btn-secondary">Volver</a>
        </form>
      </div>
    </div>
  </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // 👁️ Mostrar / Ocultar con color visual
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function() {
      const input = this.closest('.input-group').querySelector('.password-field');
      const icon = this.querySelector('i');

      if (input.type === 'password') {
        input.type = 'text';
        input.classList.add('bg-success-subtle');
        icon.classList.replace('fa-eye', 'fa-eye-slash');
        this.title = 'Ocultar contraseña';
      } else {
        input.type = 'password';
        input.classList.remove('bg-success-subtle');
        icon.classList.replace('fa-eye-slash', 'fa-eye');
        this.title = 'Mostrar contraseña';
      }
    });
  });

  // 📋 Copiar contraseña
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
