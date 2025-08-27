@extends('layouts.main')

@section('contenido')

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Crear Factura</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Agregar Factura</h5>

                        <form action="{{ route('facturas.store') }}" method="POST">
                            @csrf
                            @include('facturas.form')

                            <button type="submit" class="btn btn-success">Guardar</button>
                            <a href="{{ route('facturas') }}" class="btn btn-secondary">Cancelar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

@push('scripts')
<script>
    let productos = @json($productos);

    function agregarProducto(productoId = '', cantidad = 1, precio = '', subtotal = '') {
        const container = document.getElementById('productos-container');
        const index = container.children.length;

        let fila = document.createElement('tr');

        fila.innerHTML = `
            <td>
                <select name="productos[${index}][producto_id]" class="form-control" onchange="actualizarPrecio(this, ${index})">
                    <option value="">Seleccione</option>
                    ${productos.map(prod => `
                        <option value="${prod.id}" ${prod.id == productoId ? 'selected' : ''}>${prod.nombre}</option>
                    `).join('')}
                </select>
            </td>
            <td><input type="number" name="productos[${index}][cantidad]" value="${cantidad}" class="form-control" min="1" onchange="recalcularLinea(${index})"></td>
            <td><input type="number" step="0.01" name="productos[${index}][precio_unitario]" value="${precio}" class="form-control" onchange="recalcularLinea(${index})"></td>
            <td><input type="number" step="0.01" name="productos[${index}][subtotal]" value="${subtotal}" class="form-control" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)">-</button></td>
        `;

        container.appendChild(fila);
        recalcularFactura();
    }

    function actualizarPrecio(select, index) {
        const productoId = select.value;
        const producto = productos.find(p => p.id == productoId);
        if (producto) {
            const row = select.closest('tr');
            row.querySelector(`[name="productos[${index}][precio_unitario]"]`).value = producto.precio_unitario;
            recalcularLinea(index);
        }
    }

    function recalcularLinea(index) {
        const row = document.querySelector(`#productos-container tr:nth-child(${index + 1})`);
        const precio = parseFloat(row.querySelector(`[name="productos[${index}][precio_unitario]"]`)?.value || 0);
        const cantidad = parseFloat(row.querySelector(`[name="productos[${index}][cantidad]"]`)?.value || 1);
        const subtotal = (precio * cantidad).toFixed(2);
        row.querySelector(`[name="productos[${index}][subtotal]"]`).value = subtotal;
        recalcularFactura();
    }

    function eliminarFila(btn) {
        btn.closest('tr').remove();
        recalcularFactura();
    }

    function recalcularFactura() {
        let subtotal = 0;
        let iva = 0;

        document.querySelectorAll('#productos-container tr').forEach((row, index) => {
            const productoSelect = row.querySelector(`[name="productos[${index}][producto_id]"]`);
            const productoId = productoSelect?.value;
            const producto = productos.find(p => p.id == productoId);

            const cantidad = parseFloat(row.querySelector(`[name="productos[${index}][cantidad]"]`)?.value || 1);
            const precio = parseFloat(row.querySelector(`[name="productos[${index}][precio_unitario]"]`)?.value || 0);

            subtotal += cantidad * precio;

            if (producto && producto.iva > 0) {
                iva += (precio * cantidad * (producto.iva / 100));
            }
        });

        document.querySelector('[name="subtotal"]').value = subtotal.toFixed(2);
        document.querySelector('[name="iva"]').value = iva.toFixed(2);

        const retencion = parseFloat(document.querySelector('[name="retencion"]')?.value || 0);
        const descuento = parseFloat(document.querySelector('[name="descuento"]')?.value || 0);

        const total = subtotal + iva - retencion - descuento;
        document.querySelector('[name="total"]').value = total.toFixed(2);
    }

    document.querySelector('[name="retencion"]').addEventListener('input', recalcularFactura);
    document.querySelector('[name="descuento"]').addEventListener('input', recalcularFactura);
</script>
@endpush

@endsection
