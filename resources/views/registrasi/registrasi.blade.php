@extends('layouts.app')

@section('content')
@php
  use Carbon\Carbon;
  $activeTab = strtolower(request('tab', 'pending')); // pending | history
@endphp

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- Header + Tabs --}}
        <div class="card-header d-flex align-items-center justify-content-between">
          <h4 class="card-title mb-0">Registrasi NIE</h4>
          <ul class="nav nav-tabs">
            <li class="nav-item">
              <a
                class="nav-link {{ $activeTab==='pending' ? 'active' : '' }}"
                id="tab-pending-tab"
                data-bs-toggle="tab"
                href="#tab-pending"
                role="tab"
                aria-controls="tab-pending"
                aria-selected="{{ $activeTab==='pending' ? 'true' : 'false' }}"
              >Perlu Diproses</a>
            </li>
            <li class="nav-item">
              <a
                class="nav-link {{ $activeTab==='history' ? 'active' : '' }}"
                id="tab-history-tab"
                data-bs-toggle="tab"
                href="#tab-history"
                role="tab"
                aria-controls="tab-history"
                aria-selected="{{ $activeTab==='history' ? 'true' : 'false' }}"
              >Riwayat Registrasi</a>
            </li>
          </ul>
        </div>

        <div class="tab-content">
          {{-- ========= Perlu Diproses ========= --}}
          <div
            class="tab-pane fade {{ $activeTab==='pending' ? 'show active' : '' }}"
            id="tab-pending" role="tabpanel" aria-labelledby="tab-pending-tab"
          >
            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Tanggal Trial Selesai</th>
                    <th>ID Permintaan</th>
                    <th>Nama Bahan</th>
                    <th>Tgl NIE Submit</th>
                    <th>Tgl Terbit NIE</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($pending as $r)
                    @php
                      $tglTrialSelesai = !empty($r->tgl_trial_selesai) ? Carbon::parse($r->tgl_trial_selesai)->format('d/m/Y') : '-';
                      $tglSubmit       = !empty($r->tgl_nie_submit)    ? Carbon::parse($r->tgl_nie_submit)->format('d/m/Y')    : '-';
                      $tglTerbit       = !empty($r->tgl_nie_terbit)    ? Carbon::parse($r->tgl_nie_terbit)->format('d/m/Y')    : '-';
                      $label = $r->status_label ?? ($r->status_dokumen ?? '-');
                      $badge = match($label){
                        'Dokumen Lengkap'        => 'bg-success',
                        'Dokumen Belum Lengkap'  => 'bg-warning text-dark',
                        'Dokumen Tidak Lengkap'  => 'bg-danger',
                        'Registrasi'             => 'bg-info',
                        default                  => 'bg-secondary'
                      };
                    @endphp
                    <tr>
                      <td>{{ $tglTrialSelesai }}</td>
                      <td>{{ $r->kode }}</td>
                      <td>{{ $r->bahan_nama ?? '-' }}</td>
                      <td>{{ $tglSubmit }}</td>
                      <td>{{ $tglTerbit }}</td>
                      <td><span class="badge rounded-pill {{ $badge }}">{{ $label }}</span></td>
                      <td class="text-end text-nowrap">
                        {{-- Tombol Riwayat (ALL modul) --}}
                        @include('riwayat.btn', [
                          'kode'      => $r->kode,
                          'type'      => 'reg',
                          'refId'     => $r->id,
                          'modul'     => 'ALL',
                          'origin'    => 'Registrasi',
                          'originTab' => 'pending',
                          'title'     => 'Riwayat',
                          'size'      => 'sm',
                          'icon'      => false,
                          'class'     => 'me-50',
                        ])

                        <a href="{{ route('registrasi.edit', $r->id) }}" class="btn btn-outline-primary btn-sm me-50">Edit</a>
                        <a href="{{ route('registrasi.confirm.form', $r->id) }}" class="btn btn-success btn-sm">Konfirmasi</a>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          {{-- ========= Riwayat Registrasi ========= --}}
          <div
            class="tab-pane fade {{ $activeTab==='history' ? 'show active' : '' }}"
            id="tab-history" role="tabpanel" aria-labelledby="tab-history-tab"
          >
            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Tanggal Trial Selesai</th>
                    <th>ID Permintaan</th>
                    <th>Nama Bahan</th>
                    <th>Tgl NIE Submit</th>
                    <th>Tgl Terbit NIE</th>
                    <th>Hasil</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($history as $r)
                    @php
                      $tglTrialSelesai = !empty($r->tgl_trial_selesai) ? Carbon::parse($r->tgl_trial_selesai)->format('d/m/Y') : '-';
                      $tglSubmit       = !empty($r->tgl_nie_submit)    ? Carbon::parse($r->tgl_nie_submit)->format('d/m/Y')    : '-';
                      $tglTerbit       = !empty($r->tgl_nie_terbit)    ? Carbon::parse($r->tgl_nie_terbit)->format('d/m/Y')    : '-';
                      $label = $r->hasil ?? '-';
                      $badge = match($label){
                        'Disetujui'   => 'bg-success',
                        'Perlu Revisi'=> 'bg-warning text-dark',
                        'Ditolak'     => 'bg-danger',
                        default       => 'bg-secondary'
                      };
                    @endphp
                    <tr>
                      <td>{{ $tglTrialSelesai }}</td>
                      <td>{{ $r->kode }}</td>
                      <td>{{ $r->bahan_nama ?? '-' }}</td>
                      <td>{{ $tglSubmit }}</td>
                      <td>{{ $tglTerbit }}</td>
                      <td><span class="badge rounded-pill {{ $badge }}">{{ $label }}</span></td>
                      <td class="text-end text-nowrap">
                        @include('riwayat.btn', [
                          'kode'      => $r->kode,
                          'type'      => 'reg',
                          'refId'     => $r->id,
                          'modul'     => 'ALL',
                          'origin'    => 'Registrasi',
                          'originTab' => 'history',
                          'title'     => 'Riwayat',
                          'size'      => 'sm',
                          'icon'      => false,
                        ])
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="7" class="text-center text-muted">Belum ada riwayat</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div> {{-- /tab-content --}}

      </div>
    </div>
  </div>
</section>

{{-- Simpan state tab di query ?tab= --}}
<script>
document.addEventListener('shown.bs.tab', function (e) {
  try {
    var id  = e.target.getAttribute('href'); // #tab-pending | #tab-history
    var tab = (id || '').replace('#tab-','');
    var url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    history.replaceState({}, '', url);
  } catch(_) {}
});
</script>
@endsection
