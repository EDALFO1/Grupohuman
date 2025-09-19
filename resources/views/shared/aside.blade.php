<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">

    <li class="nav-item">
      <a class="nav-link " href="{{ route("home") }}">
        <i class="bi bi-grid"></i>
        <span>Panel de Inicio</span>
      </a>
    </li><!-- End Dashboard Nav -->

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
        <i class="fa-solid fa-folder-open"></i></i><span>Administrativo</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      
      <ul id="components-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        
        <li>
          <a href="{{ route ('remisiones')}}">
            <i class="bi bi-book" style="font-size: 1em;"></i><span>Crear Remisiones</span>
          </a>
        </li>
        <li>
      <a href="{{ route('usuario_externos') }}">
        <i class="bi bi-person-gear" style="font-size: 1em;"></i><span>Afiliados externos</span>
      </a>
    </li>
       
        
        
      </ul>
    </li><!-- End Components Nav -->
    
<li class="nav-item">
  <a class="nav-link collapsed" data-bs-target="#varios-nav" data-bs-toggle="collapse" href="#">
    <i class="fa-solid fa-user-tie"></i><span>Financiero</span><i class="bi bi-chevron-down ms-auto"></i>
  </a>

  @php
    // Meses de referencia para los links del menú
    $periodoActual    = \Carbon\Carbon::now()->format('Y-m');                 // p.ej. 2025-08
    $periodoSiguiente = \Carbon\Carbon::now()->addMonthNoOverflow()->format('Y-m'); // p.ej. 2025-09
  @endphp

  <ul id="varios-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
     <li>
          <a href="{{ route ('recibos')}}">
            <i class="bi bi-book" style="font-size: 1em;"></i><span>Crear Recibos</span>
          </a>
        </li>
    

    {{-- Usuarios marcados para el SIGUIENTE período (no pasamos empresa; el controlador usa la de sesión) --}}
    <li>
      <a href="{{ route('periodos.index', ['periodo' => $periodoSiguiente]) }}">
        <i class="bi bi-people" style="font-size: 1em;"></i><span>Activos siguiente período ({{ $periodoSiguiente }})</span>
      </a>
    </li>



  







    {{-- Reporte: pendientes de recibo del PERÍODO ACTUAL --}}
    <li>
      <a href="{{ route('periodos.pendientes', ['periodo' => $periodoActual]) }}">
        <i class="bi bi-list-check" style="font-size: 1em;"></i><span>Pendientes de recibo ({{ $periodoActual }})</span>
      </a>
    </li>

    {{-- Retiros masivos (usa período por defecto = mes actual en el form) --}}
    <li>
      <a href="{{ route('recibos.retirosMasivos.form') }}">
        <i class="bi bi-person-dash" style="font-size: 1em;"></i><span>Retiros masivos (pendientes)</span>
      </a>
    </li>

     <li>
  <a href="{{ route('excel.usuarios_vigentes.descargar', [
        'empresa_local_id' => session('empresa_local_id'),
        'periodo' => now()->format('Y-m')
      ]) }}">
    <i class="bi bi-file-earmark-excel" style="font-size: 1em;"></i>
    <span>Exportar usuarios vigentes </span>
  </a>
</li>
  </ul>
</li>

    <!-- End Contact Page Nav -->

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#empresas-nav" data-bs-toggle="collapse" href="#">
        <i class="fa-solid fa-building"></i><span>Empresas</span><i class="bi bi-chevron-down ms-auto"></i>
        
      </a>
      <ul id="empresas-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ route ('empresa_local')}}">
            <i class="bi bi-buildings" style="font-size: 1em;"></i><span>Empresa Local</span>
          </a>
        </li>
        <li>
          <a href="{{ route ('empresa_externas')}}">
            <i class="bi bi-buildings-fill" style="font-size: 1em;"></i><span>Empresa Externa</span>
          </a>
        </li>
      </ul>  
    </li><!-- End Register Page Nav -->
    
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#productos-nav" data-bs-toggle="collapse" href="#">
        <i class="fa-solid fa-sitemap"></i></i><span>Items</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="productos-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
       
        <li>
          <a href="{{ route ('asesores')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Asesores</span>
          </a>
        </li>
        <li>
          <a href="{{ route ('productos')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Productos</span>
          </a>
        </li>
        
      </ul>
    </li>

    
   
    
    <!-- End Error 404 Page Nav -->

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#libreria.nav" data-bs-toggle="collapse" href="#">
        <i class="fa-solid fa-building"></i><span>Admon de Libreria</span><i class="bi bi-chevron-down ms-auto"></i>
        
      </a>
      <ul id="libreria.nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li>
          <a href="{{ route ('valores.index')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Valores</span>
          </a>
        </li>
        <li>
          <a href="{{ route ('arls')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Arl</span>
          </a>
        </li>
        <li>
          <a href="{{ route ('eps')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Eps</span>
          </a>
        </li>
        <li>
          <a href="{{ route ('pensions')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Pension</span>
          </a>
        </li>
        <li>
          <a href="{{ route ('cajas')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Caja</span>
          </a>
        </li>
        <li>
          <a href="{{ route ('documentos')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Documentos</span>
          </a>
        </li>
        <li>
          <a href="{{ route ('subtipo_cotizantes')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Subtipos</span>
          </a>
        </li>
        <li>
          <a href="{{ route ('incapacidades.index')}}">
            <i class="bi bi-send" style="font-size: 1em;"></i><span>Incapacidades</span>
          </a>
        </li>
      </ul>  
    </li>

    

    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ route ('usuarios')}}">
        <i class="fa-solid fa-users"></i>
        <span>Usuarios</span>
      </a>
    </li><!-- End Blank Page Nav -->

  </ul>

</aside>