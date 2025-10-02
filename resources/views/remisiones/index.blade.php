@extends('layouts.main')
@section('contenido')

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Listado de Remisiones</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card mb-3">
                    <div class="card-body d-flex align-items-center gap-3">
                        <label for="periodoSelector" class="mb-0">Periodo:</label>
                        <input type="month" id="periodoSelector" class="form-control" style="width:180px;" value="{{ $period }}">
                        <div class="ms-auto">
                            <a href="{{ route('remisiones.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-circle-plus"></i> Crear Nueva Remisión
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <hr>

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <hr>

                        <table class="table datatable" id="tablaRemisiones">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Número Doc</th>
                                    <th>Días</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($remisiones as $remision)
                                    <tr>
                                        <td>{{ $remision->numero }}</td>
                                        <td>{{ \Carbon\Carbon::parse($remision->fecha)->format('d/m/Y') }}</td>
                                        <td>{{ optional($remision->usuarioExterno)->primer_nombre ?? '' }} {{ optional($remision->usuarioExterno)->primer_apellido ?? '' }}</td>
                                        <td>{{ optional($remision->usuarioExterno)->numero_documento ?? optional($remision->usuarioExterno)->numero ?? '' }}</td>
                                        <td>{{ $remision->dias_liquidar }}</td>
                                        <td>${{ number_format($remision->total, 2, ',', '.') }}</td>
                                        <td class="d-flex flex-wrap gap-1">
                                            <a href="{{ route('remisiones.edit', $remision->id) }}" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="{{ route('remisiones.imprimir', $remision->id) }}" class="btn btn-info btn-sm" target="_blank">
                                                <i class="fas fa-print"></i> Imprimir
                                            </a>
                                            <form action="{{ route('remisiones.destroy', $remision->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta remisión?')" class="d-inline eliminar-remision-form">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Paginación --}}
                        <div class="d-flex justify-content-center mt-3">
                            {{ $remisiones->links() }}
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>
</main>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('periodoSelector');
    const tbody = document.querySelector('#tablaRemisiones tbody');

    // Obtiene CSRF token desde meta (asegúrate de que tu layout tenga <meta name="csrf-token" content="{{ csrf_token() }}">)
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const CSRF_TOKEN = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    selector.addEventListener('change', async function() {
        const period = this.value; // 'YYYY-MM'
        if (!period) return;

        try {
            const resp = await axios.get('{{ route("remisiones.api.period") }}', { params: { period }});
            const rems = resp.data.remisiones || [];
            tbody.innerHTML = '';

            if (rems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3">No hay remisiones en este periodo.</td></tr>';
                return;
            }

            rems.forEach(r => {
                const fecha = r.fecha ? (r.fecha.substring(0,10) || '') : '';
                const usuario = r.usuario_externo ? ((r.usuario_externo.primer_nombre||'') + ' ' + (r.usuario_externo.primer_apellido||'')).trim() : '';
                const documento = r.usuario_externo ? (r.usuario_externo.numero_documento || r.usuario_externo.numero || '') : '';
                const dias = r.dias_liquidar ?? (r.dias ?? '');
                const total = (r.total ?? 0);

                // Construir formulario de eliminar con token CSRF (nota: acción y método)
                const deleteForm = `
                    <form action="/remisiones/${r.id}" method="POST" onsubmit="return confirm('¿Eliminar esta remisión?')" class="d-inline eliminar-remision-form">
                        <input type="hidden" name="_token" value="${CSRF_TOKEN}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-danger btn-sm" type="submit">
                            <i class="fas fa-trash-alt"></i> Eliminar
                        </button>
                    </form>
                `;

                const acciones = `
                    <a href="/remisiones/${r.id}/edit" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="/remisiones/${r.id}/imprimir" class="btn btn-info btn-sm" target="_blank">
                        <i class="fas fa-print"></i> Imprimir
                    </a>
                    ${deleteForm}
                `;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${r.numero ?? r.id}</td>
                    <td>${fecha ? (new Date(fecha).toLocaleDateString()) : ''}</td>
                    <td>${escapeHtml(usuario)}</td>
                    <td>${escapeHtml(documento)}</td>
                    <td>${dias}</td>
                    <td>$${Number(total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="d-flex flex-wrap gap-1">${acciones}</td>
                `;
                tbody.appendChild(tr);
            });

        } catch (err) {
            console.error(err);
            alert('Error cargando remisiones para el periodo seleccionado.');
        }
    });

    // Pequeña función para escapar texto y evitar inyección de HTML
    function escapeHtml(text) {
        if (!text && text !== 0) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endpush
