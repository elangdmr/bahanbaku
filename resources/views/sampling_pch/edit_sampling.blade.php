@extends('layouts.app')

@php
  use Carbon\Carbon;

  $tglPermintaan = !empty($row->tgl_sampling_permintaan) ? Carbon::parse($row->tgl_sampling_permintaan)->format('Y-m-d') : '';
  $tglEstimasi   = !empty($row->est_sampling_diterima)   ? Carbon::parse($row->est_sampling_diterima)->format('Y-m-d')   : '';
  $tglDikirim    = !empty($row->tgl_sampling_dikirim)    ? Carbon::parse($row->tgl_sampling_dikirim)->format('Y-m-d')    : '';
  $tglDiterima   = !empty($row->tgl_sampling_diterima)   ? Carbon::parse($row->tgl_sampling_diterima)->format('Y-m-d')   : '';

  // flags dari controller
  $lockAll        = (bool)($row->lock_all ?? false);          // terkunci total jika sudah diterima (kecuali admin)
  $fromTrial      = (bool)($row->from_trial ?? false);
  $lockFromTrial  = (bool)($row->lock_from_trial ?? false);   // non-admin & asal Trial → kunci sebagian field

  // Boleh tampilkan tombol Konfirmasi:
  $canConfirm = (
    !$lockAll &&
    $tglPermintaan &&
    ($tglEstimasi || $fromTrial) &&
    $tglDikirim
  );

  // Nilai default untuk form "Tambah Proses"
  $defPermintaanBaru = now()->format('Y-m-d');
  $defKetBaru        = trim(($row->keterangan ?? '').' [PROSES BARU]');
@endphp

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title">Update Data Sampling — {{ $row->kode }}</h4>
            <p class="text-muted mb-0">
              <strong>ID Permintaan:</strong> {{ $row->kode }} &nbsp; | &nbsp;
              <strong>Nama Bahan:</strong> {{ $row->bahan_nama ?? '-' }}
            </p>
          </div>

          {{-- Tombol toggle Tambah Proses (muncul bila tidak lock total) --}}
          @if(!$lockAll)
            <button class="btn btn-warning" id="btnToggleNewProcess">+ Tambah Proses</button>
          @endif
        </div>

        <div class="card-body">
          @if($lockAll)
            <div class="alert alert-success py-1 mb-2">
              Sampling sudah <strong>diterima</strong>. Semua kolom dikunci.
            </div>
          @elseif($fromTrial)
            <div class="alert alert-warning py-1 mb-2">
              Tiket ini <strong>berasal dari Trial</strong>. Untuk non-admin, kolom
              <em>Tanggal Permintaan</em>, <em>Tanggal Estimasi Diterima</em>, dan <em>Keterangan</em> dikunci.
              Silakan isi <em>Tanggal Sampling Dikirim</em> lalu lakukan <strong>Konfirmasi Diterima</strong>.
            </div>
          @endif

          {{-- ================== FORM UTAMA ================== --}}
          <form method="POST" action="{{ route('sampling-pch.update', $row->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-1">
              <label class="form-label">Tanggal Permintaan Sampling</label>
              <input type="date" name="tgl_sampling_permintaan" class="form-control"
                     value="{{ old('tgl_sampling_permintaan', $tglPermintaan) }}"
                     {{ ($lockAll || $lockFromTrial) ? 'disabled' : '' }}>
              @error('tgl_sampling_permintaan') <div class="text-danger mt-25">{{ $message }}</div> @enderror
            </div>

            <div class="mb-1">
              <label class="form-label">Tanggal Estimasi Sampling Diterima</label>
              <input type="date" name="est_sampling_diterima" class="form-control"
                     value="{{ old('est_sampling_diterima', $tglEstimasi) }}"
                     {{ ($lockAll || $lockFromTrial) ? 'disabled' : '' }}>
              @error('est_sampling_diterima') <div class="text-danger mt-25">{{ $message }}</div> @enderror
            </div>

            <div class="mb-1">
              <label class="form-label">Tanggal Sampling Dikirim</label>
              <input type="date" name="tgl_sampling_dikirim" class="form-control"
                     value="{{ old('tgl_sampling_dikirim', $tglDikirim) }}"
                     {{ $lockAll ? 'disabled' : '' }}>
              @error('tgl_sampling_dikirim') <div class="text-danger mt-25">{{ $message }}</div> @enderror
            </div>

            <div class="mb-1">
              <label class="form-label">Keterangan</label>
              <input type="text" name="keterangan" class="form-control"
                     value="{{ old('keterangan', $row->keterangan ?? '') }}"
                     {{ ($lockAll || $lockFromTrial) ? 'disabled' : '' }}>
            </div>

            <div class="mt-2 d-grid">
              <button type="submit" class="btn btn-success" {{ $lockAll ? 'disabled' : '' }}>
                Simpan Perubahan
              </button>
            </div>
          </form>

          {{-- ================== FORM TAMBAH PROSES (INLINE) ================== --}}
          @if(!$lockAll)
            <div id="newProcessWrap" class="border rounded p-1 mt-2" style="display:none">
              <h6 class="mb-1">Tambah Proses (Tanggal Baru)</h6>
              <form method="POST" action="{{ route('sampling-pch.process.inline', $row->id) }}" class="row g-1">
                @csrf
                @method('PUT')
                <div class="col-md-3">
                  <label class="form-label">Tanggal Permintaan Sampling (baru)</label>
                  <input type="date" name="new_tgl_permintaan" class="form-control"
                         value="{{ old('new_tgl_permintaan', $defPermintaanBaru) }}" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Tanggal Estimasi Diterima (baru)</label>
                  <input type="date" name="new_est_sampling_diterima" class="form-control"
                         value="{{ old('new_est_sampling_diterima') }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Tanggal Sampling Dikirim (baru)</label>
                  <input type="date" name="new_tgl_sampling_dikirim" class="form-control"
                         value="{{ old('new_tgl_sampling_dikirim') }}">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Keterangan (baru)</label>
                  <input type="text" name="new_keterangan" class="form-control"
                         value="{{ old('new_keterangan', $defKetBaru) }}">
                </div>
                <div class="col-12 mt-1">
                  <button type="submit" class="btn btn-warning">Simpan Proses</button>
                </div>
              </form>
              <small class="text-muted d-block mt-50">
                Menyimpan proses baru akan memperbarui tanggal & keterangan di database, mengosongkan
                <em>Tanggal Sampling Diterima</em>, dan tetap berada di tahap <strong>Sampling</strong>.
              </small>
            </div>
          @endif

          {{-- ================== FORM KONFIRMASI (TERPISAH) ================== --}}
          @if($canConfirm)
            <hr class="my-2">
            <form method="POST" action="{{ route('sampling-pch.confirm.update', $row->id) }}" class="row g-1 align-items-end">
              @csrf
              @method('PUT')
              <div class="col-md-4">
                <label class="form-label">Tanggal Sampling Diterima</label>
                <input type="date" name="tgl_sampling_diterima" class="form-control"
                       value="{{ old('tgl_sampling_diterima', $tglDiterima ?: now()->format('Y-m-d')) }}">
                @error('tgl_sampling_diterima') <div class="text-danger mt-25">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Konfirmasi Diterima &amp; Lanjut Trial R&amp;D</button>
              </div>
            </form>
          @endif

          @error('form')
            <div class="text-danger mt-75">{{ $message }}</div>
          @enderror
        </div>

        <div class="card-footer">
          <a href="{{ route('sampling-pch.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>

      </div>
    </div>
  </div>
</section>

@if(!$lockAll)
<script>
  (function(){
    const btn = document.getElementById('btnToggleNewProcess');
    const wrap = document.getElementById('newProcessWrap');
    btn?.addEventListener('click', () => {
      if (!wrap) return;
      wrap.style.display = (wrap.style.display === 'none' || wrap.style.display === '') ? 'block' : 'none';
    });
  })();
</script>
@endif
@endsection
