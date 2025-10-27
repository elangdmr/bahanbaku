@extends('layouts.app')

@php
  use Carbon\Carbon;

  // Repeater data
  $proses        = is_array($row->proses ?? null) ? $row->proses : [];
  $lockAll       = (bool)($row->lock_all ?? false);         // kunci total (Dokumen Lengkap / hasil final)
  $lockExisting  = (bool)($row->lock_existing ?? false);    // kunci baris existing, boleh tambah baris baru
  $canAdd        = (bool)($row->can_add_row ?? false);      // munculkan tombol +Tambah Proses?
@endphp

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header">
          <h4 class="card-title">Update Proses Registrasi NIE — {{ $row->kode ?? '-' }}</h4>
          <p class="text-muted mb-0">
            <strong>ID Permintaan:</strong> {{ $row->kode ?? '-' }} &nbsp; | &nbsp;
            <strong>Nama Bahan:</strong> {{ $row->bahan_nama ?? '-' }}
          </p>
        </div>

        <div class="card-body">

          {{-- Banner info lock --}}
          @if($lockAll)
            <div class="alert alert-success py-1 mb-2">
              Dokumen <strong>Lengkap</strong> / <strong>Final</strong>. Semua kolom terkunci.
            </div>
          @elseif($lockExisting)
            <div class="alert alert-info py-1 mb-2">
              Riwayat proses sudah ada dan belum lengkap. <strong>Baris yang sudah ada terkunci</strong>,
              namun Anda masih bisa <strong>menambah baris proses baru</strong>.
            </div>
          @endif

          <form method="POST" action="{{ route('registrasi.update', $row->id) }}">
            @csrf
            @method('PUT')

            {{-- Repeater: Tgl Submit + Tgl Terbit + Status + Keterangan + Aksi --}}
            <div class="border rounded p-1 mb-2">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <h6 class="mb-0">Proses Dokumen Registrasi</h6>
                @if($canAdd)
                  <button type="button" id="btnAddRow" class="btn btn-sm btn-outline-primary">
                    + Tambah Proses
                  </button>
                @endif
              </div>

              <div class="row fw-semibold text-muted px-1 mb-25">
                <div class="col-lg-3 col-md-3">Tanggal NIE Submit</div>
                <div class="col-lg-3 col-md-3">Tanggal Terbit NIE</div>
                <div class="col-lg-3 col-md-3">Status Dokumen</div>
                <div class="col-lg-2 col-md-2">Keterangan</div>
                <div class="col-lg-1 col-md-1 text-end">Aksi</div>
              </div>

              <div id="prosesRows">
                @forelse($proses as $i => $p)
                  @php $rowDisabled = $lockAll || $lockExisting; @endphp
                  <div class="row g-1 align-items-end proses-row mb-1" data-index="{{ $i }}">
                    <div class="col-lg-3 col-md-3">
                      <input type="date" class="form-control"
                             name="proses[{{ $i }}][tgl_submit]"
                             value="{{ !empty($p['tgl_submit']) ? Carbon::parse($p['tgl_submit'])->format('Y-m-d') : '' }}"
                             {{ $rowDisabled ? 'disabled' : '' }}>
                    </div>
                    <div class="col-lg-3 col-md-3">
                      <input type="date" class="form-control"
                             name="proses[{{ $i }}][tgl_terbit]"
                             value="{{ !empty($p['tgl_terbit']) ? Carbon::parse($p['tgl_terbit'])->format('Y-m-d') : '' }}"
                             {{ $rowDisabled ? 'disabled' : '' }}>
                    </div>
                    <div class="col-lg-3 col-md-3">
                      @php $val = $p['status_dokumen'] ?? ''; @endphp
                      <select class="form-select" name="proses[{{ $i }}][status_dokumen]" {{ $rowDisabled ? 'disabled' : '' }}>
                        <option value="">Pilih</option>
                        <option value="Dokumen Belum Lengkap" {{ $val==='Dokumen Belum Lengkap' ? 'selected':'' }}>Dokumen Belum Lengkap</option>
                        <option value="Dokumen Lengkap"       {{ $val==='Dokumen Lengkap' ? 'selected':'' }}>Dokumen Lengkap</option>
                        <option value="Dokumen Tidak Lengkap"  {{ $val==='Dokumen Tidak Lengkap' ? 'selected':'' }}>Dokumen Tidak Lengkap</option>
                      </select>
                    </div>
                    <div class="col-lg-2 col-md-2">
                      <input type="text" class="form-control"
                             name="proses[{{ $i }}][keterangan]"
                             value="{{ $p['keterangan'] ?? '' }}"
                             {{ $rowDisabled ? 'disabled' : '' }}>
                    </div>
                    <div class="col-lg-1 col-md-1 text-end">
                      @if(!$rowDisabled)
                        <button type="button" class="btn btn-sm btn-danger btnRemoveRow" title="Hapus baris">&times;</button>
                      @endif
                    </div>
                  </div>
                @empty
                  {{-- Tidak ada riwayat → sediakan satu baris awal (aktif jika tidak lockAll) --}}
                  <div class="row g-1 align-items-end proses-row mb-1" data-index="0">
                    <div class="col-lg-3 col-md-3">
                      <input type="date" class="form-control" name="proses[0][tgl_submit]" value="" {{ $lockAll ? 'disabled' : '' }}>
                    </div>
                    <div class="col-lg-3 col-md-3">
                      <input type="date" class="form-control" name="proses[0][tgl_terbit]" value="" {{ $lockAll ? 'disabled' : '' }}>
                    </div>
                    <div class="col-lg-3 col-md-3">
                      <select class="form-select" name="proses[0][status_dokumen]" {{ $lockAll ? 'disabled' : '' }}>
                        <option value="">Pilih</option>
                        <option value="Dokumen Belum Lengkap">Dokumen Belum Lengkap</option>
                        <option value="Dokumen Lengkap">Dokumen Lengkap</option>
                        <option value="Dokumen Tidak Lengkap">Dokumen Tidak Lengkap</option>
                      </select>
                    </div>
                    <div class="col-lg-2 col-md-2">
                      <input type="text" class="form-control" name="proses[0][keterangan]" value="" {{ $lockAll ? 'disabled' : '' }}>
                    </div>
                    <div class="col-lg-1 col-md-1 text-end">
                      @if(!$lockAll)
                        <button type="button" class="btn btn-sm btn-danger btnRemoveRow" title="Hapus baris">&times;</button>
                      @endif
                    </div>
                  </div>
                @endforelse
              </div>
            </div>

            {{-- Catatan Umum --}}
            <div class="mb-1">
              <label class="form-label">Keterangan Umum</label>
              <input type="text" name="keterangan" class="form-control"
                     value="{{ old('keterangan', $row->keterangan ?? '') }}"
                     {{ $lockAll ? 'disabled' : '' }}>
            </div>

            <div class="mt-2 d-grid">
              <button type="submit" class="btn btn-success" {{ $lockAll ? 'disabled' : '' }}>
                Simpan Perubahan
              </button>
            </div>
          </form>

          @error('form')
            <div class="text-danger mt-75">{{ $message }}</div>
          @enderror
        </div>

        <div class="card-footer">
          <a href="{{ route('registrasi.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
      </div>
    </div>
  </div>
</section>

@if($canAdd)
<template id="prosesRowTemplate">
  <div class="row g-1 align-items-end proses-row mb-1" data-index="__IDX__">
    <div class="col-lg-3 col-md-3">
      <input type="date" class="form-control" name="proses[__IDX__][tgl_submit]" value="">
    </div>
    <div class="col-lg-3 col-md-3">
      <input type="date" class="form-control" name="proses[__IDX__][tgl_terbit]" value="">
    </div>
    <div class="col-lg-3 col-md-3">
      <select class="form-select" name="proses[__IDX__][status_dokumen]">
        <option value="">Pilih</option>
        <option value="Dokumen Belum Lengkap">Dokumen Belum Lengkap</option>
        <option value="Dokumen Lengkap">Dokumen Lengkap</option>
        <option value="Dokumen Tidak Lengkap">Dokumen Tidak Lengkap</option>
      </select>
    </div>
    <div class="col-lg-2 col-md-2">
      <input type="text" class="form-control" name="proses[__IDX__][keterangan]" value="">
    </div>
    <div class="col-lg-1 col-md-1 text-end">
      <button type="button" class="btn btn-sm btn-danger btnRemoveRow" title="Hapus baris">&times;</button>
    </div>
  </div>
</template>

{{-- langsung inline agar pasti dieksekusi (tidak tergantung @stack) --}}
<script>
(function () {
  const wrap   = document.getElementById('prosesRows');
  const btnAdd = document.getElementById('btnAddRow');
  const tplStr = document.getElementById('prosesRowTemplate')?.innerHTML || '';

  function nextIndex() {
    let max = -1;
    wrap.querySelectorAll('.proses-row').forEach(el => {
      const i = parseInt(el.getAttribute('data-index'), 10);
      if (!isNaN(i) && i > max) max = i;
    });
    return max + 1;
  }

  function bindRemove(scope) {
    (scope || wrap).querySelectorAll('.btnRemoveRow').forEach(b => {
      b.addEventListener('click', function (e) {
        e.preventDefault();
        const row = b.closest('.proses-row');
        if (row) row.remove();
      });
    });
  }

  btnAdd?.addEventListener('click', function (e) {
    e.preventDefault();
    const idx = nextIndex();
    const tmp = document.createElement('div');
    tmp.innerHTML = (tplStr || '').replaceAll('__IDX__', String(idx)).trim();
    const node = tmp.firstElementChild;
    wrap.appendChild(node);
    bindRemove(node);
  });

  bindRemove(); // initial
})();
</script>
@endif
@endsection
