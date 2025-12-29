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
        
        <div class="d-flex justify-content-between align-items-center mb-3">


  <a href="{{ route('empresa-claves.create', ['empresa_id' => $empresaId]) }}"
     class="btn btn-primary">
    <i class="bi bi-plus-circle"></i> Nueva Clave
  </a>
</div>


        {{-- 🔍 FILTRO --}}
        <form method="GET" action="{{ route('empresa-claves.resumen') }}" class="row g-3 align-items-end mb-4">
          <div class="col-md-6">
            <label for="empresa_id" class="form-label fw-bold">Seleccionar Empresa</label>
            <select id="empresa_id" name="empresa_id" class="form-select">
              <option value="">-- Todas las Empresas --</option>
              @foreach($todasEmpresas as $id => $nombre)
                <option value="{{ $id }}" {{ $empresaId == $id ? 'selected' : '' }}>{{ $nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
            <a href="{{ route('empresa-claves.resumen') }}" class="btn btn-secondary">Limpiar</a>
          </div>
        </form>

        {{-- 🏢 AGRUPADO POR EMPRESA --}}
        @foreach ($empresas as $empresa)
          <div class="card mt-4">
            <div class="card-header bg-secondary-subtle text-primary fw-bold">
              <i class="fa-solid fa-building"></i> {{ strtoupper($empresa->nombre) }}
              <span class="badge bg-light text-dark float-end">{{ $empresa->claves->count() }} servicios</span>
            </div>
            <div class="card-body p-0">
              @if ($empresa->claves->isEmpty())
                <p class="p-3 text-muted mb-0">No tiene claves registradas.</p>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mb-0">
                    <thead>
  <tr>
    <th>Servicio</th>
    <th>Usuario</th>
    <th>Correo Registrado</th>
    <th>Contraseña</th>
    <th>URL</th>
    <th>Acciones</th>
  </tr>
</thead>
                   <tbody>
  @foreach ($empresa->claves as $clave)
    <tr>
      <td>{{ $clave->servicio->nombre ?? '—' }}</td>
      <td>{{ $clave->usuario ?? '—' }}</td>
      <td>{{ $clave->correo_registrado ?? '—' }}</td>

      {{-- 🔐 CONTRASEÑA --}}
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

      {{-- 🌐 URL --}}
      <td>
        @if (!empty($clave->servicio->url))
          <a href="{{ $clave->servicio->url }}" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-link"></i> Ir
          </a>
        @else
          <span class="text-muted">—</span>
        @endif
      </td>

      {{-- 🛠 ACCIONES --}}
      <td class="d-flex gap-2">
        <a href="{{ route('empresa-claves.edit', $clave) }}" 
           class="btn btn-warning btn-sm" title="Editar">
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
  @endforeach
</tbody>

                  </table>
                </div>
              @endif
            </div>
          </div>
        @endforeach
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
        input.classList.add('bg-success-subtle'); // cambia color cuando se muestra
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
