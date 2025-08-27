@extends('layouts.main')

@section('contenido')

<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">

            <div class="card">
              <div class="card-body">
                <hr>

                <a href="{{ route('productos.create') }}" class="btn btn-primary mb-3">
                    <i class="fa-solid fa-circle-plus"></i> Crear Nuevo Producto
                </a>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <hr>
                <table class="table datatable">
                  <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>IVA (%)</th>
                        <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($productos as $producto)
                        <tr>
                            <td>{{ $producto->codigo }}</td>
                            <td>{{ $producto->nombre }}</td>
                            <td>${{ number_format($producto->precio_unitario, 0, ',', '.') }}</td>
                            <td>{{ $producto->iva }}%</td>
                            <td>
                                <a href="{{ route('productos.edit', $producto) }}" class="btn btn-warning btn-sm">Editar</a>
                                <form action="{{ route('productos.destroy', $producto) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('¿Deseas eliminar este producto?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
    </section>
</main>
@endsection
