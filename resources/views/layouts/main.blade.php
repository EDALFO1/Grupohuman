<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>@yield('titulo')</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('NiceAdmin/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.3/css/buttons.dataTables.css">

  <!-- Template Main CSS File -->
  <link href="{{ asset('NiceAdmin/assets/css/style.css') }}" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- =======================================================
  * Template Name: NiceAdmin
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Updated: Apr 20 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body>
  <!-- ======= Header ======= -->
  @include('shared.header')
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  @include('shared.aside')
  <!-- End Sidebar-->

  @php
      use App\Models\EmpresaLocal;
      $empresaActual = session('empresa_local_id') ? EmpresaLocal::find(session('empresa_local_id')) : null;
  @endphp

  @if($empresaActual)
      <div class="alert alert-info text-center mb-3">
          <strong>Empresa activa:</strong> {{ $empresaActual->nombre }}
          <a href="{{ route('cambiar.empresa') }}" class="btn btn-sm btn-warning ms-3">
              Cambiar Empresa
          </a>
      </div>
  @endif

  @yield('contenido')

  <!-- ======= Footer ======= -->
  @include('shared.footer')
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('NiceAdmin/assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/chart.js/chart.umd.js') }}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/echarts/echarts.min.js') }}"></script>
  <!-- (corregido el path a NiceAdmin) -->
  <script src="{{ asset('NiceAdmin/assets/vendor/quill/quill.js') }}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/tinymce/tinymce.min.js') }}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/php-email-form/validate.js') }}"></script>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

  <!-- DataTables & Buttons -->
  <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/buttons/3.2.3/js/dataTables.buttons.js"></script>
  <script src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.dataTables.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.print.min.js"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('NiceAdmin/assets/js/main.js') }}"></script>

  <!-- Librerías auxiliares -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <script>
    // Inicialización de DataTables con idioma y botones
    $(function () {
      if ($('.datatable').length) {
        $('.datatable').DataTable({
          layout: {
            topStart: {
              buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
            }
          },
          language: {
            decimal: "",
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            infoEmpty: "Mostrando 0 a 0 de 0 entradas",
            infoFiltered: "(filtrado de _MAX_ entradas totales)",
            infoPostFix: "",
            thousands: ",",
            lengthMenu: "Mostrar _MENU_ entradas",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscar:",
            zeroRecords: "Sin resultados encontrados",
            paginate: {
              first: "Primero",
              last: "Último",
              next: "Siguiente",
              previous: "Anterior"
            }
          }
        });
      }

      // SweetAlerts de sesión
      @if(session('success'))
        Swal.fire({
          title: '¡Éxito!',
          text: @json(session('success')),
          icon: 'success',
          confirmButtonText: 'Aceptar'
        });
      @endif

      @if(session('error'))
        Swal.fire({
          title: 'Error',
          text: @json(session('error')),
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      @endif
    });
  </script>

  <script>
    // Búsqueda de usuario para remisiones
    $(document).on('click', '#buscar_usuario', function () {
      const numero = $('#numero_documento').val().trim();
      if (!numero) {
        alert("Ingrese un número de documento");
        return;
      }

      $.ajax({
        url: `/remisiones/buscar-usuario/${numero}`,
        method: 'GET',
        success: function (data) {
          $('#usuario_externo_id').val(data.id);
          $('#nombre').val(data.nombre);
          $('#direccion').val(data.direccion);
          $('#telefono').val(data.telefono);
          $('#empresa_local').val(data.empresa_local);
          $('#empresa_externa').val(data.empresa_externa);
          $('#eps').val(data.eps);
          $('#arl').val(data.arl);
          $('#pension').val(data.pension);
          $('#caja').val(data.caja);
          $('#administracion').val(data.administracion);
        },
        error: function () {
          alert('Usuario no encontrado.');
        }
      });
    });
  </script>

  @stack('scripts')
  @yield('scripts')
</body>
</html>
