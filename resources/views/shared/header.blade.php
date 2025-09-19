<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="index.html" class="logo d-flex align-items-center">
      <img src="assets/img/logo.png" alt="">
      <span class="d-none d-lg-block">GrupoHuman</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">


<li class="nav-item me-4">
  <a href="{{ route('planes.index') }}" 
     class="btn d-flex align-items-center text-white px-3" 
     style="background: linear-gradient(90deg, #0062E6, #33AEFF);">
    <i class="bi bi-layers-fill me-2 fs-5"></i>
    <span>Planes</span>
  </a>
</li>



      {{-- Mostrar empresa actual en verde --}}
      @php
          use App\Models\EmpresaLocal;
          $empresaActual = session('empresa_local_id') ? EmpresaLocal::find(session('empresa_local_id')) : null;
      @endphp

      @if($empresaActual)
        <li class="nav-item dropdown pe-3 me-3">
          <a href="{{ route('cambiar.empresa') }}" class="nav-link text-success fw-bold">
            <i class="bi bi-building text-success me-1"></i> {{ $empresaActual->nombre }}
          </a>
        </li>
      @endif

      <li class="nav-item d-block d-lg-none">
        <a class="nav-link nav-icon search-bar-toggle" href="#">
          <i class="bi bi-search"></i>
        </a>
      </li>

      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <span class="d-none d-md-block dropdown-toggle ps-2">{{ Auth::user()->name }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6>{{ Auth::user()->name }}</h6>
            <span>{{ Auth::user()->rol }}</span>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}">
              <i class="bi bi-box-arrow-right"></i>
              <span>Salir</span>
            </a>
          </li>
        </ul>
      </li>

    </ul>
  </nav>

</header>
