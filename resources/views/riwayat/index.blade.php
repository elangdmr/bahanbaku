@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row">
    <div class="col-12">
      <div class="card">

        {{-- ========= Header + Toolbar ========= --}}
        <div class="card-header border-bottom-0 pb-0">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-50">
            <div>
              <h4 class="card-title mb-25">Riwayat Proses</h4>
              <small class="text-muted">
                Gabungan semua modul, diurutkan dari terbaru.
                <span class="ms-25">(<strong>{{ $events->count() }}</strong> baris)</span>
              </small>
            </div>

            <div class="d-flex align-items-center gap-1">
              {{-- === Export dropdown === --}}
              <div class="btn-group me-50">
                <button type="button" class="btn btn-success btn-sm dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false">
                  <i data-feather="download"></i><span class="ms-50">Export</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <h6 class="dropdown-header">Pivot (nyamping, 1 baris = 1 PB)</h6>
                  <a class="dropdown-item"
                     href="{{ route('riwayat.export', array_merge(['scope'=>'filtered','layout'=>'pivot'], request()->query())) }}">
                    Pivot — Sesuai Filter
                  </a>
                  <a class="dropdown-item"
                     href="{{ route('riwayat.export', ['scope'=>'all','layout'=>'pivot']) }}">
                    Pivot — Semua Data
                  </a>
                  <div class="dropdown-divider"></div>
                  <h6 class="dropdown-header">Raw (panjang, baris per peristiwa)</h6>
                  <a class="dropdown-item"
                     href="{{ route('riwayat.export', array_merge(['scope'=>'filtered','layout'=>'long'], request()->query())) }}">
                    Raw — Sesuai Filter
                  </a>
                  <a class="dropdown-item"
                     href="{{ route('riwayat.export', ['scope'=>'all','layout'=>'long']) }}">
                    Raw — Semua Data
                  </a>
                </div>
              </div>

              {{-- Toggle filter (mobile) --}}
              <button class="btn btn-outline-primary d-lg-none" type="button"
                      data-bs-toggle="collapse" data-bs-target="#filterBar" aria-expanded="true">
                <i data-feather="sliders"></i><span class="ms-50">Filter</span>
              </button>
            </div>
          </div>

          {{-- ========= Filter Bar ========= --}}
          <div id="filterBar" class="collapse show">
            <form class="d-flex flex-wrap justify-content-between align-items-center gap-2 filter-toolbar"
                  method="GET" action="{{ route('riwayat.index') }}">
              
              {{-- kiri: search + filter --}}
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

              {{-- kanan: tombol --}}
              <div class="d-flex align-items-center gap-1">
                <button class="btn btn-primary d-flex align-items-center" type="submit">
                  <i data-feather="filter"></i><span class="ms-50">Filter</span>
                </button>
                <a href="{{ route('riwayat.index') }}" class="btn btn-outline-secondary d-flex align-items-center">
                  <i data-feather="rotate-ccw"></i><span class="ms-50">Reset</span>
                </a>
              </div>

            </form>

            {{-- chips filter aktif (opsional) --}}
            @php
              $chips = [];
              if(request('q'))     $chips[] = ['icon'=>'search','text'=>'Cari: "'.request('q').'"'];
              if(request('modul')) $chips[] = ['icon'=>'layers','text'=>'Modul: '.request('modul')];
              if(request('from'))  $chips[] = ['icon'=>'calendar','text'=>'Dari: '.request('from')];
              if(request('to'))    $chips[] = ['icon'=>'calendar','text'=>'Sampai: '.request('to')];
            @endphp
            @if(count($chips))
              <div class="mt-50 d-flex flex-wrap gap-50">
                @foreach($chips as $c)
                  <span class="badge bg-light-secondary text-dark">
                    <i data-feather="{{ $c['icon'] }}" class="me-25" style="height:14px;width:14px"></i>
                    {{ $c['text'] }}
                  </span>
                @endforeach
              </div>
            @endif
          </div>
        </div>
        {{-- ========= /Header + Filter ========= --}}

        {{-- ========= Tabel ========= --}}
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="width:135px">Tanggal</th>
                  <th style="width:110px">ID</th>
                  <th>Nama Bahan</th>
                  <th style="width:150px">Diproses di</th>
                  <th>Peristiwa</th>
                  <th style="width:200px">Status</th>
                  <th>Keterangan</th>
                  <th style="width:100px" class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
              @forelse($events as $e)
                @php
                  $tanggal = !empty($e['tanggal']) ? \Carbon\Carbon::parse($e['tanggal'])->format('d/m/Y') : '-';
                  $st = (string)($e['status'] ?? '');
                  $badge = 'bg-light-secondary';
                  if (preg_match('/(Lengkap|Approved|Lulus|Diterima)/i', $st)) $badge='bg-success';
                  elseif (preg_match('/(Belum|Menunggu|Estimasi)/i', $st)) $badge='bg-warning text-dark';
                  elseif (preg_match('/(Tidak|Rejected|Ditolak|Gagal)/i', $st)) $badge='bg-danger';
                  elseif (preg_match('/(Diproses|Proses)/i', $st)) $badge='bg-info';

                  $hasModFilter = request()->filled('modul');
                  $urlAll   = $e['link']        ?? null;       // ALL
                  $urlMod   = $e['link_modul']  ?? $urlAll;    // fallback ALL bila null
                @endphp
                <tr>
                  <td>{{ $tanggal }}</td>
                  <td>
                    <span class="badge rounded-pill bg-secondary" title="Kode Permintaan">
                      {{ $e['kode'] }}
                    </span>
                  </td>
                  <td>{{ $e['bahan'] }}</td>
                  <td>{{ $e['modul'] }}</td>
                  <td>{{ $e['peristiwa'] }}</td>
                  <td>
                    <span class="badge {{ $badge }}">{{ $st !== '' ? $st : '-' }}</span>
                  </td>
                  <td>{{ $e['keterangan'] ?? '-' }}</td>
                  <td class="text-end">
                    @if($hasModFilter)
                      {{-- Bila sedang filter per modul, arahkan ke detail per-modul --}}
                      @if($urlMod)
                        <a class="btn btn-sm btn-outline-primary" href="{{ $urlMod }}">
                          Detail
                        </a>
                      @endif
                    @else
                      {{-- Tanpa filter modul: beri opsi ALL vs MODUL --}}
                      <div class="btn-group">
                        <a class="btn btn-sm btn-outline-primary" href="{{ $urlAll }}">Detail</a>
                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                          <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          @if($urlAll)
                            <li>
                              <a class="dropdown-item" href="{{ $urlAll }}">
                                Detail (Semua Modul)
                              </a>
                            </li>
                          @endif
                          @if($urlMod)
                            <li>
                              <a class="dropdown-item" href="{{ $urlMod }}">
                                Detail (Modul: {{ $e['modul'] }})
                              </a>
                            </li>
                          @endif
                        </ul>
                      </div>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-muted py-2">Belum ada data.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
        {{-- ========= /Tabel ========= --}}

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
  .table thead th { white-space: nowrap; }
</style>

{{-- aktifkan feather icon --}}
<script>window.feather && window.feather.replace();</script>
@endsection
