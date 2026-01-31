@csrf

<div class="mb-3">
  <label class="form-label">Número de Documento del Usuario</label>
  <div class="input-group">
    <input type="text" class="form-control" id="numero" placeholder="Buscar por número..." required>
    <button type="button" id="btnBuscar" class="btn btn-secondary">Buscar</button>
  </div>
</div>

<div id="datosUsuario" style="display:none;">
  <input type="hidden" name="usuario_externo_id" id="usuario_externo_id">

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Nombre completo</label>
      <input type="text" class="form-control" id="nombre_completo" disabled>
    </div>

    <div class="col-md-6">
      <label class="form-label">Fecha de afiliación</label>
      <input type="text" class="form-control" id="fecha_afiliacion" disabled>
    </div>

    <div class="col-md-6">
      <label class="form-label">Días a liquidar</label>
      <input type="text" class="form-control" id="dias_liquidar" disabled>
    </div>
  </div>

  <hr>
  <h5>Valores (calculados por el sistema)</h5>

  <div class="row g-3">
    @foreach (['eps','arl','pension','caja','admon','exequial','mora'] as $v)
      <div class="col-md-3">
        <label class="form-label text-capitalize">{{ $v }}</label>
        <input type="text" id="valor_{{ $v }}" class="form-control" value="—" disabled>
      </div>
    @endforeach
  </div>

  <div class="row g-3 mt-2">
    <div class="col-md-3">
      <label class="form-label">Otros servicios</label>
      <input
        type="number"
        step="100"
        min="0"
        id="otros_servicios"
        name="otros_servicios"
        class="form-control"
        value="{{ old('otros_servicios', 0) }}"
        required
      >
    </div>
  </div>

  <div class="mt-4">
    <label class="form-label"><strong>Total</strong></label>
    <input type="text" id="total" class="form-control form-control-lg fw-bold" value="—" disabled>
  </div>

  <div class="row g-3 mt-4">
    <div class="col-md-6">
      <label class="form-label">Fecha de la remisión</label>
      <input type="date" name="fecha" id="fecha" class="form-control"
             value="{{ old('fecha', now()->format('Y-m-d')) }}" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">Novedad</label>
      <select name="novedad" id="novedad" class="form-control" required>
        <option value="Ingreso">Ingreso</option>
        <option value="Retiro">Retiro</option>
      </select>
    </div>
  </div>

  <div class="mt-3" id="campoFechaRetiro" style="display:none;">
    <label class="form-label">Fecha de retiro</label>
    <input type="date" name="fecha_retiro" id="fecha_retiro" class="form-control" disabled>
  </div>
</div>

@push('scripts')
<script>
let usuarioCargado = null;

/* ===================== UTILIDADES ===================== */
function parseLocalDate(s) {
  if (!s) return null;
  const [y,m,d] = s.split('-').map(Number);
  return new Date(y, m-1, d);
}

/* ===== DÍAS BASE-30 (UX) ===== */
function diasMesBase30(fechaAfiliacion, fechaRemision, fechaRetiro=null) {
  if (!fechaAfiliacion || !fechaRemision) return 0;

  const fRem = parseLocalDate(fechaRemision);
  const inicioMes = new Date(fRem.getFullYear(), fRem.getMonth()-1, 1);
  const finMes    = new Date(fRem.getFullYear(), fRem.getMonth()-1, 30);

  const af  = parseLocalDate(fechaAfiliacion);
  const ret = fechaRetiro ? parseLocalDate(fechaRetiro) : null;

  if (!af || af > finMes) return 0;

  const start = af <= inicioMes ? 1 : Math.min(af.getDate(),30);
  const end   = ret && ret >= inicioMes && ret <= finMes
                  ? Math.min(ret.getDate(),30)
                  : 30;

  return Math.max(0, end - start + 1);
}

/* ===================== PREVIEW AJAX ===================== */
async function solicitarPreview() {
  const uid = document.getElementById('usuario_externo_id').value;
  if (!uid) return;

  const payload = {
    usuario_externo_id: uid,
    fecha: document.getElementById('fecha').value,
    novedad: document.getElementById('novedad').value,
    fecha_retiro: document.getElementById('fecha_retiro').value || null,
    otros_servicios: document.getElementById('otros_servicios').value || 0,
  };

  try {
    const { data } = await axios.post('{{ route("liquidacion.preview") }}', payload);

    document.getElementById('dias_liquidar').value = data.dias;

    Object.entries(data.valores).forEach(([k,v]) => {
      const el = document.getElementById(k);
      if (el) el.value = v;
    });

    document.getElementById('total').value = data.total;

  } catch (e) {
    console.error(e);
    alert('No fue posible calcular la liquidación.');
  }
}

/* ===================== BUSCAR USUARIO ===================== */
document.getElementById('btnBuscar').addEventListener('click', async () => {
  const numero = document.getElementById('numero').value.trim();
  if (!numero) return alert('Ingrese un número');

  try {
    const { data } = await axios.get(`/remisiones/buscar-usuario/${numero}`);
    usuarioCargado = data;

    document.getElementById('usuario_externo_id').value = data.id;
    document.getElementById('nombre_completo').value =
      `${data.primer_nombre ?? ''} ${data.primer_apellido ?? ''}`.trim();
    document.getElementById('fecha_afiliacion').value =
      (data.fecha_afiliacion || '').substring(0,10);

    document.getElementById('datosUsuario').style.display = 'block';

    solicitarPreview(); // 👈 preview real

  } catch {
    alert('Usuario no encontrado');
    usuarioCargado = null;
    document.getElementById('datosUsuario').style.display = 'none';
  }
});

/* ===================== NOVEDAD ===================== */
document.getElementById('novedad').addEventListener('change', function () {
  const campo = document.getElementById('campoFechaRetiro');
  const retiro = document.getElementById('fecha_retiro');

  if (this.value === 'Retiro') {
    campo.style.display = 'block';
    retiro.removeAttribute('disabled');
  } else {
    campo.style.display = 'none';
    retiro.value = '';
    retiro.setAttribute('disabled', true);
  }

  solicitarPreview();
});

/* ===================== DISPARADORES ===================== */
['fecha','fecha_retiro','otros_servicios'].forEach(id => {
  document.getElementById(id)?.addEventListener('change', solicitarPreview);
});

/* ===================== SUBMIT ===================== */
document.querySelector('form').addEventListener('submit', () => {
  if (document.getElementById('novedad').value === 'Retiro') {
    document.getElementById('fecha_retiro').removeAttribute('disabled');
  }
});
</script>
@endpush
