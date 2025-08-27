@csrf

<div class="mb-3">
    <label for="numero" class="form-label">Número de Documento del Usuario</label>
    <div class="input-group">
        <input type="text" class="form-control" id="numero" name="numero" placeholder="Buscar por número..." required>
        <button type="button" id="btnBuscar" class="btn btn-secondary">Buscar</button>
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
            <label class="form-label">Salario</label>
            <input type="text" class="form-control" id="sueldo" disabled>
        </div>

        <div class="col-md-6">
            <label class="form-label">Días a Liquidar</label>
            <input type="text" class="form-control" id="dias_liquidar" disabled>
        </div>
    </div>

    <hr>
    <h5>Valores Calculados</h5>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">EPS</label><input type="text" id="valor_eps" class="form-control" disabled></div>
        <div class="col-md-3"><label class="form-label">ARL</label><input type="text" id="valor_arl" class="form-control" disabled></div>
        <div class="col-md-3"><label class="form-label">Pensión</label><input type="text" id="valor_pension" class="form-control" disabled></div>
        <div class="col-md-3"><label class="form-label">Caja</label><input type="text" id="valor_caja" class="form-control" disabled></div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-3"><label class="form-label">Administración</label><input type="text" id="valor_admon" class="form-control" disabled></div>
        <div class="col-md-3"><label class="form-label">Exequial</label><input type="text" id="valor_exequial" class="form-control" disabled></div>
        <div class="col-md-3"><label class="form-label">Mora</label><input type="text" id="valor_mora" class="form-control" disabled></div>
        <div class="col-md-3">
    <label class="form-label">Otros Servicios</label>
    <input type="number" step="100" min="0" id="otros_servicios" name="otros_servicios" class="form-control" value="{{ old('otros_servicios', 0) }}" required>
</div>

    </div>

    <div class="mt-4">
        <label class="form-label"><strong>Total</strong></label>
        <input type="text" id="total" class="form-control form-control-lg fw-bold" disabled>
    </div>

    <div class="mt-4 row">
        <div class="col-md-6">
            <label for="fecha" class="form-label">Fecha de la Remisión</label>
            <input type="date" name="fecha" class="form-control" value="{{ old('fecha', now()->format('Y-m-d')) }}" required>
        </div>

        <div class="col-md-6">
            <label for="novedad" class="form-label">Novedad</label>
            <select name="novedad" id="novedad" class="form-control" required>
                <option value="Ingreso" selected>Ingreso</option>
                <option value="Retiro">Retiro</option>
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

  /** Parse YYYY-MM-DD en zona local (evita parseo UTC) */
  function parseLocalDate(s) {
    if (!s) return null;
    const str = String(s).slice(0, 10); // 'YYYY-MM-DD'
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

  function toHundreds(n) {
    const x = parseFloat(n);
    if (!Number.isFinite(x)) return 0;
    return Math.round(x / 100) * 100;
  }

  // === Regla de días base 30 usando el MES ANTERIOR a la fecha de remisión (INCLUSIVO) ===
  function diasMesBase30(fechaAfiliacionStr, fechaBaseStr, fechaRetiroStr = null) {
    if (!fechaAfiliacionStr || !fechaBaseStr) return 0;

    const fBase = parseLocalDate(fechaBaseStr);
    if (!fBase) return 0;

    // Mes anterior a la FECHA BASE (remisión/recibo) y tope en 30
    const inicioMes = new Date(fBase.getFullYear(), fBase.getMonth() - 1, 1);
    const finMes    = new Date(fBase.getFullYear(), fBase.getMonth() - 1, 30);

    const af  = parseLocalDate(fechaAfiliacionStr);
    const ret = fechaRetiroStr ? parseLocalDate(fechaRetiroStr) : null;
    if (!af) return 0;

    // Si afiliación es posterior al mes base → 0
    if (af > finMes) return 0;

    // Día de inicio
    let startDay;
    if (af <= inicioMes) {
      startDay = 1;
    } else if (af >= inicioMes && af <= finMes) {
      startDay = Math.min(af.getDate(), 30);
    } else {
      return 0;
    }

    // Día de fin
    let endDay = 30;
    if (ret && ret >= inicioMes && ret <= finMes) {
      endDay = Math.min(ret.getDate(), 30);
    }

    if (startDay === 1 && endDay === 30) return 30;

    // 🔧 Sin retiro → inclusivo (cuenta el día de inicio)
    if (!ret || ret < inicioMes || ret > finMes) {
      return Math.max(0, (30 - startDay + 1));
    }

    // Con retiro → inclusivo en ambos extremos
    return Math.max(0, endDay - startDay + 1);
  }

  function calcularValores(usuario, dias) {
    const safeNumber = (v, def = 0) => {
      const n = parseFloat(v);
      return Number.isFinite(n) ? n : def;
    };

    const sueldo = safeNumber(usuario?.sueldo, 0);

    const porcEPS     = safeNumber(usuario?.eps?.porcentaje, 0);
    const porcARL     = safeNumber(usuario?.arl?.porcentaje, 0);
    const porcPension = safeNumber(usuario?.pension?.porcentaje, 0);
    const porcCaja    = safeNumber(usuario?.caja?.porcentaje, 0);

    const calc = (p) => Math.round((sueldo * (p / 100) / 30 * dias) / 100) * 100;

    const eps     = calc(porcEPS);
    const arl     = calc(porcARL);
    const pension = calc(porcPension);

    let caja = 0;
    const nombreCaja = (usuario?.caja?.nombre || '').toString().toLowerCase();
    if (nombreCaja === 'comfandi') {
      caja = calc(porcCaja);
    } else if (usuario?.caja) {
      // Si hay caja pero no es Comfandi, tu regla actual pone 100
      caja = 100;
    } else {
      caja = 0;
    }

    const admon    = Math.round(safeNumber(usuario?.admon, 0) / 100) * 100;
    const exequial = Math.round(safeNumber(usuario?.seg_exequial, 0) / 100) * 100;
    const mora     = Math.round(safeNumber(usuario?.mora, 0) / 100) * 100;

    const otrosInput = document.getElementById('otros_servicios');
    const otros = Math.round(safeNumber(otrosInput?.value, 0) / 100) * 100;

    const total = eps + arl + pension + caja + admon + exequial + mora + otros;

    document.getElementById('dias_liquidar').value   = dias;
    document.getElementById('valor_eps').value       = eps.toFixed(0);
    document.getElementById('valor_arl').value       = arl.toFixed(0);
    document.getElementById('valor_pension').value   = pension.toFixed(0);
    document.getElementById('valor_caja').value      = caja.toFixed(0);
    document.getElementById('valor_admon').value     = admon.toFixed(0);
    document.getElementById('valor_exequial').value  = exequial.toFixed(0);
    document.getElementById('valor_mora').value      = mora.toFixed(0);
    document.getElementById('total').value           = total.toFixed(0);
  }

  document.getElementById('btnBuscar').addEventListener('click', async function () {
    const numero = document.getElementById('numero').value.trim();
    if (!numero) {
      alert('Ingrese un número de documento');
      return;
    }

    try {
      const { data: usuario } = await axios.get(`/remisiones/buscar-usuario/${numero}`);
      if (!usuario || !usuario.id) throw new Error('Respuesta inválida');
      usuarioCargado = usuario;

      const fechaBase = document.querySelector('input[name="fecha"]').value; // remisión
      const dias = diasMesBase30(usuario.fecha_afiliacion, fechaBase);

      document.getElementById('usuario_externo_id').value = usuario.id;
      document.getElementById('nombre_completo').value =
        `${usuario.primer_nombre ?? ''} ${usuario.primer_apellido ?? ''}`.trim();

      // Muestra la fecha como YYYY-MM-DD
      document.getElementById('fecha_afiliacion').value = toYMD(usuario.fecha_afiliacion);

      // sueldo en número entero
      document.getElementById('sueldo').value = (parseFloat(usuario.sueldo) || 0).toFixed(0);

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
    const campo = document.getElementById('campoFechaRetiro');
    const campoRetiro = document.getElementById('fecha_retiro');

    if (this.value === 'Retiro') {
      campo.style.display = 'block';
      campoRetiro.removeAttribute('disabled');
    } else {
      campo.style.display = 'none';
      campoRetiro.value = '';
      campoRetiro.setAttribute('disabled', true);
    }

    // Recalcular días/valores al cambiar novedad
    if (usuarioCargado) {
      const fechaRemision = document.querySelector('input[name="fecha"]').value;
      const fechaRetiro   = (this.value === 'Retiro') ? (campoRetiro.value || null) : null;
      const dias = diasMesBase30(usuarioCargado.fecha_afiliacion, fechaRemision, fechaRetiro);
      calcularValores(usuarioCargado, dias);
    }
  });

  document.getElementById('fecha_retiro').addEventListener('change', function () {
    if (!usuarioCargado) return;

    const fechaRetiroStr = this.value || null;
    const fechaRemision  = document.querySelector('input[name="fecha"]').value;
    if (!fechaRetiroStr || !fechaRemision) return;

    const fRet = parseLocalDate(fechaRetiroStr);
    const fRem = parseLocalDate(fechaRemision);

    const inicioMes = new Date(fRem.getFullYear(), fRem.getMonth() - 1, 1);
    const finMes    = new Date(fRem.getFullYear(), fRem.getMonth() - 1, 30); // base-30

    if (fRet < inicioMes || fRet > finMes) {
      alert('⚠️ La fecha de retiro debe estar dentro del mes anterior a la remisión (1..30).');
      this.value = '';
      // Recalcular como si no hubiera retiro
      const dias = diasMesBase30(usuarioCargado.fecha_afiliacion, fechaRemision, null);
      calcularValores(usuarioCargado, dias);
      return;
    }

    const dias = diasMesBase30(usuarioCargado.fecha_afiliacion, fechaRemision, fechaRetiroStr);
    calcularValores(usuarioCargado, dias);
  });

  // 🔁 Recalcular al escribir/cambiar en "Otros Servicios" (y normalizar en blur)
  (function setupOtrosServiciosRealtime() {
    const otrosInput = document.getElementById('otros_servicios');
    if (!otrosInput) return;

    // Evita notación científica y signos
    otrosInput.addEventListener('keydown', (e) => {
      if (['e','E','+','-','.'].includes(e.key)) e.preventDefault();
    });

    // Si tiene 0 al enfocar, seleccionar para que se reemplace al escribir
    otrosInput.addEventListener('focus', function () {
      if (this.value === '0') this.select();
    });

    // Limpia ceros a la izquierda y caracteres no numéricos mientras escribe
    ['input', 'change', 'keyup'].forEach(evt => {
      otrosInput.addEventListener(evt, function () {
        // Deja solo dígitos
        let v = String(this.value).replace(/[^\d]/g, '');
        // Quita ceros a la izquierda (pero deja "0" si está vacío)
        v = v.replace(/^0+(?=\d)/, '');
        if (v === '') v = '0';
        this.value = v;

        if (usuarioCargado) {
          const dias = parseInt(document.getElementById('dias_liquidar').value || 0);
          calcularValores(usuarioCargado, dias);
        } else {
          // Fallback si aún no hay usuario cargado
          const ids = ['valor_eps','valor_arl','valor_pension','valor_caja','valor_admon','valor_exequial','valor_mora'];
          const suma = ids.map(id => parseFloat(document.getElementById(id)?.value || 0))
                          .reduce((a,b)=>a+b,0);
          const toHundreds = (n) => Math.round((parseFloat(n)||0)/100)*100;
          document.getElementById('total').value = (suma + toHundreds(this.value)).toFixed(0);
        }
      });
    });

    // Al salir del campo, normaliza a centenas y recalcula
    otrosInput.addEventListener('blur', function () {
      const toHundreds = (n) => Math.round((parseFloat(n)||0)/100)*100;
      this.value = String(toHundreds(this.value));
      if (usuarioCargado) {
        const dias = parseInt(document.getElementById('dias_liquidar').value || 0);
        calcularValores(usuarioCargado, dias);
      }
    });
  })();

  document.querySelector('input[name="fecha"]').addEventListener('change', function () {
    if (!usuarioCargado) return;
    const fechaRemision = this.value;
    const novedad = document.getElementById('novedad').value;
    const fechaRetiro = (novedad === 'Retiro') ? document.getElementById('fecha_retiro').value || null : null;

    const dias = diasMesBase30(usuarioCargado.fecha_afiliacion, fechaRemision, fechaRetiro);
    calcularValores(usuarioCargado, dias);
  });
</script>




@endpush
