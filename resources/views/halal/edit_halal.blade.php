@extends('layouts.app')

@php
  use Carbon\Carbon;

  $proses           = is_array($row->proses ?? null) ? $row->proses : [];
  $lockAll          = (bool)($row->lock_all ?? false);
  $lockExisting     = (bool)($row->lock_existing ?? false);   // tetap tersedia untuk tombol hapus
  $canAdd           = (bool)($row->can_add_row ?? false);
  $canConfirm       = (bool)($row->can_confirm ?? false);
  $distributorList  = is_array($row->distributor_list ?? null) ? $row->distributor_list : [];

  // info role untuk Blade (admin selalu bebas)
  $role      = strtolower((string) (auth()->user()->role ?? ''));
  $isAdminUI = in_array($role, ['admin','administrator','superadmin'], true);
@endphp

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header">
          <h4 class="card-title">Update Proses Halal — {{ $row->kode }}</h4>
          <p class="text-muted mb-0">
            <strong>ID Permintaan:</strong> {{ $row->kode }} &nbsp; | &nbsp;
            <strong>Nama Bahan:</strong> {{ $row->bahan_nama ?? '-' }}
          </p>
        </div>

        <div class="card-body">

          @if($lockAll)
            <div class="alert alert-success py-1 mb-2">
              Status dokumen <strong>Lengkap</strong>. Isian dikunci, namun Anda masih bisa <strong>Konfirmasi</strong> di bawah.
            </div>
          @elseif($lockExisting)
            <div class="alert alert-info py-1 mb-2">
              Riwayat proses sudah ada. <strong>Baris lama</strong> dikunci. Silakan <strong>tambah baris baru</strong> bila perlu.
            </div>
          @endif

          <div class="border rounded p-1 mb-2">
            <div class="row g-1">
              <div class="col-md-4">
                <label class="form-label">Pabrik Pembuat</label>
                <input class="form-control" value="{{ $row->pabrik_pembuat ?? '-' }}" disabled>
              </div>
              <div class="col-md-4">
                <label class="form-label">Negara Asal</label>
                <input class="form-control" value="{{ $row->negara_asal ?? '-' }}" disabled>
              </div>
              <div class="col-md-4">
                <label class="form-label">Distributor</label>
                @if(count($distributorList))
                  <div class="d-flex flex-wrap gap-50">
                    @foreach($distributorList as $dist)
                      <span class="badge rounded-pill bg-light-primary text-dark mb-50">{{ $dist }}</span>
                    @endforeach
                  </div>
                @else
                  <input class="form-control" value="{{ $row->distributor ?? '-' }}" disabled>
                @endif
              </div>
            </div>
          </div>

          <form id="formHalal" method="POST" action="{{ route('halal.update', $row->id) }}">
            @csrf
            @method('PUT')

            <div class="border rounded p-1 mb-2">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <h6 class="mb-0">Proses Dokumen Halal</h6>
                @if($canAdd)
                  <button type="button" id="btnAddRow" class="btn btn-sm btn-outline-primary">+ Tambah Proses</button>
                @endif
              </div>

              <div class="row fw-semibold text-muted px-1 mb-25">
                <div class="col-md-3">Tanggal Pengajuan</div>
                <div class="col-md-3">Tanggal Terima Dokumen</div>
                <div class="col-md-3">Status Dokumen</div>
                <div class="col-md-2">Keterangan</div>
                <div class="col-md-1 text-end">Aksi</div>
              </div>

              <div id="prosesRows">
                @forelse($proses as $i => $p)
                  @php
                    // KUNCI GLOBAL
                    $globDisabled = ($lockAll || $lockExisting) ? 'disabled' : '';

                    // KUNCI BERURUTAN (hanya saat render ulang)
                    $disPeng = $globDisabled;
                    $disTer  = $globDisabled;

                    if (!$isAdminUI && !$globDisabled) {
                      $hasPeng = !empty($p['tgl_pengajuan']);
                      $hasTer  = !empty($p['tgl_terima']);

                      if ($hasTer) {
                        $disPeng = 'disabled';
                        $disTer  = 'disabled';
                      } elseif ($hasPeng) {
                        $disPeng = 'disabled';
                      }
                    }

                    // Status & keterangan hanya ikut kunci global
                    $disStatusKet = $globDisabled;
                  @endphp

                  <div class="row g-1 align-items-end proses-row mb-1" data-index="{{ $i }}">
                    <div class="col-md-3">
                      <input type="date"
                             class="form-control fld-pengajuan"
                             name="proses[{{ $i }}][tgl_pengajuan]"
                             value="{{ !empty($p['tgl_pengajuan']) ? Carbon::parse($p['tgl_pengajuan'])->format('Y-m-d') : '' }}"
                             {{ $disPeng }}>
                    </div>
                    <div class="col-md-3">
                      <input type="date"
                             class="form-control fld-terima"
                             name="proses[{{ $i }}][tgl_terima]"
                             value="{{ !empty($p['tgl_terima']) ? Carbon::parse($p['tgl_terima'])->format('Y-m-d') : '' }}"
                             {{ $disTer }}>
                    </div>
                    <div class="col-md-3">
                      @php $val = $p['status_dokumen'] ?? ''; @endphp
                      <select class="form-select fld-status"
                              name="proses[{{ $i }}][status_dokumen]"
                              {{ $disStatusKet }}>
                        <option value="">Pilih</option>
                        <option value="Dokumen Belum Lengkap" {{ $val==='Dokumen Belum Lengkap' ? 'selected':'' }}>Dokumen Belum Lengkap</option>
                        <option value="Dokumen Lengkap"       {{ $val==='Dokumen Lengkap' ? 'selected':'' }}>Dokumen Lengkap</option>
                        <option value="Dokumen Tidak Lengkap"  {{ $val==='Dokumen Tidak Lengkap' ? 'selected':'' }}>Dokumen Tidak Lengkap</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <input type="text"
                             class="form-control"
                             name="proses[{{ $i }}][keterangan]"
                             value="{{ $p['keterangan'] ?? '' }}"
                             {{ $disStatusKet }}>
                    </div>
                    <div class="col-md-1 text-end">
                      @if(!$lockAll && !$lockExisting)
                        <button type="button" class="btn btn-sm btn-danger btnRemoveRow" title="Hapus baris">&times;</button>
                      @endif
                    </div>
                  </div>
                @empty
                  <div class="row g-1 align-items-end proses-row mb-1" data-index="0">
                    <div class="col-md-3">
                      <input type="date" class="form-control fld-pengajuan"
                             name="proses[0][tgl_pengajuan]" value=""
                             {{ $lockAll ? 'disabled' : '' }}>
                    </div>
                    <div class="col-md-3">
                      <input type="date" class="form-control fld-terima"
                             name="proses[0][tgl_terima]" value=""
                             {{ $lockAll ? 'disabled' : '' }}>
                    </div>
                    <div class="col-md-3">
                      <select class="form-select fld-status"
                              name="proses[0][status_dokumen]"
                              {{ $lockAll ? 'disabled' : '' }}>
                        <option value="">Pilih</option>
                        <option value="Dokumen Belum Lengkap">Dokumen Belum Lengkap</option>
                        <option value="Dokumen Lengkap">Dokumen Lengkap</option>
                        <option value="Dokumen Tidak Lengkap">Dokumen Tidak Lengkap</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <input type="text" class="form-control"
                             name="proses[0][keterangan]" value=""
                             {{ $lockAll ? 'disabled' : '' }}>
                    </div>
                    <div class="col-md-1 text-end">
                      @if(!$lockAll)
                        <button type="button" class="btn btn-sm btn-danger btnRemoveRow" title="Hapus baris">&times;</button>
                      @endif
                    </div>
                  </div>
                @endforelse
              </div>
            </div>

            <div class="mb-1">
              <label class="form-label">Keterangan Umum</label>
              <input type="text" name="keterangan" class="form-control"
                     value="{{ old('keterangan', $row->keterangan ?? '') }}"
                     {{ $lockAll ? 'disabled' : '' }}>
            </div>

            <div class="mt-2 d-flex justify-content-between align-items-center">
              <a href="{{ route('halal.index') }}" class="btn btn-outline-secondary">Kembali</a>

              <div class="d-flex gap-50">
                <button type="submit" class="btn btn-success" {{ $lockAll ? 'disabled' : '' }}>
                  Simpan Proses Halal
                </button>

                {{-- TOMBOL KONFIRMASI: buka modal --}}
                @if($canConfirm)
                  <button type="button"
                          id="btnOpenConfirm"
                          class="btn btn-primary"
                          data-confirm-url="{{ route('halal.confirm.form', $row->id) }}?modal=1">
                    Konfirmasi Dokumen Lengkap
                  </button>
                @endif
              </div>
            </div>
          </form>

          @error('form')
            <div class="text-danger mt-75">{{ $message }}</div>
          @enderror
        </div>

      </div>
    </div>
  </div>
</section>

{{-- ===== Modal Konfirmasi (konten di-load via AJAX) ===== --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Hasil Halal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body p-0" id="confirmModalBody">
        <div class="p-2 text-center text-muted">Memuat...</div>
      </div>
    </div>
  </div>
</div>

{{-- Template baris baru (clone) --}}
@if($canAdd)
<template id="prosesRowTemplate">
  <div class="row g-1 align-items-end proses-row mb-1" data-index="__IDX__">
    <div class="col-md-3"><input type="date" class="form-control fld-pengajuan" name="proses[__IDX__][tgl_pengajuan]" value=""></div>
    <div class="col-md-3"><input type="date" class="form-control fld-terima"    name="proses[__IDX__][tgl_terima]"    value=""></div>
    <div class="col-md-3">
      <select class="form-select fld-status" name="proses[__IDX__][status_dokumen]">
        <option value="">Pilih</option>
        <option value="Dokumen Belum Lengkap">Dokumen Belum Lengkap</option>
        <option value="Dokumen Lengkap">Dokumen Lengkap</option>
        <option value="Dokumen Tidak Lengkap">Dokumen Tidak Lengkap</option>
      </select>
    </div>
    <div class="col-md-2"><input type="text" class="form-control" name="proses[__IDX__][keterangan]" value=""></div>
    <div class="col-md-1 text-end">
      <button type="button" class="btn btn-sm btn-danger btnRemoveRow" title="Hapus baris">&times;</button>
    </div>
  </div>
</template>
@endif

<script>
(function () {
  // Repeater minimal
  const form   = document.getElementById('formHalal');
  const wrap   = document.getElementById('prosesRows');
  const btnAdd = document.getElementById('btnAddRow');
  const tpl    = document.getElementById('prosesRowTemplate')?.innerHTML || '';

  function maxIdx() {
    let m = -1;
    wrap?.querySelectorAll('.proses-row').forEach(el => {
      const i = parseInt(el.getAttribute('data-index'), 10);
      if (!isNaN(i) && i > m) m = i;
    });
    return m;
  }
  function bindRemove(scope) {
    (scope || wrap)?.querySelectorAll('.btnRemoveRow').forEach(b => {
      b.onclick = () => b.closest('.proses-row')?.remove();
    });
  }

  btnAdd?.addEventListener('click', () => {
    const next = maxIdx() + 1;
    const html = tpl.replaceAll('__IDX__', String(next));
    const tmp  = document.createElement('div');
    tmp.innerHTML = html.trim();
    const node = tmp.firstElementChild;
    wrap.appendChild(node);
    bindRemove(node);
  });
  bindRemove();

  // Guard: jika tgl_terima diisi, status wajib diisi
  form?.addEventListener('submit', (e) => {
    let invalid = false;
    wrap?.querySelectorAll('.proses-row').forEach((row) => {
      const terima = row.querySelector('.fld-terima')?.value?.trim();
      const status = row.querySelector('.fld-status')?.value?.trim();
      if (terima && !status) invalid = true;
    });
    if (invalid) {
      e.preventDefault();
      alert('Status Dokumen wajib dipilih pada setiap baris yang memiliki Tanggal Terima Dokumen.');
    }
  });

  // Modal konfirmasi (AJAX)
  const openBtn   = document.getElementById('btnOpenConfirm');
  const modalEl   = document.getElementById('confirmModal');
  const modalBody = document.getElementById('confirmModalBody');

  async function openConfirmModal() {
    if (!openBtn || !modalEl || !modalBody) return;
    const url = openBtn.getAttribute('data-confirm-url');
    modalBody.innerHTML = '<div class="p-2 text-center text-muted">Memuat...</div>';
    try {
      const res  = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
      const html = await res.text();
      modalBody.innerHTML = html;
    } catch (e) {
      modalBody.innerHTML = '<div class="p-2 text-danger">Gagal memuat form konfirmasi.</div>';
    }
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  }
  openBtn?.addEventListener('click', openConfirmModal);
})();
</script>
@endsection
