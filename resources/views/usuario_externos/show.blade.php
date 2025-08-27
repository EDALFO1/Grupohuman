@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Detalle del Usuario Externo</h1>
    </div>
    <section class="section">
        <div class="row">
          <div class="col-lg-12">
            <a href="{{ route('usuario_externos') }}" class="btn btn-secondary mb-3">← Volver</a>
            <div class="card">
              <div class="card-body">
            <p><strong>Documento:</strong> {{ optional($usuarioExterno->documento)->nombre }}</p>
            <p><strong>Número:</strong> {{ $usuarioExterno->numero }}</p>
            <p><strong>Fecha de expedición:</strong> {{ \Carbon\Carbon::parse($usuarioExterno->fecha_expedicion)->format('d/m/Y') }}</p>
            <p><strong>Primer apellido:</strong> {{ $usuarioExterno->primer_apellido }}</p>
            <p><strong>Segundo apellido:</strong> {{ $usuarioExterno->segundo_apellido }}</p>
            <p><strong>Primer nombre:</strong> {{ $usuarioExterno->primer_nombre }}</p>
            <p><strong>Segundo nombre:</strong> {{ $usuarioExterno->segundo_nombre }}</p>
            <p><strong>Fecha de nacimiento:</strong> {{ \Carbon\Carbon::parse($usuarioExterno->fecha_nacimiento)->format('d/m/Y') }}</p>
            <p><strong>Correo electrónico:</strong> {{ $usuarioExterno->correo_electronico }}</p>
            <p><strong>Dirección:</strong> {{ $usuarioExterno->direccion }}</p>
            <p><strong>Teléfono:</strong> {{ $usuarioExterno->telefono }}</p>
            <p><strong>Fecha de afiliación:</strong> {{ \Carbon\Carbon::parse($usuarioExterno->fecha_afiliacion)->format('d/m/Y') }}</p>
            <p><strong>Novedad:</strong> {{ $usuarioExterno->novedad }}</p>
            @if($usuarioExterno->novedad === 'Retiro')
                <p><strong>Fecha de retiro:</strong> {{ \Carbon\Carbon::parse($usuarioExterno->fecha_retiro)->format('d/m/Y') }}</p>
            @endif
            <p><strong>Sexo:</strong> {{ $usuarioExterno->sexo === 'M' ? 'Masculino' : 'Femenino' }}</p>

            <hr>

            <h4 class="card-title">Relaciones</h4>
            <p><strong>EPS:</strong> {{ optional($usuarioExterno->eps)->nombre }}</p>
            <p><strong>ARL:</strong> {{ optional($usuarioExterno->arl)->nombre }}</p>
            <p><strong>Pensión:</strong> {{ optional($usuarioExterno->pension)->nombre }}</p>
            <p><strong>Caja:</strong> {{ optional($usuarioExterno->caja)->nombre }}</p>
            <p><strong>Empresa Local:</strong> {{ optional($usuarioExterno->empresaLocal)->nombre }}</p>
            <p><strong>Empresa Externa:</strong> {{ optional($usuarioExterno->empresaExterna)->nombre }}</p>
            <p><strong>Subtipo de Cotizante:</strong> {{ optional($usuarioExterno->subtipoCotizante)->nombre }}</p>
            <p><strong>Asesor:</strong> {{ optional($usuarioExterno->asesor)->nombre }}</p>

            <hr>

            <h4 class="card-title">Información económica</h4>
            <p><strong>Sueldo:</strong> ${{ number_format($usuarioExterno->sueldo, 2) }}</p>
            <p><strong>Administración:</strong> ${{ number_format($usuarioExterno->admon, 2) }}</p>
            <p><strong>Seguro Exequial:</strong> ${{ number_format($usuarioExterno->seg_exequial ?? 0, 2) }}</p>
            <p><strong>Mora:</strong> ${{ number_format($usuarioExterno->mora ?? 0, 2) }}</p>
            <p><strong>Otros servicios:</strong> ${{ number_format($usuarioExterno->otros_servicios ?? 0, 2) }}</p>
            <p><strong>Cargo:</strong> {{ $usuarioExterno->cargo }}</p>
            <p><strong>Estado:</strong> {{ $usuarioExterno->estado ? 'Activo' : 'Inactivo' }}</p>
              </div>
            </div>
  
          </div>
        </div>
      </section>

</main>
@endsection
