@csrf
{{-- Si este es el form de edición, afuera agrega @method('PUT') --}}

<div class="row">
    <div class="col-md-4">
        <label for="documento_id">Tipo de Documento</label>
        <select name="documento_id" id="documento_id" class="form-control" required>
            <option value="">Seleccione</option>
            @foreach($documentos as $item)
                <option value="{{ $item->id }}" {{ old('documento_id', $usuarioExterno->documento_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="numero">Número de Documento</label>
        <input type="text" name="numero" id="numero" class="form-control" value="{{ old('numero', $usuarioExterno->numero ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label for="fecha_expedicion">Fecha de Expedición</label>
        <input
            type="date"
            name="fecha_expedicion"
            id="fecha_expedicion"
            class="form-control"
            value="{{ old('fecha_expedicion', isset($usuarioExterno) ? ($usuarioExterno->fecha_expedicion?->format('Y-m-d')) : '') }}"
            required
        >
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <label for="primer_apellido">Primer Apellido</label>
        <input type="text" name="primer_apellido" id="primer_apellido" class="form-control" value="{{ old('primer_apellido', $usuarioExterno->primer_apellido ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label for="segundo_apellido">Segundo Apellido</label>
        <input type="text" name="segundo_apellido" id="segundo_apellido" class="form-control" value="{{ old('segundo_apellido', $usuarioExterno->segundo_apellido ?? '') }}">
    </div>

    <div class="col-md-4">
        <label for="primer_nombre">Primer Nombre</label>
        <input type="text" name="primer_nombre" id="primer_nombre" class="form-control" value="{{ old('primer_nombre', $usuarioExterno->primer_nombre ?? '') }}" required>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <label for="segundo_nombre">Segundo Nombre</label>
        <input type="text" name="segundo_nombre" id="segundo_nombre" class="form-control" value="{{ old('segundo_nombre', $usuarioExterno->segundo_nombre ?? '') }}">
    </div>

    <div class="col-md-4">
        <label for="fecha_nacimiento">Fecha de Nacimiento</label>
        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" value="{{ old('fecha_nacimiento', isset($usuarioExterno) ? ($usuarioExterno->fecha_nacimiento?->format('Y-m-d')) : '') }}" required>
    </div>

    <div class="col-md-4">
        <label for="correo_electronico">Correo Electrónico</label>
        <input type="email" name="correo_electronico" id="correo_electronico" class="form-control" value="{{ old('correo_electronico', $usuarioExterno->correo_electronico ?? '') }}">
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <label for="direccion">Dirección</label>
        <input type="text" name="direccion" id="direccion" class="form-control" value="{{ old('direccion', $usuarioExterno->direccion ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label for="telefono">Teléfono</label>
        <input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono', $usuarioExterno->telefono ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label for="fecha_afiliacion">Fecha de Afiliación</label>
        <input
            type="date"
            name="fecha_afiliacion"
            id="fecha_afiliacion"
            class="form-control"
            value="{{ old('fecha_afiliacion', isset($usuarioExterno) ? ($usuarioExterno->fecha_afiliacion?->format('Y-m-d')) : '') }}"
            required
        >
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <label for="sexo">Sexo</label>
        <select name="sexo" id="sexo" class="form-control" required>
            <option value="">Seleccione</option>
            <option value="M"    {{ old('sexo', $usuarioExterno->sexo ?? '') == 'M' ? 'selected' : '' }}>Masculino</option>
            <option value="F"    {{ old('sexo', $usuarioExterno->sexo ?? '') == 'F' ? 'selected' : '' }}>Femenino</option>
            <option value="Otro" {{ old('sexo', $usuarioExterno->sexo ?? '') == 'Otro' ? 'selected' : '' }}>Otro</option>
        </select>
    </div>

    <div class="col-md-4">
        <label for="eps_id">EPS</label>
        <select name="eps_id" id="eps_id" class="form-control" required>
            <option value="">Seleccione</option>
            @foreach($eps as $item)
                <option value="{{ $item->id }}" {{ old('eps_id', $usuarioExterno->eps_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="arl_id">ARL</label>
        <select name="arl_id" id="arl_id" class="form-control" required>
            <option value="">Seleccione</option>
            @foreach($arls as $item)
                <option value="{{ $item->id }}" {{ old('arl_id', $usuarioExterno->arl_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->nombre }} (Nivel {{ $item->nivel }})
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <label for="subtipo_cotizantes_id">Subtipo Cotizante</label>
        <select name="subtipo_cotizantes_id" id="subtipo_cotizantes_id" class="form-control" required>
            <option value="">Seleccione</option>
            @foreach($subtipos as $item)
                <option value="{{ $item->id }}" {{ old('subtipo_cotizantes_id', $usuarioExterno->subtipo_cotizantes_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="pension_id">Pensión</label>
        <select name="pension_id" id="pension_id" class="form-control" required>
            <option value="">Seleccione</option>
            @foreach($pensions as $item)
                <option value="{{ $item->id }}" {{ old('pension_id', $usuarioExterno->pension_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="caja_id">Caja de Compensación</label>
        <select name="caja_id" id="caja_id" class="form-control" required>
            <option value="">Seleccione</option>
            @foreach($cajas as $item)
                <option value="{{ $item->id }}" {{ old('caja_id', $usuarioExterno->caja_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->nombre }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
      <label for="empresa_local_id">Empresa Local</label>
        @php $inactivo = isset($usuarioExterno) ? !$usuarioExterno->estado : false; @endphp

        @if($inactivo)
         {{-- Si está INACTIVO: permitir elegir empresa destino para la reactivación --}}
            <select name="empresa_local_id" id="empresa_local_id" class="form-control">
                 <option value="">Seleccione</option>
                    @foreach(($empresasLocales ?? []) as $emp)
                        <option value="{{ $emp->id }}"
                            {{ (string)old('empresa_local_id', $usuarioExterno->empresa_local_id) === (string)$emp->id ? 'selected' : '' }}>
                            {{ $emp->nombre }}
                        </option>
                    @endforeach
            </select>
    <small class="text-muted">Esta empresa se aplicará al guardar como Activo.</small>
  @else
    {{-- Si está ACTIVO: solo lectura + hidden (no se puede cambiar) --}}
    <input type="text" class="form-control"
           value="{{ $usuarioExterno->empresaLocal->nombre ?? 'Sin empresa' }}" readonly>
    <input type="hidden" name="empresa_local_id" value="{{ $usuarioExterno->empresa_local_id }}">
  @endif
    </div>

    <div class="col-md-6">
      <label for="empresa_externa_id">Empresa Externa</label>
      <select name="empresa_externa_id" id="empresa_externa_id" class="form-control" required>
          <option value="">Seleccione</option>
          @foreach($empresaExternas as $item)
              <option value="{{ $item->id }}"
                {{ old('empresa_externa_id', $usuarioExterno->empresa_externa_id ?? '') == $item->id ? 'selected' : '' }}>
                  {{ $item->nombre }}
              </option>
          @endforeach
      </select>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <label for="sueldo">Sueldo</label>
        <input type="number" step="0.01" name="sueldo" id="sueldo" class="form-control" value="{{ old('sueldo', $usuarioExterno->sueldo ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label for="admon">Valor Administración</label>
        <input type="number" step="0.01" name="admon" id="admon" class="form-control" value="{{ old('admon', $usuarioExterno->admon ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label for="seg_exequial">Servicio Exequial</label>
        <input type="number" step="0.01" name="seg_exequial" id="seg_exequial" class="form-control" value="{{ old('seg_exequial', $usuarioExterno->seg_exequial ?? '') }}">
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <label for="mora">Mora</label>
        <input type="number" step="0.01" name="mora" id="mora" class="form-control" value="{{ old('mora', $usuarioExterno->mora ?? '') }}">
    </div>

    <div class="col-md-4">
        <label for="otros_servicios">Otros Servicios</label>
        <input type="number" step="0.01" name="otros_servicios" id="otros_servicios" class="form-control" value="{{ old('otros_servicios', $usuarioExterno->otros_servicios ?? '') }}">
    </div>

    <div class="col-md-4">
        <label for="cargo">Cargo</label>
        <input type="text" name="cargo" id="cargo" class="form-control" value="{{ old('cargo', $usuarioExterno->cargo ?? '') }}" required>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <label for="asesor_id">Asesor</label>
        <select name="asesor_id" id="asesor_id" class="form-control" required>
            <option value="">Seleccione</option>
            @foreach($asesores as $item)
                <option value="{{ $item->id }}" {{ old('asesor_id', $usuarioExterno->asesor_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="estado">Estado</label>
        <select name="estado" id="estado" class="form-control" required>
            <option value="1" {{ old('estado', $usuarioExterno->estado ?? 1) == 1 ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ old('estado', $usuarioExterno->estado ?? 1) == 0 ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>

    <div class="col-md-4">
        <label for="novedad">Novedad</label>
        <select name="novedad" id="novedad" class="form-control" required>
            @php $nvd = old('novedad', $usuarioExterno->novedad ?? 'Ingreso'); @endphp
            <option value="Ingreso" {{ $nvd === 'Ingreso' ? 'selected' : '' }}>Ingreso</option>
            <option value="Retiro"  {{ $nvd === 'Retiro'  ? 'selected' : '' }}>Retiro</option>
        </select>
    </div>
</div>

@php $mostrarRetiro = old('novedad', $usuarioExterno->novedad ?? 'Ingreso') === 'Retiro'; @endphp
<div class="row mt-3" id="fecha-retiro-container" style="{{ $mostrarRetiro ? '' : 'display:none;' }}">
    <div class="col-md-4">
        <label for="fecha_retiro">Fecha de Retiro</label>
        {{-- IMPORTANTE: sin required por defecto --}}
        <input
            type="date"
            name="fecha_retiro"
            id="fecha_retiro"
            class="form-control"
            value="{{ old('fecha_retiro', isset($usuarioExterno) ? ($usuarioExterno->fecha_retiro?->format('Y-m-d')) : '') }}"
        >
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const novedadSel       = document.getElementById('novedad');
    const retiroBox        = document.getElementById('fecha-retiro-container');
    const fechaRetiroInput = document.getElementById('fecha_retiro');

    function toggleFechaRetiro() {
        if (!novedadSel || !retiroBox) return;
        const show = (novedadSel.value === 'Retiro');
        retiroBox.style.display = show ? 'block' : 'none';

        if (fechaRetiroInput) {
            if (show) {
                fechaRetiroInput.setAttribute('required', 'required'); // ← exigir solo si Retiro
            } else {
                fechaRetiroInput.removeAttribute('required');
                fechaRetiroInput.value = '';
            }
        }
    }

    if (novedadSel) {
        novedadSel.addEventListener('change', toggleFechaRetiro);
        toggleFechaRetiro(); // Estado inicial
    }
});

document.addEventListener('DOMContentLoaded', () => {
  const estadoSel = document.getElementById('estado');
  const empresaSelWrap = document.getElementById('empresa_local_id').closest('.col-md-6');

  function toggleEmpresa() {
    // si está activo, mostramos; si inactivo, también podrías dejarlo visible
    empresaSelWrap.style.display = 'block';
  }

  if (estadoSel) {
    estadoSel.addEventListener('change', toggleEmpresa);
    toggleEmpresa();
  }
});
</script>
@endpush
