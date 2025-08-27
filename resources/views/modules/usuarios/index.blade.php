@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Panel Administración Usuario</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <hr>

                        <!-- Botón crear nuevo usuario -->
                        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
                            <i class="fa-solid fa-circle-plus"></i> Crear Nuevo Usuario
                        </a>

                        <hr>

                        <!-- Tabla de usuarios -->
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Cambio Password</th>
                                    <th>Activo</th>
                                    <th>Rol</th>
                                    <th>Editar</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-usuarios">
                                @include('modules.usuarios.tbody')
                            </tbody>
                        </table>
                        <!-- End Table -->

                    </div>
                </div>

            </div>
        </div>
    </section>
</main>

@include('modules.usuarios.modal_cambiar_password')
@endsection

@push('scripts')
<script>
    // Re-cargar el tbody y reenganchar eventos
    function recargar_tbody() {
        $.ajax({
            type: "GET",
            url: "{{ route('usuarios.tbody') }}",
            success: function (respuesta) {
                $('#tbody-usuarios').html(respuesta);
                // Reenganchar listeners después de reemplazar el tbody
                bindEstadoSwitch();
                bindAbrirModal();
            },
            error: function () {
                console.error('No se pudo recargar el listado de usuarios.');
            }
        });
    }

    function cambiar_estado(id, estado) {
        $.ajax({
            type: "GET",
            url: "usuarios/cambiar-estado/" + id + "/" + estado,
            success: function (respuesta) {
                if (respuesta == 1) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Cambio de estado exitoso',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });
                    recargar_tbody();
                } else {
                    Swal.fire({
                        title: '¡Fallo!',
                        text: 'No se llevó a cabo el cambio',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            }
        });
    }

    function agregar_id_usuario(id) {
        $('#id_usuario').val(id);
    }

    function cambio_password() {
        let id = $('#id_usuario').val();
        let password = $('#password').val();

        $.ajax({
            type: "GET",
            url: "usuarios/cambiar-password/" + id + "/" + password,
            success: function (respuesta) {
                if (respuesta == 1) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Cambio de password exitoso',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });
                    $('#frmPassword')[0].reset();
                    // Si el modal se cierra con data-bs-dismiss, se cerrará automáticamente
                } else {
                    Swal.fire({
                        title: '¡Fallo!',
                        text: 'Cambio de password no exitoso',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            }
        });

        return false;
    }

    // Encapsulo los listeners para reusarlos tras recargar tbody
    function bindEstadoSwitch() {
        $('.form-check-input').off('change').on('change', function () {
            let id = $(this).attr('id');
            let estado = $(this).is(':checked') ? 1 : 0;
            cambiar_estado(id, estado);
        });
    }

    function bindAbrirModal() {
        // Si usas algún botón que abra el modal y asigna el id:
        // data-id en el botón que abre el modal
        $('[data-open-password-modal]').off('click').on('click', function () {
            const id = $(this).data('id');
            agregar_id_usuario(id);
        });
    }

    $(document).ready(function () {
        bindEstadoSwitch();
        bindAbrirModal();
    });
</script>
@endpush
