@php
  $u = $arlUsuario ?? new \App\Models\ArlUsuario();
@endphp

<div class="row">
  <div class="col-md-4">
    <label>Tipo documento</label>
    <select name="documento_id" class="form-control" required>
      <option value="">Seleccione</option>
      @foreach($documentos as $d)
        <option value="{{ $d->id }}" {{ old('documento_id', $u->documento_id) == $d->id ? 'selected' : '' }}>
          {{ $d->nombre }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-md-4">
    <label>Número documento</label>
    <input type="text" name="numero" class="form-control" value="{{ old('numero', $u->numero) }}" required>
  </div>

  <div class="col-md-4">
    <label>Nombre completo</label>
    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $u->nombre) }}" required>
  </div>
</div>

<div class="row mt-3">
  <div class="col-md-4">
    <label>Fecha ingreso</label>
    <input type="date" name="fecha_ingreso" class="form-control"
           value="{{ old('fecha_ingreso', optional($u->fecha_ingreso)->format('Y-m-d')) }}" required>
  </div>

  <div class="col-md-4">
    <label>Nivel ARL</label>
    <select name="arl_id" id="arl_id" class="form-control" required>
      <option value="">Seleccione</option>
      @foreach($arls as $a)
        <option value="{{ $a->id }}" data-porc="{{ $a->porcentaje }}" {{ old('arl_id', $u->arl_id) == $a->id ? 'selected' : '' }}>
          {{ $a->nombre }} @if($a->nivel) (Nivel {{ $a->nivel }}) @endif - {{ number_format($a->porcentaje,2) }}%
        </option>
      @endforeach
    </select>
  </div>

  <div class="col-md-4">
    <label>Empresa externa</label>
    <select name="empresa_externa_id" class="form-control" required>
      <option value="">Seleccione</option>
      @foreach($empresaExternas as $e)
        <option value="{{ $e->id }}" {{ old('empresa_externa_id', $u->empresa_externa_id) == $e->id ? 'selected' : '' }}>
          {{ $e->nombre }}
        </option>
      @endforeach
    </select>
  </div>
</div>

<div class="row mt-3">
  <div class="col-md-4">
    <label>Base cotización</label>
    <input type="number" step="0.01" name="base_cotizacion" id="base_cotizacion" class="form-control"
           value="{{ old('base_cotizacion', $u->base_cotizacion) }}">
    <small class="text-muted">Si se deja en 0, se usará la base vigente de la empresa.</small>
  </div>

  <div class="col-md-4">
    <label>Administración</label>
    <input type="number" step="0.01" name="administracion" id="administracion" class="form-control"
           value="{{ old('administracion', $u->administracion) }}">
    <small class="text-muted">Si se deja en 0, se usará el valor vigente de la empresa.</small>
  </div>

  <div class="col-md-4">
    <label>Valor (ARL + Adm)</label>
    <input type="text" id="valor_calculado" class="form-control" value="0.00" readonly>
  </div>
</div>

<div class="row mt-3">
  <div class="col-md-4">
    <label>Estado</label>
    <select name="estado" class="form-control" required>
      <option value="1" {{ old('estado', $u->estado ?? 1) == 1 ? 'selected' : '' }}>Activo</option>
      <option value="0" {{ old('estado', $u->estado ?? 1) == 0 ? 'selected' : '' }}>Inactivo</option>
    </select>
  </div>

  <div class="col-md-4">
    <label>Fecha de retiro</label>
    <input type="date" name="fecha_retiro" class="form-control"
           value="{{ old('fecha_retiro', optional($u->fecha_retiro)->format('Y-m-d')) }}">
  </div>

 
</div>


@push('scripts')
<script>
(function(){
  function n(v){ return isNaN(v) ? 0 : parseFloat(v); }
  function fmt0(v){ 
    try { return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(v); } 
    catch(e){ return (Math.round(v)).toString(); }
  }

  const selArl = document.getElementById('arl_id');
  const inputBase = document.getElementById('base_cotizacion');
  const inputAdm  = document.getElementById('administracion');
  const outValor  = document.getElementById('valor_calculado');

  function calc(){
    const opt  = selArl ? selArl.options[selArl.selectedIndex] : null;
    const porc = opt ? n(opt.getAttribute('data-porc')) : 0;
    const base = n(inputBase?.value);
    const adm  = n(inputAdm?.value);

    const bruto = (base * (porc/100)) + adm;
    const redondeado = Math.round(bruto/100) * 100; // ← al centenar más cercano

    if(outValor) outValor.value = fmt0(redondeado); // sin decimales
  }

  ['change','keyup','blur'].forEach(ev=>{
    selArl?.addEventListener(ev, calc);
    inputBase?.addEventListener(ev, calc);
    inputAdm?.addEventListener(ev, calc);
  });

  calc(); // inicial
})();
</script>
@endpush


