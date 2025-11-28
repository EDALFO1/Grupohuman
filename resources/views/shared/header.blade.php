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

    

      {{-- BOTÓN PLANES --}}
      <li class="nav-item me-4">
        <a href="{{ route('planes.index') }}" 
          class="btn d-flex align-items-center text-white px-3" 
          style="background: linear-gradient(90deg, #0062E6, #33AEFF);">
          <i class="bi bi-layers-fill me-2 fs-5"></i>
          <span>Planes</span>
        </a>
      </li>

      {{-- BOTÓN CLAVES --}}
      <li class="nav-item me-4">
        <a href="{{ route('empresa-claves.resumen') }}" 
          class="btn d-flex align-items-center text-white px-3" 
          style="background: linear-gradient(90deg, #ff7e5f, #feb47b);">
          <i class="bi bi-key me-2 fs-5"></i>
          <span>Claves por Empresa</span>
        </a>
      </li>

      {{-- EMPRESA ACTUAL --}}
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

      {{-- PERFIL DEL USUARIO (SEGURO) --}}
      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">

          {{-- Mostrar el nombre solo si hay Auth::user() --}}
          <span class="d-none d-md-block dropdown-toggle ps-2">
            {{ Auth::check() ? Auth::user()->name : 'Invitado' }}
          </span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6>{{ Auth::check() ? Auth::user()->name : 'Invitado' }}</h6>
            <span>{{ Auth::check() ? Auth::user()->rol : 'Sin Rol' }}</span>
          </li>

          <li><hr class="dropdown-divider"></li>

          {{-- Si está autenticado, mostrar logout --}}
          @if(Auth::check())
            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}">
                <i class="bi bi-box-arrow-right"></i>
                <span>Salir</span>
              </a>
            </li>
          @endif

        </ul>
      </li>

    </ul>
  </nav>

</header>
