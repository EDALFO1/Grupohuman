<div class="mb-3">
    <label for="numero" class="form-label fw-bold">Número</label>
    <input type="text" name="numero" class="form-control @error('numero') is-invalid @enderror"
           value="{{ old('numero', $factura->numero ?? '') }}">
    @error('numero')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="fecha_emision" class="form-label fw-bold">Fecha de Emisión</label>
    <input type="date" name="fecha_emision" class="form-control @error('fecha_emision') is-invalid @enderror"
           value="{{ old('fecha_emision', isset($factura) ? $factura->fecha_emision->format('Y-m-d') : '') }}">
    @error('fecha_emision')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="moneda" class="form-label fw-bold">Moneda</label>
    <select name="moneda" class="form-control @error('moneda') is-invalid @enderror">
        <option value="">Seleccione...</option>
        <option value="COP" {{ old('moneda', $factura->moneda ?? 'COP') == 'COP' ? 'selected' : '' }}>COP - Peso Colombiano</option>
        <option value="USD" {{ old('moneda', $factura->moneda ?? '') == 'USD' ? 'selected' : '' }}>USD - Dólar</option>
    </select>
    @error('moneda')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>


<div class="mb-3">
    <label for="empresa_local_id" class="form-label fw-bold">Empresa Local</label>
    <select name="empresa_local_id" class="form-control @error('empresa_local_id') is-invalid @enderror">
        <option value="">Seleccione...</option>
        @foreach($empresasLocales as $empresa)
            <option value="{{ $empresa->id }}" {{ old('empresa_local_id', $factura->empresa_local_id ?? '') == $empresa->id ? 'selected' : '' }}>
                {{ $empresa->nombre }}
            </option>
        @endforeach
    </select>
    @error('empresa_local_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="cliente_id" class="form-label fw-bold">Cliente</label>
    <select name="cliente_id" class="form-control @error('cliente_id') is-invalid @enderror">
        <option value="">Seleccione...</option>
        @foreach($clientes as $cliente)
            <option value="{{ $cliente->id }}" {{ old('cliente_id', $factura->cliente_id ?? '') == $cliente->id ? 'selected' : '' }}>
                {{ $cliente->nombre }}
            </option>
        @endforeach
    </select>
    @error('cliente_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- BLOQUE DE PRODUCTOS --}}
<h5 class="mt-4">Productos</h5>
<table class="table table-bordered" id="tabla-productos">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
            <th>Subtotal</th>
            <th>
                <button type="button" class="btn btn-sm btn-success" onclick="agregarProducto()">+</button>
            </th>
        </tr>
    </thead>
    <tbody id="productos-container">
        <!-- Filas dinámicas JS -->
    </tbody>
</table>
<input type="hidden" name="productos_data" id="productos_data">

{{-- BLOQUE DE TOTALES --}}
<div class="mb-3">
    <label for="subtotal" class="form-label fw-bold">Subtotal</label>
    <input type="number" step="0.01" name="subtotal" class="form-control @error('subtotal') is-invalid @enderror"
       value="{{ old('subtotal', $factura->subtotal ?? '') }}" readonly>
    @error('subtotal')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="iva" class="form-label fw-bold">IVA</label>
    <input type="number" step="0.01" name="iva" class="form-control @error('iva') is-invalid @enderror"
       value="{{ old('iva', $factura->iva ?? 0) }}" readonly>
    @error('iva')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="retencion" class="form-label fw-bold">Retención</label>
    <input type="number" step="0.01" name="retencion" class="form-control @error('retencion') is-invalid @enderror"
           value="{{ old('retencion', $factura->retencion ?? 0) }}">
    @error('retencion')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="descuento" class="form-label fw-bold">Descuento</label>
    <input type="number" step="0.01" name="descuento" class="form-control @error('descuento') is-invalid @enderror"
           value="{{ old('descuento', $factura->descuento ?? 0) }}">
    @error('descuento')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="total" class="form-label fw-bold">Total</label>
    <input type="number" step="0.01" name="total" class="form-control @error('total') is-invalid @enderror"
       value="{{ old('total', $factura->total ?? '') }}" readonly>
    @error('total')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="tipo" class="form-label fw-bold">Tipo de Factura</label>
    <select name="tipo" class="form-control @error('tipo') is-invalid @enderror">
        <option value="Factura" {{ old('tipo', $factura->tipo ?? '') == 'Factura' ? 'selected' : '' }}>Factura</option>
        <option value="Nota Crédito" {{ old('tipo', $factura->tipo ?? '') == 'Nota Crédito' ? 'selected' : '' }}>Nota Crédito</option>
        <option value="Nota Débito" {{ old('tipo', $factura->tipo ?? '') == 'Nota Débito' ? 'selected' : '' }}>Nota Débito</option>
    </select>
    @error('tipo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
