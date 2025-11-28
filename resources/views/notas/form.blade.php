<div class="mb-3">
  <label for="titulo" class="form-label fw-bold">Título</label>
  <input type="text" id="titulo" name="titulo"
         class="form-control @error('titulo') is-invalid @enderror"
         value="{{ old('titulo', $nota->titulo ?? '') }}">
  @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="descripcion" class="form-label fw-bold">Descripción</label>
  <textarea id="descripcion" name="descripcion" rows="4"
            class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $nota->descripcion ?? '') }}</textarea>
  @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="tipo" class="form-label fw-bold">Tipo</label>
  <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror">
    @php
      $tipos = [
        'traslado'       => 'Traslado',
        'certificado'    => 'Certificado',
        'cambio_empresa' => 'Cambio de empresa',
        'recordatorio'   => 'Recordatorio',
        'otro'           => 'Otro',
      ];
    @endphp
    @foreach($tipos as $valor => $texto)
      <option value="{{ $valor }}" @selected(old('tipo', $nota->tipo ?? 'otro') == $valor)>
        {{ $texto }}
      </option>
    @endforeach
  </select>
  @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="estado" class="form-label fw-bold">Estado</label>
  <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror">
    @php
      $estados = [
        'pendiente'   => 'Pendiente',
        'en_proceso'  => 'En proceso',
        'resuelto'    => 'Resuelto',
        'cancelado'   => 'Cancelado',
      ];
    @endphp
    @foreach($estados as $valor => $texto)
      <option value="{{ $valor }}" @selected(old('estado', $nota->estado ?? 'pendiente') == $valor)>
        {{ $texto }}
      </option>
    @endforeach
  </select>
  @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
  <label for="fecha_vencimiento" class="form-label fw-bold">Fecha de vencimiento</label>
  <input type="date" id="fecha_vencimiento" name="fecha_vencimiento"
         class="form-control @error('fecha_vencimiento') is-invalid @enderror"
         value="{{ old('fecha_vencimiento', optional($nota->fecha_vencimiento ?? null)->format('Y-m-d')) }}">
  @error('fecha_vencimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
