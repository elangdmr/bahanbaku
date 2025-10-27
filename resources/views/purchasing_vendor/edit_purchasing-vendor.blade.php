@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
          <div>
            <h4 class="card-title mb-25">Edit Vendor/COA — {{ $row->kode ?? '-' }}</h4>
            <p class="text-muted mb-0">Bahan: <strong>{{ $row->bahan_nama ?? '-' }}</strong></p>
          </div>
          <a href="{{ route('purch-vendor.index') }}" class="btn btn-light">Kembali</a>
        </div>

        <div class="card-body">
          @if ($errors->any())
            <div class="alert alert-danger">
              <div class="fw-bolder mb-50">Periksa isian berikut:</div>
              <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
          @endif

          @php
            // susun daftar distributor untuk repeater
            $dists = collect(old('distributor', []))
              ->map(fn($v)=>trim((string)$v))->filter()->values()->all();

            if (empty($dists)) {
              $fromJson = [];
              if (!empty($row->distributor_list)) {
                $decoded = json_decode($row->distributor_list, true);
                if (is_array($decoded)) $fromJson = array_values(array_filter(array_map('trim', $decoded)));
              }
              $dists = $fromJson ?: ( !empty($row->distributor) ? [trim((string)$row->distributor)] : [''] );
            }
          @endphp

          {{-- FORM UTAMA --}}
          <form id="vendorForm" method="POST" action="{{ route('purch-vendor.update', $row->id) }}">
            @csrf
            @method('PUT')

            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Pabrik Pembuat</label>
                <input name="pabrik_pembuat" type="text" class="form-control @error('pabrik_pembuat') is-invalid @enderror"
                       value="{{ old('pabrik_pembuat', $row->pabrik_pembuat) }}" placeholder="Contoh: PT Samco">
                @error('pabrik_pembuat')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Negara Asal</label>
                <input name="negara_asal" type="text" class="form-control @error('negara_asal') is-invalid @enderror"
                       value="{{ old('negara_asal', $row->negara_asal) }}" placeholder="Contoh: Indonesia">
                @error('negara_asal')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="border rounded p-1 mt-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="mb-0">Distributor <small class="text-muted">(bisa lebih dari satu)</small></h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddDist">+ Tambah Distributor</button>
              </div>

              <div class="row fw-semibold text-muted px-1 mb-25">
                <div class="col-md-10">Nama Distributor</div>
                <div class="col-md-2 text-end">Aksi</div>
              </div>

              <div id="distRows">
                @foreach($dists as $i => $d)
                <div class="row g-1 align-items-end dist-row mb-1" data-index="{{ $i }}">
                  <div class="col-md-10">
                    <input type="text" class="form-control" name="distributor[{{ $i }}]" value="{{ $d }}" placeholder="Nama distributor">
                  </div>
                  <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-danger btnRemoveRow" title="Hapus">&times;</button>
                  </div>
                </div>
                @endforeach
              </div>
            </div>

            <div class="row g-2 mt-2">
              <div class="col-md-6 col-lg-3">
                <label class="form-label">Tgl Permintaan COA</label>
                <input name="tgl_permintaan_coa" type="date" class="form-control @error('tgl_permintaan_coa') is-invalid @enderror"
                       value="{{ old('tgl_permintaan_coa', $row->tgl_permintaan_coa ? \Carbon\Carbon::parse($row->tgl_permintaan_coa)->format('Y-m-d') : '') }}">
                @error('tgl_permintaan_coa')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6 col-lg-3">
                <label class="form-label">Estimasi COA Diterima</label>
                <input name="est_coa_diterima" type="date" class="form-control @error('est_coa_diterima') is-invalid @enderror"
                       value="{{ old('est_coa_diterima', $row->est_coa_diterima ? \Carbon\Carbon::parse($row->est_coa_diterima)->format('Y-m-d') : '') }}">
                @error('est_coa_diterima')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="mt-3 d-flex gap-50">
              <button class="btn btn-primary" type="submit">Simpan</button>
              <a href="{{ route('purch-vendor.index') }}" class="btn btn-outline-secondary">Batal</a>

              {{-- Tombol buka modal Accept --}}
              <button type="button" class="btn btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#acceptModal">
                Accept & Lanjut COA
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- Modal ACCEPT --}}
<div class="modal fade" id="acceptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Accept</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Tanggal COA Diterima</label>
        {{-- JANGAN required permanen, supaya tombol Simpan tetap jalan --}}
        <input type="date" id="accDate" name="tgl_coa_diterima" form="vendorForm" class="form-control"
               value="{{ old('tgl_coa_diterima', $row->tgl_coa_diterima ? \Carbon\Carbon::parse($row->tgl_coa_diterima)->format('Y-m-d') : '') }}">
        <small class="text-muted">Menekan <strong>Accept</strong> akan menyimpan Vendor & mengalihkan ke Hasil Uji COA.</small>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        {{-- submit form utama, tapi ke route ACCEPT --}}
        <button type="submit" id="btnAccept" class="btn btn-success"
                form="vendorForm"
                formaction="{{ route('purch-vendor.accept.update', $row->id) }}">
          Accept
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Template + JS repeater --}}
<template id="distRowTemplate">
  <div class="row g-1 align-items-end dist-row mb-1" data-index="__IDX__">
    <div class="col-md-10"><input type="text" class="form-control" name="distributor[__IDX__]" placeholder="Nama distributor"></div>
    <div class="col-md-2 text-end"><button type="button" class="btn btn-sm btn-danger btnRemoveRow">&times;</button></div>
  </div>
</template>

<script>
(function () {
  const wrap = document.getElementById('distRows');
  const btnAdd = document.getElementById('btnAddDist');
  const tpl = document.getElementById('distRowTemplate').innerHTML;

  function nextIdx() {
    let m = -1;
    wrap.querySelectorAll('.dist-row').forEach(el => {
      const i = parseInt(el.getAttribute('data-index'), 10);
      if (!isNaN(i) && i > m) m = i;
    });
    return m + 1;
  }
  function bindRemove(scope) {
    (scope || wrap).querySelectorAll('.btnRemoveRow').forEach(b => {
      b.onclick = () => b.closest('.dist-row')?.remove();
    });
  }
  btnAdd?.addEventListener('click', () => {
    const idx = nextIdx();
    const tmp = document.createElement('div');
    tmp.innerHTML = tpl.replaceAll('__IDX__', String(idx)).trim();
    wrap.appendChild(tmp.firstElementChild);
    bindRemove();
  });
  bindRemove();

  // Saat klik Accept → jadikan tanggal required (validasi HTML5)
  const btnAccept = document.getElementById('btnAccept');
  const accDate   = document.getElementById('accDate');
  btnAccept?.addEventListener('click', () => {
    accDate.setAttribute('required','required');
  });

  // auto-open modal jika datang dari tombol Accept di list (?accept=1)
  if (new URLSearchParams(location.search).get('accept') === '1') {
    const m = new bootstrap.Modal(document.getElementById('acceptModal'));
    m.show();
  }
})();
</script>
@endsection
