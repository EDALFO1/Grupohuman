@php
  $inc = $incapacidad ?? null;
@endphp

@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row g-3">
  {{-- BUSCAR POR DOCUMENTO --}}
  <div class="col-md-3">
    <label class="form-label">Documento</label>
    <div class="input-group">
      <input type="text" id="doc_buscar" class="form-control" placeholder="Número"
             value="{{ old('documento', $inc?->documento) }}">
      <button class="btn btn-outline-secondary" type="button" id="btnBuscarDoc">Buscar</button>
    </div>
    <small class="text-muted">Busca y autocompleta datos del usuario</small>
  </div>

  <input type="hidden" name="usuario_externo_id" id="usuario_externo_id"
         value="{{ old('usuario_externo_id', $inc?->usuario_externo_id) }}">

  <div class="col-md-5">
    <label class="form-label">Nombre</label>
    <input type="text" name="nombre" id="nombre" class="form-control"
           value="{{ old('nombre', $inc?->nombre) }}" required>
  </div>

  <div class="col-md-4">
    <!--<label class="form-label">Documento (guardar)</label>-->
    <input type="hidden" name="documento" id="documento" class="form-control"
           value="{{ old('documento', $inc?->documento) }}" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Empresa Local</label>
    <input type="hidden" name="empresa_local_id" id="empresa_local_id"
           value="{{ old('empresa_local_id', $inc?->empresa_local_id) }}">
    <input type="text" id="empresa_local_nombre" class="form-control"
           value="{{ old('empresa_local_nombre', data_get($inc, 'empresaLocal.nombre')) }}"
           placeholder="(Se autocompleta si usas buscar)">
  </div>

  <div class="col-md-6">
    <label class="form-label">Empresa Externa</label>
    <input type="hidden" name="empresa_externa_id" id="empresa_externa_id"
           value="{{ old('empresa_externa_id', $inc?->empresa_externa_id) }}">
    <input type="text" id="empresa_externa_nombre" class="form-control"
           value="{{ old('empresa_externa_nombre', data_get($inc, 'empresaExterna.nombre')) }}"
           placeholder="(Se autocompleta si usas buscar)">
  </div>

  {{-- ENTIDAD --}}
  <div class="col-md-3">
    <label class="form-label">Entidad (tipo)</label>
    <select name="entidad_tipo" id="entidad_tipo" class="form-select" required>
      @foreach($entidadTipos as $tipo)
        <option value="{{ $tipo }}" @selected(old('entidad_tipo', $inc?->entidad_tipo) === $tipo)>{{ $tipo }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-md-5">
    <label class="form-label">EPS</label>
    <select name="eps_id" id="eps_id" class="form-select">
      <option value="">-- Seleccione EPS --</option>
      @foreach($epsList as $e)
        <option value="{{ $e->id }}" @selected((int)old('eps_id', $inc?->eps_id) === $e->id)>{{ $e->nombre }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label">ARL</label>
    <select name="arl_id" id="arl_id" class="form-select">
      <option value="">-- Seleccione ARL --</option>
      @foreach($arlList as $a)
        <option value="{{ $a->id }}" @selected((int)old('arl_id', $inc?->arl_id) === $a->id)>{{ $a->nombre }}</option>
      @endforeach
    </select>
  </div>

  <input type="hidden" name="entidad_nombre" id="entidad_nombre"
         value="{{ old('entidad_nombre', $inc?->entidad_nombre) }}">

  {{-- FECHAS Y DIAS --}}
  <div class="col-md-4">
    <label class="form-label">Fecha inicio</label>
    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control"
           value="{{ old('fecha_inicio', optional($inc?->fecha_inicio)->format('Y-m-d')) }}" required>
  </div>

  <div class="col-md-4">
    <label class="form-label">Fecha fin</label>
    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control"
           value="{{ old('fecha_fin', optional($inc?->fecha_fin)->format('Y-m-d')) }}" required>
  </div>

  <div class="col-md-4">
    <label class="form-label">Días solicitados</label>
    <input type="number" name="dias_solicitados" id="dias_solicitados" class="form-control"
           value="{{ old('dias_solicitados', $inc?->dias_solicitados) }}" readonly>
    <small class="text-muted">Se calcula automáticamente (inclusive)</small>
  </div>

  <div class="col-md-4">
    <label class="form-label">Fecha radicación</label>
    <input type="date" name="fecha_radicacion" class="form-control"
           value="{{ old('fecha_radicacion', optional($inc?->fecha_radicacion)->format('Y-m-d')) }}">
  </div>

  <div class="col-md-4">
    <label class="form-label">Estado</label>
    <select name="estado" class="form-select" required>
      @foreach($estados as $es)
        <option value="{{ $es }}" @selected(old('estado', $inc?->estado ?? 'transcrita') === $es)>{{ ucfirst($es) }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label">Fecha de pago</label>
    <input type="date" name="fecha_pago" class="form-control"
           value="{{ old('fecha_pago', optional($inc?->fecha_pago)->format('Y-m-d')) }}">
  </div>

  <div class="col-12">
    <label class="form-label">Observaciones libres</label>
    <textarea name="observaciones_libres" rows="3" class="form-control">{{ old('observaciones_libres', $inc?->observaciones_libres) }}</textarea>
    @if(($mode ?? 'create') === 'create')
      <small class="text-muted">Además puedes agregar una <b>observación inicial</b> que queda en el historial:</small>
      <input type="text" name="observacion_inicial" class="form-control mt-1" placeholder="Observación inicial (opcional)">
    @endif
  </div>

  <div class="col-12 text-end mt-3">
    <a href="{{ route('incapacidades.index') }}" class="btn btn-secondary">Volver</a>
    <button class="btn btn-primary">{{ ($mode ?? 'create') === 'create' ? 'Guardar' : 'Actualizar' }}</button>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
const entidadTipo = document.getElementById('entidad_tipo');
const epsSel = document.getElementById('eps_id');
const arlSel = document.getElementById('arl_id');
const entidadNombre = document.getElementById('entidad_nombre');

function toggleEntidadFields(){
  const tipo = entidadTipo.value;
  epsSel.disabled = (tipo !== 'EPS');
  arlSel.disabled = (tipo !== 'ARL');
}
toggleEntidadFields();
entidadTipo.addEventListener('change', () => { toggleEntidadFields(); actualizarEntidadNombre(); });

function actualizarEntidadNombre() {
  if (entidadTipo.value === 'EPS') {
    entidadNombre.value = epsSel.options[epsSel.selectedIndex]?.text || '';
  } else if (entidadTipo.value === 'ARL') {
    entidadNombre.value = arlSel.options[arlSel.selectedIndex]?.text || '';
  } else {
    entidadNombre.value = '';
  }
}
epsSel.addEventListener('change', actualizarEntidadNombre);
arlSel.addEventListener('change', actualizarEntidadNombre);
actualizarEntidadNombre();

function calcularDias() {
  const fi = document.getElementById('fecha_inicio').value;
  const ff = document.getElementById('fecha_fin').value;
  const out = document.getElementById('dias_solicitados');

  if(fi && ff){
    const d1 = new Date(fi+'T00:00:00');
    const d2 = new Date(ff+'T00:00:00');
    const diff = Math.floor((d2 - d1) / (1000*60*60*24));
    out.value = (diff >= 0) ? (diff + 1) : 0; // inclusivo
  } else {
    out.value = '';
  }
}
document.getElementById('fecha_inicio').addEventListener('change', calcularDias);
document.getElementById('fecha_fin').addEventListener('change', calcularDias);
calcularDias();

// Buscar usuario externo por documento
document.getElementById('btnBuscarDoc').addEventListener('click', async () => {
  const doc = (document.getElementById('doc_buscar').value || '').trim();
  if(!doc) { alert('Ingresa un documento para buscar'); return; }

  try {
    const { data } = await axios.post("{{ route('incapacidades.buscarUsuario') }}", { documento: doc });
    if(!data.ok){ alert(data.msg || 'No encontrado'); return; }

    document.getElementById('documento').value = data.usuario.documento;
    document.getElementById('usuario_externo_id').value = data.usuario.id;
    document.getElementById('nombre').value = data.usuario.nombre;

    document.getElementById('empresa_local_id').value = data.usuario.empresa_local_id || '';
    document.getElementById('empresa_local_nombre').value = data.usuario.empresa_local_nombre || '';

    document.getElementById('empresa_externa_id').value = data.usuario.empresa_externa_id || '';
    document.getElementById('empresa_externa_nombre').value = data.usuario.empresa_externa_nombre || '';

    if (data.usuario.eps_id) {
      entidadTipo.value = 'EPS';
      toggleEntidadFields();
      epsSel.value = data.usuario.eps_id;
      arlSel.value = '';
    } else if (data.usuario.arl_id) {
      entidadTipo.value = 'ARL';
      toggleEntidadFields();
      arlSel.value = data.usuario.arl_id;
      epsSel.value = '';
    }
    actualizarEntidadNombre();
  } catch (e) {
    alert('Error en la búsqueda');
  }
});
</script>
@endpush
