@extends('layouts.app')

@php
  use Carbon\Carbon;
  $tglDiterima  = !empty($row->tgl_coa_diterima) ? Carbon::parse($row->tgl_coa_diterima)->format('Y-m-d') : '';
  $details      = $row->detail_uji ?? [];
  $lockAll      = (bool)($row->lock_all ?? false);
  $lockExisting = (bool)($row->lock_existing ?? false);
  $canAddRow    = (bool)($row->can_add_row ?? true);
  $hasDetail    = is_array($details) ? count($details) : (is_countable($details) ? count($details) : 0);
@endphp

@section('content')
<section class="app-user-list">
  <style>
    .btn-xxs{ padding:.25rem .5rem; line-height:1; }
    .input-group .btnRemoveRow{ margin-left:.5rem; }
  </style>

  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header">
          <h4 class="card-title">Edit Hasil Uji COA — {{ $row->kode }}</h4>
          <p class="text-muted mb-0">
            <strong>ID Permintaan:</strong> {{ $row->kode }}
            &nbsp; | &nbsp;
            <strong>Nama Bahan:</strong> {{ $row->bahan_nama ?? '-' }}
          </p>
        </div>

        <div class="card-body">
          @if($lockAll)
            <div class="alert alert-success py-1 mb-1">Permintaan ini sudah <strong>Lulus</strong>. Semua kolom dikunci.</div>
          @elseif($lockExisting)
            <div class="alert alert-warning py-1 mb-1">Hasil pengujian terakhir <strong>Tidak Lulus</strong>. Baris existing dikunci. Tambahkan <strong>baris pengujian baru</strong> lalu simpan.</div>
          @endif

          {{-- ===== FORM UTAMA ===== --}}
          <form id="coaForm" method="POST" action="{{ route('uji-coa.update', $row->id) }}">
            @csrf
            @method('PUT')

            {{-- Tanggal COA diterima --}}
            <div class="mb-1">
              <label class="form-label">Tanggal COA Diterima</label>
              <input type="date" name="tgl_coa_diterima" class="form-control"
                     value="{{ old('tgl_coa_diterima', $tglDiterima) }}" {{ $lockAll ? 'disabled' : '' }}>
              @error('tgl_coa_diterima') <div class="text-danger mt-25">{{ $message }}</div> @enderror
            </div>

            {{-- DETAIL UJI --}}
            <div class="border rounded p-1 mb-2">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <h6 class="mb-0">Detail Pengujian</h6>
                @if($canAddRow)
                  <button type="button" id="btnAddRow" class="btn btn-sm btn-outline-primary">+ Tambah Pengujian</button>
                @endif
              </div>

              <div id="ujiRows">
                @foreach($details as $i => $d)
                  @php $disabled = ($lockExisting || $lockAll) ? 'disabled' : ''; @endphp
                  <div class="row g-1 align-items-end uji-row mb-1 existing" data-index="{{ $i }}">
                    <div class="col-md-3">
                      <label class="form-label">Pengujian</label>
                      <select class="form-select" name="details[{{ $i }}][pengujian]" {{ $disabled }}>
                        <option value="">Pilih</option>
                        <option value="Pengujian Pertama" {{ ($d['pengujian'] ?? '')==='Pengujian Pertama' ? 'selected' : '' }}>Pengujian Pertama</option>
                        <option value="Pengujian Kedua"   {{ ($d['pengujian'] ?? '')==='Pengujian Kedua'   ? 'selected' : '' }}>Pengujian Kedua</option>
                      </select>
                    </div>

                    <div class="col-md-3">
                      <label class="form-label">Hasil</label>
                      <select class="form-select" name="details[{{ $i }}][hasil]" {{ $disabled }}>
                        <option value="">Pilih</option>
                        <option value="Lulus"       {{ ($d['hasil'] ?? '')==='Lulus' ? 'selected' : '' }}>Lulus</option>
                        <option value="Tidak Lulus" {{ ($d['hasil'] ?? '')==='Tidak Lulus' ? 'selected' : '' }}>Tidak Lulus</option>
                      </select>
                    </div>

                    <div class="col-md-4">
                      <label class="form-label">Keterangan</label>
                      <input type="text" class="form-control" name="details[{{ $i }}][keterangan]"
                             value="{{ $d['keterangan'] ?? '' }}" {{ $disabled }}>
                    </div>

                    <div class="col-md-2">
                      <label class="form-label">Mulai Pengujian</label>
                      <div class="input-group">
                        <input type="date" class="form-control" name="details[{{ $i }}][mulai]"
                               value="{{ !empty($d['mulai_pengujian']) ? \Carbon\Carbon::parse($d['mulai_pengujian'])->format('Y-m-d') : '' }}"
                               {{ $disabled }}>
                        @if(!$lockExisting && !$lockAll)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-2" title="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforeach

                @if(!$details)
                  <div class="row g-1 align-items-end uji-row mb-1 new" data-index="0">
                    <div class="col-md-3">
                      <label class="form-label">Pengujian</label>
                      <select class="form-select" name="details[0][pengujian]" {{ $lockAll ? 'disabled' : '' }}>
                        <option value="">Pilih</option>
                        <option value="Pengujian Pertama">Pengujian Pertama</option>
                        <option value="Pengujian Kedua">Pengujian Kedua</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Hasil</label>
                      <select class="form-select" name="details[0][hasil]" {{ $lockAll ? 'disabled' : '' }}>
                        <option value="">Pilih</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Tidak Lulus">Tidak Lulus</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Keterangan</label>
                      <input type="text" class="form-control" name="details[0][keterangan]" value="" {{ $lockAll ? 'disabled' : '' }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Mulai Pengujian</label>
                      <div class="input-group">
                        <input type="date" class="form-control" name="details[0][mulai]" value="" {{ $lockAll ? 'disabled' : '' }}>
                        @if(!$lockAll)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-2" title="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            </div>

            {{-- Catatan umum --}}
            <div class="mb-1">
              <label class="form-label">Catatan/Keterangan Umum</label>
              <input type="text" name="keterangan" class="form-control"
                     value="{{ old('keterangan', $row->keterangan ?? '') }}" {{ $lockAll ? 'disabled' : '' }}>
            </div>

            {{-- Tombol aksi --}}
            <div class="mt-2 d-flex justify-content-between flex-wrap gap-50">
              <button type="submit" class="btn btn-success">Simpan Hasil Uji</button>

              @php
                // Biar bisa konfirmasi walau lockAll = true
                $canConfirm = !empty($tglDiterima) && ($hasDetail > 0);
              @endphp
              <button type="button"
                      id="btnOpenConfirm"
                      class="btn btn-primary"
                      data-confirm-url="{{ route('uji-coa.confirm.form', $row->id) }}?modal=1"
                      {{ $canConfirm ? '' : 'disabled' }}>
                Konfirmasi
              </button>
            </div>
          </form>

          @error('form') <div class="text-danger mt-75">{{ $message }}</div> @enderror
        </div>

        <div class="card-footer">
          <a href="{{ route('uji-coa.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- ===== Modal (muat partial via AJAX) ===== --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:560px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Hasil Uji COA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body p-0" id="confirmModalBody">
        <div class="p-2 text-center text-muted">Memuat...</div>
      </div>
    </div>
  </div>
</div>

@if($canAddRow)
<template id="ujiRowTemplate">
  <div class="row g-1 align-items-end uji-row mb-1 new" data-index="__IDX__">
    <div class="col-md-3">
      <label class="form-label">Pengujian</label>
      <select class="form-select" name="details[__IDX__][pengujian]">
        <option value="">Pilih</option>
        <option value="Pengujian Pertama">Pengujian Pertama</option>
        <option value="Pengujian Kedua">Pengujian Kedua</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Hasil</label>
      <select class="form-select" name="details[__IDX__][hasil]">
        <option value="">Pilih</option>
        <option value="Lulus">Lulus</option>
        <option value="Tidak Lulus">Tidak Lulus</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Keterangan</label>
      <input type="text" class="form-control" name="details[__IDX__][keterangan]" value="">
    </div>
    <div class="col-md-2">
      <label class="form-label">Mulai Pengujian</label>
      <div class="input-group">
        <input type="date" class="form-control" name="details[__IDX__][mulai]" value="">
        <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-2" title="Hapus baris">&times;</button>
      </div>
    </div>
  </div>
</template>
@endif

<script>
(function () {
  const wrap = document.getElementById('ujiRows');
  const btn  = document.getElementById('btnAddRow');
  const tpl  = document.getElementById('ujiRowTemplate')?.innerHTML || '';

  function maxIdx(){ let m=-1; wrap?.querySelectorAll('.uji-row').forEach(el=>{const i=+el.dataset.index||0; if(i>m)m=i;}); return m; }
  function bindDel(scope){ (scope||wrap)?.querySelectorAll('.btnRemoveRow').forEach(b=>b.onclick=()=>b.closest('.uji-row')?.remove()); }

  btn?.addEventListener('click', ()=>{
    const idx=maxIdx()+1;
    const tmp=document.createElement('div');
    tmp.innerHTML=tpl.replaceAll('__IDX__', String(idx)).trim();
    const node=tmp.firstElementChild;
    wrap.appendChild(node);
    bindDel(node);
  });
  bindDel();

  // Load halaman konfirmasi (partial) ke dalam modal
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

  if (new URLSearchParams(location.search).get('confirm') === '1') {
    openConfirmModal();
  }
})();
</script>
@endsection
