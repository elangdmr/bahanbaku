@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row">
    <div class="col-12">
      <div class="card">

        {{-- ========= Header + Filter Toolbar ========= --}}
        <div class="card-header border-bottom-0 pb-0">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-50">
            <div>
              <h4 class="card-title mb-25">Riwayat Proses</h4>
              <small class="text-muted">Gabungan semua modul, diurutkan dari terbaru.</small>
            </div>

            {{-- Toggle filter (mobile) --}}
            <button class="btn btn-outline-primary d-lg-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="true">
              <i data-feather="sliders"></i><span class="ms-50">Filter</span>
            </button>
          </div>

          <div id="filterBar" class="collapse show">
            <form class="d-flex flex-wrap justify-content-between align-items-center gap-2 filter-toolbar" method="GET" action="{{ route('riwayat.index') }}">
              
              {{-- === kiri: pencarian & filter === --}}
              <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                {{-- Search --}}
                <div class="flex-grow-1" style="min-width:220px;">
                  <div class="input-group">
                    <span class="input-group-text"><i data-feather="search" class="text-muted"></i></span>
                    <input type="text" class="form-control" name="q"
                           value="{{ request('q','') }}" placeholder="Cari kode / bahan / status">
                  </div>
                </div>

                {{-- Modul --}}
                <div style="min-width:180px;">
                  <div class="input-group">
                    <span class="input-group-text"><i data-feather="layers" class="text-muted"></i></span>
                    <select class="form-select" name="modul">
                      <option value="">Semua Modul</option>
                      @foreach($modulList as $m)
                        <option value="{{ $m }}" {{ request('modul')===$m ? 'selected' : '' }}>{{ $m }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>

                {{-- Dari --}}
                <div style="min-width:160px;">
                  <div class="input-group">
                    <span class="input-group-text"><i data-feather="calendar" class="text-muted"></i></span>
                    <input type="date" class="form-control" name="from" value="{{ request('from') }}">
                  </div>
                </div>

                {{-- Sampai --}}
                <div style="min-width:160px;">
                  <div class="input-group">
                    <span class="input-group-text"><i data-feather="calendar" class="text-muted"></i></span>
                    <input type="date" class="form-control" name="to" value="{{ request('to') }}">
                  </div>
                </div>
              </div>

              {{-- === kanan: tombol === --}}
              <div class="d-flex align-items-center gap-1">
                <button class="btn btn-primary d-flex align-items-center">
                  <i data-feather="filter"></i><span class="ms-50">Filter</span>
                </button>
                <a href="{{ route('riwayat.index') }}" class="btn btn-outline-secondary d-flex align-items-center">
                  <i data-feather="rotate-ccw"></i><span class="ms-50">Reset</span>
                </a>
              </div>

            </form>
          </div>
        </div>
        {{-- ========= /Header + Filter ========= --}}

        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th style="width:135px">Tanggal</th>
                  <th style="width:105px">ID</th>
                  <th>Nama Bahan</th>
                  <th style="width:140px">Diproses di</th>
                  <th>Peristiwa</th>
                  <th style="width:180px">Status</th>
                  <th>Keterangan</th>
                  <th style="width:90px" class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
              @forelse($events as $e)
                <tr>
                  <td>{{ \Carbon\Carbon::parse($e['tanggal'])->format('d/m/Y') }}</td>
                  <td><span class="badge rounded-pill bg-secondary">{{ $e['kode'] }}</span></td>
                  <td>{{ $e['bahan'] }}</td>
                  <td>{{ $e['modul'] }}</td>
                  <td>{{ $e['peristiwa'] }}</td>
                  <td>
                    @php
                      $st = (string)($e['status'] ?? '');
                      $badge = 'bg-light-secondary';
                      if (preg_match('/(Lengkap|Approved|Lulus|Diterima)/i', $st)) $badge='bg-success';
                      elseif (preg_match('/(Belum|Menunggu|Estimasi)/i', $st)) $badge='bg-warning text-dark';
                      elseif (preg_match('/(Tidak|Rejected|Ditolak|Gagal)/i', $st)) $badge='bg-danger';
                      elseif (preg_match('/(Diproses|Proses)/i', $st)) $badge='bg-info';
                      elseif ($st==='') $badge='bg-light-secondary';
                    @endphp
                    <span class="badge {{ $badge }}">{{ $st !== '' ? $st : '-' }}</span>
                  </td>
                  <td>{{ $e['keterangan'] ?? '-' }}</td>
                  <td class="text-end">
                    @if(!empty($e['link']))
                      <a class="btn btn-sm btn-outline-primary" href="{{ $e['link'] }}">Detail</a>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-muted">Belum ada data.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- styling kecil untuk toolbar --}}
<style>
  .filter-toolbar .input-group-text { background:#fff; border-right:0; }
  .filter-toolbar .form-control, .filter-toolbar .form-select { border-left:0; }
  .filter-toolbar .input-group { border:1px solid #e9ecef; border-radius:.428rem; overflow:hidden; background:#fff; }
  @media (max-width: 992px) {
    .filter-toolbar { flex-direction:column; align-items:stretch !important; }
    .filter-toolbar > div { width:100% !important; }
  }
</style>

{{-- aktifkan feather icon --}}
<script>window.feather && window.feather.replace();</script>
@endsection
