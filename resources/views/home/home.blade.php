@extends('layouts.app')

@section('content')
<div class="content-body">
  <section id="dashboard-ecommerce">
    <div class="row match-height">

      {{-- TIGA KARTU KECIL: REQUESTS, APPROVED, PENDING --}}
      <div class="col-12">
        <div class="row match-height">
          <!-- REQUESTS -->
          <div class="col-lg-4 col-md-4 col-6">
            <div class="card text-center">
              <div class="card-body">
                <div class="avatar bg-light-info p-50 mb-1">
                  <div class="avatar-content">
                    <i data-feather="file-text" class="font-medium-5"></i>
                  </div>
                </div>
                <h2 class="fw-bolder">{{ $kelas ?? 0 }}</h2>
                <p class="card-text">REQUESTS</p>
              </div>
            </div>
          </div>

          <!-- APPROVED -->
          <div class="col-lg-4 col-md-4 col-6">
            <div class="card text-center">
              <div class="card-body">
                <div class="avatar bg-light-success p-50 mb-1">
                  <div class="avatar-content">
                    <i data-feather="thumbs-up" class="font-medium-5"></i>
                  </div>
                </div>
                <h2 class="fw-bolder">{{ $pelajaran ?? 0 }}</h2>
                <p class="card-text">APPROVED</p>
              </div>
            </div>
          </div>

          <!-- PENDING -->
          <div class="col-lg-4 col-md-4 col-6">
            <div class="card text-center">
              <div class="card-body">
                <div class="avatar bg-light-danger p-50 mb-1">
                  <div class="avatar-content">
                    <i data-feather="clock" class="font-medium-5"></i>
                  </div>
                </div>
                <h2 class="fw-bolder">{{ $pending ?? ($userSiswa ?? 0) }}</h2>
                <p class="card-text">PENDING</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- AKUN YANG TERDAFTAR --}}
      <div class="col-12">
        <div class="card card-statistics">
          <div class="card-header">
            <h4 class="card-title">Akun Yang Terdaftar</h4>
          </div>
          <div class="card-body statistics-body">
            <div class="row">
              <!-- ADMIN -->
              <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                <div class="d-flex flex-row">
                  <div class="avatar bg-light-info me-2">
                    <div class="avatar-content">
                      <i data-feather="users" class="avatar-icon"></i>
                    </div>
                  </div>
                  <div class="my-auto">
                    <h4 class="fw-bolder mb-0">{{ $countAdmin ?? 0 }}</h4>
                    <p class="card-text font-small-3 mb-0">Admin</p>
                  </div>
                </div>
              </div>

              <!-- PPIC -->
              <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                <div class="d-flex flex-row">
                  <div class="avatar bg-light-info me-2">
                    <div class="avatar-content">
                      <i data-feather="users" class="avatar-icon"></i>
                    </div>
                  </div>
                  <div class="my-auto">
                    <h4 class="fw-bolder mb-0">{{ $countPPIC ?? 0 }}</h4>
                    <p class="card-text font-small-3 mb-0">PPIC</p>
                  </div>
                </div>
              </div>

              <!-- R&D -->
              <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                <div class="d-flex flex-row">
                  <div class="avatar bg-light-info me-2">
                    <div class="avatar-content">
                      <i data-feather="users" class="avatar-icon"></i>
                    </div>
                  </div>
                  <div class="my-auto">
                    <h4 class="fw-bolder mb-0">{{ $countRD ?? 0 }}</h4>
                    <p class="card-text font-small-3 mb-0">R&amp;D</p>
                  </div>
                </div>
              </div>

              <!-- PURCHASING -->
              <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                <div class="d-flex flex-row">
                  <div class="avatar bg-light-info me-2">
                    <div class="avatar-content">
                      <i data-feather="users" class="avatar-icon"></i>
                    </div>
                  </div>
                  <div class="my-auto">
                    <h4 class="fw-bolder mb-0">{{ $countPurchasing ?? 0 }}</h4>
                    <p class="card-text font-small-3 mb-0">Purchasing</p>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>
@endsection
