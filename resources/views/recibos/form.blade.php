@csrf

@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="col-md-3">
  <div class="input-group input-group-sm">
    <input type="text" class="form-control form-control-sm" id="numero" name="numero" placeholder="Número..." required>
    <button type="button" id="btnBuscar" class="btn btn-success btn-sm">Buscar</button>
  </div>
</div>



<div id="datosUsuario" style="display: none;">
  <input type="hidden" name="usuario_externo_id" id="usuario_externo_id">

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Nombre Completo</label>
      <input type="text" class="form-control" id="nombre_completo" disabled>
    </div>

    <div class="col-md-6">
      <label class="form-label">Fecha de Afiliación</label>
      <input type="text" class="form-control" id="fecha_afiliacion" disabled>
    </div>

    <div class="col-md-6">
      <label class="form-label">Salario (vigencia BD)</label>
      {{-- Solo mostrar, no se envía al backend --}}
      <input
        type="number"
        step="100"
        min="0"
        class="form-control"
        id="sueldo"
        readonly
      >
    </div>

    <div class="col-md-6">
      <label class="form-label">Días a Liquidar</label>
      <input type="text" class="form-control" id="dias_liquidar" disabled>
    </div>
  </div>

  {{-- Administración solo lectura desde BD --}}
  <div class="row g-3 mt-2">
    <div class="col-md-6">
      <label class="form-label">Administración (vigencia BD)</label>
      {{-- Solo mostrar, no se envía al backend --}}
      <input
        type="number"
        step="100"
        min="0"
        id="admon"
        class="form-control"
        readonly
      >
    </div>
  </div>

  <hr>
  <h5>Valores Calculados</h5>

  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">EPS</label>
      <input type="text" id="valor_eps" class="form-control" disabled>
    </div>
    <div class="col-md-3">
      <label class="form-label">ARL</label>
      <input type="text" id="valor_arl" class="form-control" disabled>
    </div>
    <div class="col-md-3">
      <label class="form-label">Pensión</label>
      <input type="text" id="valor_pension" class="form-control" disabled>
    </div>
    <div class="col-md-3">
      <label class="form-label">Caja</label>
      <input type="text" id="valor_caja" class="form-control" disabled>
    </div>
  </div>

  <div class="row g-3 mt-2">
    <div class="col-md-3">
      <label class="form-label">Administración</label>
      <input type="text" id="valor_admon" class="form-control" disabled>
    </div>
    <div class="col-md-3">
      <label class="form-label">Exequial</label>
      <input type="text" id="valor_exequial" class="form-control" disabled>
    </div>
    <div class="col-md-3">
      <label class="form-label">Mora</label>
      <input type="text" id="valor_mora" class="form-control" disabled>
    </div>
    <div class="col-md-3">
      <label class="form-label">Otros Servicios</label>
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
    <input type="text" id="total" class="form-control form-control-lg fw-bold" disabled>
  </div>

  <div class="mt-4 row">
    <div class="col-md-6">
      <label for="fecha" class="form-label">Fecha del recibo</label>
      <input
        type="date"
        name="fecha"
        class="form-control"
        value="{{ old('fecha', now()->format('Y-m-d')) }}"
        required
      >
    </div>

    <div class="col-md-6">
      <label for="novedad" class="form-label">Novedad</label>
      <select name="novedad" id="novedad" class="form-control">
        <option value="">— Sin novedad —</option>
        <option value="Ingreso" {{ old('novedad') === 'Ingreso' ? 'selected' : '' }}>Ingreso</option>
        <option value="Retiro"  {{ old('novedad') === 'Retiro'  ? 'selected' : '' }}>Retiro</option>
      </select>
    </div>
  </div>

  <div class="mt-3" id="campoFechaRetiro" style="display: none;">
    <label for="fecha_retiro" class="form-label">Fecha de Retiro</label>
    <input type="date" name="fecha_retiro" id="fecha_retiro" class="form-control" disabled>
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const otros = document.getElementById('otros_servicios');
    if (otros && (otros.value === '' || otros.value === null)) {
      otros.value = '0';
    }
  });

  let usuarioCargado = null;

  /** Parse YYYY-MM-DD en zona local (evita interpretar en UTC) */
  function parseLocalDate(s) {
    if (!s) return null;
    const str = String(s).slice(0, 10);
    const [y, m, d] = str.split('-').map(v => parseInt(v, 10));
    if (!Number.isFinite(y) || !Number.isFinite(m) || !Number.isFinite(d)) return null;
    return new Date(y, m - 1, d);
  }

  function toYMD(s) {
    const d = parseLocalDate(s);
    if (!d) return '';
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
  }

  /** Reglas base-30 usando el MES ANTERIOR a la FECHA DEL RECIBO */
  function diasMesBase30(fechaAfiliacionStr, fechaBaseStr, fechaRetiroStr = null) {
    if (!fechaAfiliacionStr || !fechaBaseStr) return 0;

    const fBase = parseLocalDate(fechaBaseStr);
    if (!fBase) return 0;

    const inicioMes = new Date(fBase.getFullYear(), fBase.getMonth() - 1, 1);
    const finMes    = new Date(fBase.getFullYear(), fBase.getMonth() - 1, 30);

    const af  = parseLocalDate(fechaAfiliacionStr);
    const ret = fechaRetiroStr ? parseLocalDate(fechaRetiroStr) : null;
    if (!af) return 0;

    let startDay;
    if (af <= inicioMes) {
      startDay = 1;
    } else if (af >= inicioMes && af <= finMes) {
      startDay = Math.min(af.getDate(), 30);
    } else {
      return 0;
    }

    let endDay = 30;
    if (ret && ret >= inicioMes && ret <= finMes) {
      endDay = Math.min(ret.getDate(), 30);
    }

    if (startDay === 1 && endDay === 30) return 30;

    // Sin retiro válido → inclusivo hasta 30
    if (!ret || ret < inicioMes || ret > finMes) {
      return Math.max(0, (30 - startDay + 1));
    }

    // Con retiro → inclusivo en ambos extremos
    return Math.max(0, endDay - startDay + 1);
  }

  /** Cálculo SIEMPRE con valores de BD; "otros_servicios" es manual */
  function calcularValores(usuario, dias) {
    const safeNumber = (v, def = 0) => {
      const n = parseFloat(v);
      return Number.isFinite(n) ? n : def;
    };
    const fmt = new Intl.NumberFormat('es-CO');

    // Bases SIEMPRE desde BD
    const sueldoBase = safeNumber(usuario?.sueldo, 0);
    const admonBase  = safeNumber(usuario?.admon, 0);

    // Refleja en inputs de solo lectura (solo mostrar)
    const sueldoInput = document.getElementById('sueldo');
    const admonInput  = document.getElementById('admon');
    if (sueldoInput) sueldoInput.value = Math.round(sueldoBase).toString();
    if (admonInput)  admonInput.value  = Math.round(admonBase).toString();

    // Porcentajes
    const porcEPS     = safeNumber(usuario?.eps?.porcentaje, 0);
    const porcARL     = safeNumber(usuario?.arl?.porcentaje, 0);
    const porcPension = safeNumber(usuario?.pension?.porcentaje, 0);
    const porcCaja    = safeNumber(usuario?.caja?.porcentaje, 0);

    const calc = (p) => Math.round((sueldoBase * (p / 100) / 30 * dias) / 100) * 100;

    const eps     = calc(porcEPS);
    const arl     = calc(porcARL);
    const pension = calc(porcPension);

    // Regla de caja según tu lógica actual
    let caja = 0;
    const nombreCaja = (usuario?.caja?.nombre || '').toString().toLowerCase();
    if (nombreCaja === 'comfandi') {
      caja = calc(porcCaja);
    } else if (usuario?.caja) {
      caja = 100;
    } else {
      caja = 0;
    }

    // Administración desde base BD (redondeada a centenas)
    const admonCalc = Math.round(admonBase / 100) * 100;

    const exequial = Math.round(safeNumber(usuario?.seg_exequial, 0) / 100) * 100;
    const mora     = Math.round(safeNumber(usuario?.mora, 0) / 100) * 100;

    const otrosInput = document.getElementById('otros_servicios');
    const otros = Math.round(safeNumber(otrosInput?.value, 0) / 100) * 100;

    const total = eps + arl + pension + caja + admonCalc + exequial + mora + otros;

    // Refresca UI
    document.getElementById('dias_liquidar').value   = dias;
    document.getElementById('valor_eps').value       = eps.toFixed(0);
    document.getElementById('valor_arl').value       = arl.toFixed(0);
    document.getElementById('valor_pension').value   = pension.toFixed(0);
    document.getElementById('valor_caja').value      = caja.toFixed(0);
    document.getElementById('valor_admon').value     = admonCalc.toFixed(0);
    document.getElementById('valor_exequial').value  = exequial.toFixed(0);
    document.getElementById('valor_mora').value      = mora.toFixed(0);
    document.getElementById('total').value           = fmt.format(total);
  }

  document.getElementById('btnBuscar').addEventListener('click', async function () {
    const numero = document.getElementById('numero').value.trim();
    if (!numero) {
      alert('Ingrese un número de documento');
      return;
    }

    try {
      const response = await axios.get(`/recibos/buscar-usuario/${numero}`);
      const usuario = response.data;
      usuarioCargado = usuario;

      const fechaBase = document.querySelector('input[name="fecha"]').value;
      const dias = diasMesBase30(usuario.fecha_afiliacion, fechaBase);

      document.getElementById('usuario_externo_id').value = usuario.id;
      document.getElementById('nombre_completo').value =
        `${usuario.primer_nombre ?? ''} ${usuario.primer_apellido ?? ''}`.trim();

      document.getElementById('fecha_afiliacion').value = toYMD(usuario.fecha_afiliacion);

      // Mostrar las bases desde BD
      document.getElementById('sueldo').value = (parseFloat(usuario.sueldo) || 0).toFixed(0);
      document.getElementById('admon').value  = (parseFloat(usuario.admon)  || 0).toFixed(0);

      calcularValores(usuario, dias);
      document.getElementById('datosUsuario').style.display = 'block';
    } catch (error) {
      console.error(error);
      alert('Usuario no encontrado');
      document.getElementById('datosUsuario').style.display = 'none';
      usuarioCargado = null;
    }
  });

  document.getElementById('novedad').addEventListener('change', function () {
    const campo       = document.getElementById('campoFechaRetiro');
    const campoRetiro = document.getElementById('fecha_retiro');

    if (this.value === 'Retiro') {
      campo.style.display = 'block';
      campoRetiro.removeAttribute('disabled');
    } else {
      campo.style.display = 'none';
      campoRetiro.value = '';
      campoRetiro.setAttribute('disabled', true);
    }

    if (usuarioCargado) {
      const fechaRecibo = document.querySelector('input[name="fecha"]').value;
      const fechaRetiro = (this.value === 'Retiro') ? (campoRetiro.value || null) : null;
      const dias = diasMesBase30(usuarioCargado.fecha_afiliacion, fechaRecibo, fechaRetiro);
      calcularValores(usuarioCargado, dias);
    }
  });

  // ===== Listener ÚNICO y AJUSTADO para fecha_retiro =====
  document.getElementById('fecha_retiro').addEventListener('change', function () {
    if (!usuarioCargado) return;

    const fechaRetiroStr = this.value || null;
    const fechaReciboInp = document.querySelector('input[name="fecha"]');
    let   fechaRecibo    = fechaReciboInp.value;

    if (!fechaRetiroStr || !fechaRecibo) return;

    const fRet = parseLocalDate(fechaRetiroStr);
    let   fRec = parseLocalDate(fechaRecibo);

    // Si el mes del recibo == mes del retiro → mover recibo al mes siguiente (día 1)
    if (fRet.getFullYear() === fRec.getFullYear() && fRet.getMonth() === fRec.getMonth()) {
      const sugerida = new Date(fRec.getFullYear(), fRec.getMonth() + 1, 1);
      const mm = String(sugerida.getMonth() + 1).padStart(2, '0');
      const dd = String(sugerida.getDate()).padStart(2, '0');
      const ymd = `${sugerida.getFullYear()}-${mm}-${dd}`;
      alert(`Para liquidar ${fRet.getFullYear()}-${String(fRet.getMonth()+1).padStart(2,'0')} el recibo debe ir en el mes siguiente. Se ajustará a ${ymd}.`);
      fechaReciboInp.value = ymd;
      fechaRecibo = ymd;
      fRec = parseLocalDate(fechaRecibo); // ← Recalcula referencia
    }

    // Con la nueva fecha del recibo (si cambió), valida que el retiro esté en el MES ANTERIOR (1..30)
    const inicioMes = new Date(fRec.getFullYear(), fRec.getMonth() - 1, 1);
    const finMes    = new Date(fRec.getFullYear(), fRec.getMonth() - 1, 30);

    // 1) Rango válido 1..30 del mes base
    if (fRet < inicioMes || fRet > finMes) {
      alert('⚠️ La fecha de retiro debe estar dentro del mes anterior al recibo (1..30).');
      this.value = '';
      const dias = diasMesBase30(usuarioCargado.fecha_afiliacion, fechaRecibo, null);
      calcularValores(usuarioCargado, dias);
      return;
    }

    // 2) No permitir retiro < afiliación cuando la afiliación cae en el mes base
    const afStr = document.getElementById('fecha_afiliacion').value; // ya está en YYYY-MM-DD
    const fAf   = parseLocalDate(afStr);

    if (fAf && fAf >= inicioMes && fAf <= finMes && fRet < fAf) {
      const dd = String(fAf.getDate()).padStart(2, '0');
      const mm = String(fAf.getMonth() + 1).padStart(2, '0');
      const yy = fAf.getFullYear();
      alert(`⚠️ La fecha de retiro no puede ser anterior a la fecha de afiliación (${yy}-${mm}-${dd}) en el mes a liquidar.`);
      this.value = '';
      const dias = diasMesBase30(usuarioCargado.fecha_afiliacion, fechaRecibo, null);
      calcularValores(usuarioCargado, dias);
      return;
    }

    const dias = diasMesBase30(usuarioCargado.fecha_afiliacion, fechaRecibo, fechaRetiroStr);
    calcularValores(usuarioCargado, dias);
  });

  document.querySelector('input[name="fecha"]').addEventListener('change', function () {
    if (!usuarioCargado) return;

    const fechaRecibo = this.value;
    const novedad     = document.getElementById('novedad').value;
    const fechaRetiro = (novedad === 'Retiro')
      ? document.getElementById('fecha_retiro').value || null
      : null;

    const dias = diasMesBase30(usuarioCargado.fecha_afiliacion, fechaRecibo, fechaRetiro);
    calcularValores(usuarioCargado, dias);
  });

  document.getElementById('otros_servicios').addEventListener('input', function () {
    if (!usuarioCargado) return;
    const dias = parseInt(document.getElementById('dias_liquidar').value || 0, 10);
    calcularValores(usuarioCargado, dias);
  });

  // Habilitar fecha_retiro en submit si corresponde
  document.querySelector('form').addEventListener('submit', function () {
    const novedad     = document.getElementById('novedad').value;
    const campoRetiro = document.getElementById('fecha_retiro');
    if (novedad === 'Retiro') {
      campoRetiro.removeAttribute('disabled');
    }
  });
</script>

@endpush
