@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex align-items-center justify-content-between">
          <h4 class="card-title mb-0">Registrasi NIE</h4>
          <ul class="nav nav-tabs">
            <li class="nav-item">
              <a class="nav-link active" data-bs-toggle="tab" href="#tab-pending">Perlu Diproses</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#tab-history">Riwayat Registrasi</a>
            </li>
          </ul>
        </div>

        <div class="tab-content">
          {{-- ========= Perlu Diproses ========= --}}
          <div class="tab-pane active" id="tab-pending">
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
                      $tglTrialSelesai = !empty($r->tgl_trial_selesai) ? \Carbon\Carbon::parse($r->tgl_trial_selesai)->format('d/m/Y') : '-';
                      $tglSubmit       = !empty($r->tgl_nie_submit)    ? \Carbon\Carbon::parse($r->tgl_nie_submit)->format('d/m/Y')    : '-';
                      $tglTerbit       = !empty($r->tgl_nie_terbit)    ? \Carbon\Carbon::parse($r->tgl_nie_terbit)->format('d/m/Y')    : '-';
                      $label = $r->status_dokumen ?? '-';
                      $badge = match($label){
                        'Dokumen Lengkap'        => 'bg-success',
                        'Dokumen Belum Lengkap'  => 'bg-warning text-dark',
                        'Dokumen Tidak Lengkap'  => 'bg-danger',
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
                      <td class="text-end">
                        <a href="{{ route('registrasi.edit', $r->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
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
          <div class="tab-pane" id="tab-history">
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
                  </tr>
                </thead>
                <tbody>
                  @forelse($history as $r)
                    @php
                      $tglTrialSelesai = !empty($r->tgl_trial_selesai) ? \Carbon\Carbon::parse($r->tgl_trial_selesai)->format('d/m/Y') : '-';
                      $tglSubmit       = !empty($r->tgl_nie_submit)    ? \Carbon\Carbon::parse($r->tgl_nie_submit)->format('d/m/Y')    : '-';
                      $tglTerbit       = !empty($r->tgl_nie_terbit)    ? \Carbon\Carbon::parse($r->tgl_nie_terbit)->format('d/m/Y')    : '-';
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
                    </tr>
                  @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada riwayat</td></tr>
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
@endsection
