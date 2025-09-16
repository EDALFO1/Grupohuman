@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Panel Administracion Usuario</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if(session('duplicados'))
                            <div class="alert alert-warning">
                                Los siguientes documentos ya existían y fueron omitidos:
                                <ul class="mb-0">
                                    @foreach(session('duplicados') as $doc)
                                        <li>{{ $doc }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h1 class="h4 m-0">Usuarios Externos</h1>

                            <div class="btn-group">
                                {{-- "Ver todos" conserva el per_page actual --}}
                                <a href="{{ route('usuario_externos', ['all' => 1, 'per_page' => request('per_page', 10)]) }}"
                                   class="btn btn-outline-secondary btn-sm">
                                    Ver todos
                                </a>

                                <a href="{{ route('usuario_externos.import') }}" class="btn btn-success">
                                    Importar desde Excel
                                </a>
                                <a href="{{ route('usuario_externos.template') }}" class="btn btn-secondary">
                                    Descargar plantilla
                                </a>
                            </div>
                        </div>

                        <hr>

                        <!-- Botón para crear nuevo usuario -->
                        <a href="{{ route('usuario_externos.create') }}" class="btn btn-primary mb-3">
                            <i class="fa-solid fa-circle-plus"></i> Crear Nuevo Usuario
                        </a>

                        <hr>

                        {{-- Tabla SIN la clase "datatable" para no mezclar paginación JS vs servidor --}}
                        <table class="table data-table">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Documento</th>
                                    <th>Número</th>
                                    <th>Nombre Completo</th>
                                    <th>EPS</th>
                                    <th>ARL</th>
                                    <th>Pensión</th>
                                    <th>Caja</th>
                                    <th>Empresa Externa</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usuarios as $usuario)
                                    <tr>
                                        <td>{{ $usuario->documento->nombre ?? 'N/A' }}</td>
                                        <td>{{ $usuario->numero }}</td>
                                        <td>
                                            {{ $usuario->primer_nombre }}
                                            {{ $usuario->segundo_nombre }}
                                            {{ $usuario->primer_apellido }}
                                            {{ $usuario->segundo_apellido }}
                                        </td>
                                        <td>{{ $usuario->eps->nombre ?? 'N/A' }}</td>
                                        <td>{{ $usuario->arl->nombre ?? 'N/A' }}</td>
                                        <td>{{ $usuario->pension->nombre ?? 'N/A' }}</td>
                                        <td>{{ $usuario->caja->nombre ?? 'N/A' }}</td>
                                        <td>{{ $usuario->empresaExterna->nombre ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $usuario->estado ? 'bg-success' : 'bg-danger' }}">
                                                {{ $usuario->estado ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('usuario_externos.show', $usuario) }}" class="btn btn-info btn-sm">Ver</a>
                                            <a href="{{ route('usuario_externos.edit', $usuario) }}" class="btn btn-warning btn-sm">Editar</a>
                                            <form action="{{ route('usuario_externos.destroy', $usuario) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar este usuario externo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger btn-sm">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">Sin registros</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        {{-- Barra inferior: selector por página + info + enlaces (inline, sin componente) --}}
                        @php
                            // Números seguros cuando no hay resultados
                            $first = $usuarios->firstItem() ?? 0;
                            $last  = $usuarios->lastItem()  ?? 0;
                            $total = method_exists($usuarios, 'total') ? (int) $usuarios->total() : null;
                            $nf    = fn($n) => number_format((int)$n, 0, ',', '.');
                        @endphp

                        @if($usuarios instanceof \Illuminate\Pagination\Paginator || $usuarios instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">

                                <form method="GET" action="{{ route('usuario_externos') }}" class="d-flex align-items-center gap-2">
                                    {{-- Preservar TODOS los parámetros actuales excepto per_page y page --}}
                                    @foreach(request()->except('per_page', 'page') as $k => $v)
                                        @if(is_array($v))
                                            @foreach($v as $vv)
                                                <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                                            @endforeach
                                        @else
                                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                        @endif
                                    @endforeach

                                    <label class="me-2">Por página:</label>
                                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                        @foreach([10,25,50,100,200] as $n)
                                            <option value="{{ $n }}" {{ (int)request('per_page',10) === $n ? 'selected' : '' }}>
                                                {{ $n }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>

                                <small class="text-muted">
                                    @if(!is_null($total))
                                        Mostrando <strong>{{ $nf($first) }}</strong>–<strong>{{ $nf($last) }}</strong>
                                        de <strong>{{ $nf($total) }}</strong> registros
                                    @else
                                        Página <strong>{{ $usuarios->currentPage() }}</strong>
                                    @endif
                                </small>

                                <div>
                                    {{ $usuarios->onEachSide(1)->links() }}
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </section>

</main>
@endsection
