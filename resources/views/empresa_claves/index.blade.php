@extends('layouts.main')

@section('titulo', 'Listado de Claves')

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle">
    <h1>Claves Registradas</h1>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
              <h5 class="card-title mb-0">Listado general de claves</h5>
              <a href="{{ route('empresa-claves.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nueva Clave
              </a>
            </div>

            {{-- Mensajes --}}
            @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
              <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- Tabla --}}
            <div class="table-responsive">
              <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Empresa</th>
                    <th>Servicio</th>
                    <th>Usuario</th>
                    <th>Correo Registrado</th>
                    <th>Contraseña</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($claves as $clave)
                    <tr>
                      <td>{{ $clave->empresa->nombre ?? '—' }}</td>
                      <td>{{ $clave->servicio->nombre ?? '—' }}</td>
                      <td>{{ $clave->usuario ?? '—' }}</td>
                      <td>{{ $clave->correo_registrado ?? '—' }}</td>

                      {{-- 🔐 Contraseña oculta con opciones --}}
                      <td>
                        <div class="input-group input-group-sm">
                          <input type="password" readonly class="form-control password-field"
                                 value="{{ $clave->password ?? '' }}">
                          <button type="button" class="btn btn-outline-secondary toggle-password" title="Mostrar contraseña">
                            <i class="fa-solid fa-eye"></i>
                          </button>
                          <button type="button" class="btn btn-outline-secondary copy-password" title="Copiar contraseña">
                            <i class="fa-solid fa-copy"></i>
                          </button>
                        </div>
                      </td>

                      {{-- 🛠 Acciones --}}
                      <td class="d-flex gap-2">
                        <a href="{{ route('empresa-claves.edit', $clave) }}" class="btn btn-warning btn-sm" title="Editar">
                          <i class="bi bi-pencil-square"></i>
                        </a>
                        <form action="{{ route('empresa-claves.destroy', $clave) }}" method="POST"
                              onsubmit="return confirm('¿Deseas eliminar esta clave?')">
                          @csrf
                          @method('DELETE')
                          <button class="btn btn-danger btn-sm" title="Eliminar">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center text-muted">No hay claves registradas.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="mt-3">
              {{ $claves->links() }}
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

  // 👁️ Mostrar / Ocultar contraseña con color visual
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
