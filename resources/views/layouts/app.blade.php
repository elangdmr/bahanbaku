@php
    $user    = auth()->user();
    $avatar  = strtoupper(substr($user->name ?? 'U', 0, 2));
    $role    = strtolower($user->role ?? '');
    $isAdmin = in_array($role, ['admin','administrator','superadmin'], true);
    $isPPIC  = ($role === 'ppic');
    $isPurch = ($role === 'purchasing');
    $isRND   = ($role === 'r&d' || $role === 'rnd');

    // Brand click tujuan
    $brandHome = route('dashboard');
    if ($isPPIC)  $brandHome = route('halal.index');
    if ($isPurch) $brandHome = route('purch-vendor.index');
    // Opsional: jika ingin R&D diarahkan ke modulnya
    // if ($isRND)  $brandHome = route('trial-rnd.index');
@endphp

<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
<style>
  .main-menu .navigation > li.active > a,
  .main-menu .navigation > li > a:hover,
  .main-menu .navigation > li.open > a{
    background: linear-gradient(118deg,#dc3545,rgba(220,53,69,.7)) !important;
    box-shadow: 0 0 10px 1px rgba(220,53,69,.5) !important;
    color:#fff !important;
  }
  .main-menu .navigation > li.active > a i,
  .main-menu .navigation > li > a:hover i,
  .main-menu .navigation > li.open > a i{ color:#fff !important; }
  .main-menu .navigation .menu-content > li.active > a{ color:#dc3545 !important; }
  .main-menu .navigation .menu-content > li.active > a:before{ background:#dc3545 !important; }
  .navbar-header .brand-text, .main-menu .brand-text { color:#dc3545 !important; }
</style>

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui" />
  <title>PT. SAMCO Farma</title>
  <link rel="shortcut icon" type="image/x-icon" href="{{ asset('app-assets/images/logo/logo.png') }}" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

  {{-- Vendor CSS --}}
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/vendors.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/charts/apexcharts.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/extensions/toastr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/pickers/pickadate/pickadate.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/pickers/flatpickr/flatpickr.min.css') }}">
  {{-- DataTables --}}
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/dataTables.bootstrap5.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/buttons.bootstrap5.min.css') }}">

  {{-- Theme --}}
  <link rel="stylesheet" href="{{ asset('app-assets/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/bootstrap-extended.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/colors.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/components.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/themes/dark-layout.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/themes/bordered-layout.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/themes/semi-dark-layout.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/forms/select/select2.min.css') }}">

  {{-- Page --}}
  <link rel="stylesheet" href="{{ asset('app-assets/css/core/menu/menu-types/vertical-menu.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/pages/dashboard-ecommerce.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/charts/chart-apex.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/extensions/ext-component-toastr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/forms/form-validation.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/pages/app-user.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/forms/pickers/form-flat-pickr.min.css') }}">
  <link rel="stylesheet" href="{{ asset('app-assets/css/plugins/forms/pickers/form-pickadate.min.css') }}">

  {{-- Custom --}}
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body class="vertical-layout vertical-menu-modern navbar-floating footer-static" data-open="click" data-menu="vertical-menu-modern">
  {{-- Header --}}
  <nav class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light navbar-shadow container-xxl">
    <div class="navbar-container d-flex content">
      <div class="bookmark-wrapper d-flex align-items-center">
        <ul class="nav navbar-nav d-xl-none">
          <li class="nav-item"><a class="nav-link menu-toggle" href="#"><i class="ficon" data-feather="menu"></i></a></li>
        </ul>
        <ul class="nav navbar-nav bookmark-icons">
          @if($isAdmin)
            <li class="nav-item d-none d-lg-block">
              <a class="nav-link" href="{{ route('show-permintaan') }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Permintaan Bahan Baku">
                <i class="ficon" data-feather="calendar"></i>
              </a>
            </li>
          @endif
        </ul>
      </div>
      <ul class="nav navbar-nav align-items-center ms-auto">
        <li class="nav-item dropdown dropdown-user">
          <a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <div class="user-nav d-sm-flex d-none">
              <span class="user-name fw-bolder">{{ $user->name }}</span>
              <span class="user-status">{{ $user->role }}</span>
            </div>
            <span class="avatar bg-light-primary"><div class="avatar-content">{{ $avatar }}</div></span>
          </a>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-user">
            <a class="dropdown-item" href="{{ route('show-profile') }}"><i class="me-50" data-feather="user"></i> Profile</a>
            <div class="dropdown-divider"></div>
            <form action="{{ route('logout') }}" method="POST">@csrf
              <button type="submit" class="dropdown-item"><i class="me-50" data-feather="power"></i> Logout</button>
            </form>
          </div>
        </li>
      </ul>
    </div>
  </nav>

  {{-- Sidebar --}}
  <div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="navbar-header">
      <ul class="nav navbar-nav flex-row">
        <li class="nav-item me-auto">
          <a class="navbar-brand" href="{{ $brandHome }}">
            <span class="brand-logo"><img src="{{ asset('app-assets/images/logo/logo.png') }}" alt=""></span>
            <h2 class="brand-text">Samco Farma</h2>
          </a>
        </li>
      </ul>
    </div>
    <div class="shadow-bottom"></div>

    <div class="main-menu-content">
      <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">

        {{-- DASHBOARD: selalu ada untuk semua role --}}
        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
          <a class="d-flex align-items-center" href="{{ route('dashboard') }}">
            <i data-feather="home"></i><span class="menu-title text-truncate">Dashboard</span>
          </a>
        </li>

        {{-- ===================== MENU ADMIN ===================== --}}
        @if ($isAdmin)
          <li class="navigation-header"><span>User Management</span><i data-feather="more-horizontal"></i></li>
          <li class="nav-item">
            <a class="d-flex align-items-center" href="#"><i data-feather="user"></i><span class="menu-title text-truncate">User</span></a>
            <ul class="menu-content">
              <li class="{{ request()->is('show-rnd*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('show-rnd') }}"><i data-feather="circle"></i><span class="menu-item text-truncate">R&amp;D</span></a>
              </li>
              <li class="{{ request()->is('show-ppic*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('show-ppic') }}"><i data-feather="circle"></i><span class="menu-item text-truncate">PPIC Halal</span></a>
              </li>
              <li class="{{ request()->is('show-purchasing*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('show-purchasing') }}"><i data-feather="circle"></i><span class="menu-item text-truncate">Purchasing</span></a>
              </li>
            </ul>
          </li>

          <li class="navigation-header"><span>Master Data</span><i data-feather="more-horizontal"></i></li>
          <li class="nav-item {{ request()->routeIs('bahan.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('bahan.index') }}">
              <i data-feather="box"></i><span class="menu-title text-truncate">Bahan Baku</span>
            </a>
          </li>

          <li class="nav-item {{ request()->routeIs('show-permintaan') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('show-permintaan') }}"><i data-feather="file-text"></i><span class="menu-title text-truncate">Permintaan Bahan Baku</span></a>
          </li>
          <li class="nav-item {{ request()->routeIs('purch-vendor.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('purch-vendor.index') }}"><i data-feather="shopping-cart"></i><span class="menu-title text-truncate">Purchasing Vendor</span></a>
          </li>
          <li class="nav-item {{ request()->routeIs('uji-coa.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('uji-coa.index') }}"><i data-feather="check-square"></i><span class="menu-title text-truncate">Hasil Uji COA</span></a>
          </li>
          <li class="nav-item {{ request()->routeIs('halal.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('halal.index') }}"><i data-feather="award"></i><span class="menu-title text-truncate">Halal PPIC</span></a>
          </li>
          <li class="nav-item {{ request()->routeIs('sampling-pch.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('sampling-pch.index') }}"><i data-feather="droplet"></i><span class="menu-title text-truncate">Sampling PCH</span></a>
          </li>
          <li class="nav-item {{ request()->routeIs('trial-rnd.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('trial-rnd.index') }}"><i data-feather="cpu"></i><span class="menu-title text-truncate">Trial R&amp;D</span></a>
          </li>
          <li class="nav-item {{ request()->routeIs('registrasi.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('registrasi.index') }}"><i data-feather="grid"></i><span class="menu-title text-truncate">Registrasi</span></a>
          </li>
        @endif

        {{-- ===================== MENU PPIC ===================== --}}
        @if ($isPPIC)
          <li class="nav-item {{ request()->routeIs('halal.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('halal.index') }}">
              <i data-feather="award"></i><span class="menu-title text-truncate">Halal PPIC</span>
            </a>
          </li>
        @endif

        {{-- ===================== MENU PURCHASING ===================== --}}
        @if ($isPurch)
          <li class="nav-item {{ request()->routeIs('purch-vendor.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('purch-vendor.index') }}"><i data-feather="shopping-cart"></i><span class="menu-title text-truncate">Purchasing Vendor</span></a>
          </li>
          <li class="nav-item {{ request()->routeIs('sampling-pch.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('sampling-pch.index') }}"><i data-feather="droplet"></i><span class="menu-title text-truncate">Sampling PCH</span></a>
          </li>
        @endif

        {{-- ===================== MENU R&D ===================== --}}
        @if ($isRND)
          <li class="nav-item {{ request()->routeIs('show-permintaan') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('show-permintaan') }}">
              <i data-feather="file-text"></i><span class="menu-title text-truncate">Permintaan Bahan Baku</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('uji-coa.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('uji-coa.index') }}">
              <i data-feather="check-square"></i><span class="menu-title text-truncate">Hasil Uji COA</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('trial-rnd.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('trial-rnd.index') }}">
              <i data-feather="cpu"></i><span class="menu-title text-truncate">Trial R&amp;D</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('registrasi.*') ? 'active' : '' }}">
            <a class="d-flex align-items-center" href="{{ route('registrasi.index') }}">
              <i data-feather="grid"></i><span class="menu-title text-truncate">Registrasi</span>
            </a>
          </li>
        @endif

        {{-- RIWAYAT: tampil untuk semua role --}}
        <li class="nav-item {{ request()->routeIs('riwayat.*') ? 'active' : '' }}">
          <a class="d-flex align-items-center" href="{{ route('riwayat.index') }}">
            <i data-feather="activity"></i><span class="menu-title text-truncate">Riwayat Proses</span>
          </a>
        </li>

      </ul>
    </div>
  </div>

  {{-- Content --}}
  <div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl p-0">
      <div class="content-header row"></div>
      <div class="content-body">
        @yield('content')
        <button class="btn btn-primary btn-icon scroll-top" type="button"><i data-feather="arrow-up"></i></button>
      </div>
    </div>
  </div>

  {{-- Vendor JS --}}
  <script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.bootstrap5.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/dataTables.responsive.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/responsive.bootstrap4.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.buttons.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/forms/validation/jquery.validate.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/charts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/forms/select/select2.full.min.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/pickadate/picker.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/pickadate/picker.date.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/pickadate/picker.time.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/pickadate/legacy.js') }}"></script>
  <script src="{{ asset('app-assets/vendors/js/pickers/flatpickr/flatpickr.min.js') }}"></script>

  {{-- Theme JS --}}
  <script src="{{ asset('app-assets/js/core/app-menu.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/core/app.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/customizer.min.js') }}"></script>

  {{-- Page JS --}}
  <script src="{{ asset('app-assets/js/scripts/cards/card-analytics.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/forms/form-select2.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/pages/dashboard-ecommerce.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/pages/app-user-list.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/tables/table-datatables-advanced.min.js') }}"></script>
  <script src="{{ asset('app-assets/js/scripts/forms/pickers/form-pickers.min.js') }}"></script>

  <script>
    $(window).on('load', function(){ if (feather) feather.replace({ width:14, height:14 }); });
  </script>
</body>
</html>
