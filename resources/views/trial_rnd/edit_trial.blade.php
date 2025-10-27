@extends('layouts.app')

@php
  use Carbon\Carbon;

  $trialBahan   = is_array($row->trial_bahan ?? null)   ? $row->trial_bahan   : [];
  $ujiFormulasi = is_array($row->uji_formulasi ?? null) ? $row->uji_formulasi : [];
  $ujiStabil    = is_array($row->uji_stabilitas ?? null)? $row->uji_stabilitas: [];
  $ujiBE        = is_array($row->uji_be ?? null)        ? $row->uji_be        : [];

  $beActive     = (bool)($row->uji_be_active ?? false);

  $lockAll      = (bool)($row->lock_all ?? false);
  $lockExisting = (bool)($row->lock_existing ?? false);
  $canAddRow    = !$lockAll;
@endphp

@section('content')
<style>
  .btn-xxs{ line-height:1; }
  .input-group .btnRemoveRow{
    margin-left:.5rem;height:calc(1.5em + .75rem + 2px);aspect-ratio:1/1;
    padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:.375rem;
  }
</style>

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header">
          <h4 class="card-title">Edit Trial R&amp;D — {{ $row->kode }}</h4>
          <p class="text-muted mb-0">
            <strong>ID Permintaan:</strong> {{ $row->kode }} &nbsp; | &nbsp;
            <strong>Nama Bahan:</strong> {{ $row->bahan_nama ?? '-' }}
          </p>
        </div>

        <div class="card-body">
          @if($lockAll)
            <div class="alert alert-success py-1 mb-1">Trial sudah <strong>final</strong>. Semua kolom dikunci.</div>
          @elseif($lockExisting)
            <div class="alert alert-info py-1 mb-1">Riwayat trial ada. <strong>Baris existing dikunci</strong>, tambahkan baris baru jika perlu.</div>
          @endif

          {{-- ====== FORM UTAMA (SATU FORM SAJA) ====== --}}
          <form method="POST" action="{{ route('trial-rnd.update', $row->id) }}">
            @csrf
            @method('PUT')

            {{-- ========== Tambah jumlah bahan (opsional) ========== --}}
<div class="border rounded p-1 mb-2">
  <div class="mb-1">
    <label class="form-label">Tambah Jumlah Bahan (opsional)</label>
    <div class="input-group">
      <input
        type="number" step="any" min="0"
        name="tambah_jumlah" class="form-control"
        placeholder="Contoh: 10 atau 10,5 atau 1.000"
        {{ $lockAll ? 'disabled' : '' }}>
      <span class="input-group-text">{{ $row->satuan ?? 'gr' }}</span>
    </div>
    <small class="text-muted">Nilai ini akan <strong>menambah</strong> stok dan bila Anda klik <em>Tambah</em> akan membuat pengajuan Sampling baru (kecuali Admin).</small>

    <div class="mt-50 d-flex gap-50">
      {{-- ⬇️ arahkan ke route add-qty (PUT) --}}
      <button
        type="submit"
        class="btn btn-success"
        formaction="{{ route('trial-rnd.add-qty', $row->id) }}"
        formmethod="POST"
        {{ $lockAll ? 'disabled' : '' }}>
        Tambah
      </button>

      {{-- tombol biasa tetap submit ke update --}}
      <button type="submit" name="aksi" value="tidak" class="btn btn-outline-secondary" {{ $lockAll ? 'disabled' : '' }}>
        Tidak
      </button>
    </div>

    <div class="text-muted mt-25">
      Jumlah saat ini: <strong>{{ number_format((float)($row->jumlah ?? 0), 2) }}</strong> {{ $row->satuan ?? 'gr' }}
    </div>
  </div>
</div>
            {{-- ========== Trial Bahan Baku ========== --}}
            <div class="border rounded p-1 mb-2">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <h6 class="mb-0">Trial Bahan Baku</h6>
                @if($canAddRow)
                  <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddTrialBahan">+ Tambah Trial</button>
                @endif
              </div>

              <div id="rowsTrialBahan">
                @forelse($trialBahan as $i => $t)
                  @php $disabled = ($lockAll || $lockExisting) ? 'disabled' : ''; @endphp
                  <div class="row g-1 align-items-end mb-1 tr-bahan" data-index="{{ $i }}">
                    <div class="col-md-3">
                      <label class="form-label">Tgl Mulai</label>
                      <input type="date" class="form-control" name="trial_bahan[{{ $i }}][mulai]"
                             value="{{ !empty($t['mulai']) ? Carbon::parse($t['mulai'])->format('Y-m-d') : '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Tgl Selesai</label>
                      <input type="date" class="form-control" name="trial_bahan[{{ $i }}][selesai]"
                             value="{{ !empty($t['selesai']) ? Carbon::parse($t['selesai'])->format('Y-m-d') : '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Status</label>
                      @php $v = $t['status'] ?? ''; @endphp
                      <select class="form-select status-select" name="trial_bahan[{{ $i }}][status]" {{ $disabled }}>
                        <option value="">Pilih...</option>
                        <option value="Sedang Trial" {{ $v==='Sedang Trial'?'selected':'' }}>Sedang Trial</option>
                        <option value="Selesai"      {{ $v==='Selesai'?'selected':'' }}>Selesai</option>
                        <option value="Gagal"        {{ $v==='Gagal'?'selected':'' }}>Gagal</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Keterangan</label>
                      <div class="input-group">
                        <input type="text" class="form-control" name="trial_bahan[{{ $i }}][keterangan]"
                               value="{{ $t['keterangan'] ?? '' }}" {{ $disabled }}>
                        @if(!$lockAll && !$lockExisting)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @empty
                  <div class="row g-1 align-items-end mb-1 tr-bahan" data-index="0">
                    <div class="col-md-3">
                      <label class="form-label">Tgl Mulai</label>
                      <input type="date" class="form-control" name="trial_bahan[0][mulai]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Tgl Selesai</label>
                      <input type="date" class="form-control" name="trial_bahan[0][selesai]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Status</label>
                      <select class="form-select status-select" name="trial_bahan[0][status]" {{ $lockAll?'disabled':'' }}>
                        <option value="">Pilih...</option>
                        <option value="Sedang Trial">Sedang Trial</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Gagal">Gagal</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Keterangan</label>
                      <div class="input-group">
                        <input type="text" class="form-control" name="trial_bahan[0][keterangan]" {{ $lockAll?'disabled':'' }}>
                        @if(!$lockAll)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforelse
              </div>
            </div>

            {{-- ========== Uji Formulasi Skala Pilot ========== --}}
            <div class="border rounded p-1 mb-2">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <h6 class="mb-0">Uji Formulasi Skala Pilot</h6>
                @if($canAddRow)
                  <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddFormulasi">+ Tambah Uji Formulasi</button>
                @endif
              </div>

              <div id="rowsFormulasi">
                @forelse($ujiFormulasi as $i => $u)
                  @php $disabled = ($lockAll || $lockExisting) ? 'disabled' : ''; @endphp
                  <div class="row g-1 align-items-end mb-1 tr-formulasi" data-index="{{ $i }}">
                    <div class="col-md-3">
                      <label class="form-label">Nama Uji</label>
                      <input type="text" class="form-control" name="uji_formulasi[{{ $i }}][nama]" value="{{ $u['nama'] ?? '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Tgl Mulai</label>
                      <input type="date" class="form-control" name="uji_formulasi[{{ $i }}][mulai]"
                             value="{{ !empty($u['mulai']) ? Carbon::parse($u['mulai'])->format('Y-m-d') : '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Tgl Selesai</label>
                      <input type="date" class="form-control" name="uji_formulasi[{{ $i }}][selesai]"
                             value="{{ !empty($u['selesai']) ? Carbon::parse($u['selesai'])->format('Y-m-d') : '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Status</label>
                      @php $v = $u['status'] ?? ''; @endphp
                      <select class="form-select status-select" name="uji_formulasi[{{ $i }}][status]" {{ $disabled }}>
                        <option value="">Pilih...</option>
                        <option value="Sedang Uji" {{ $v==='Sedang Uji'?'selected':'' }}>Sedang Uji</option>
                        <option value="Selesai"   {{ $v==='Selesai'?'selected':'' }}>Selesai</option>
                        <option value="Gagal"     {{ $v==='Gagal'?'selected':'' }}>Gagal</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Keterangan</label>
                      <div class="input-group">
                        <input type="text" class="form-control" name="uji_formulasi[{{ $i }}][keterangan]" value="{{ $u['keterangan'] ?? '' }}" {{ $disabled }}>
                        @if(!$lockAll && !$lockExisting)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @empty
                  <div class="row g-1 align-items-end mb-1 tr-formulasi" data-index="0">
                    <div class="col-md-3">
                      <label class="form-label">Nama Uji</label>
                      <input type="text" class="form-control" name="uji_formulasi[0][nama]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Tgl Mulai</label>
                      <input type="date" class="form-control" name="uji_formulasi[0][mulai]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Tgl Selesai</label>
                      <input type="date" class="form-control" name="uji_formulasi[0][selesai]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Status</label>
                      <select class="form-select status-select" name="uji_formulasi[0][status]" {{ $lockAll?'disabled':'' }}>
                        <option value="">Pilih...</option>
                        <option value="Sedang Uji">Sedang Uji</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Gagal">Gagal</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Keterangan</label>
                      <div class="input-group">
                        <input type="text" class="form-control" name="uji_formulasi[0][keterangan]" {{ $lockAll?'disabled':'' }}>
                        @if(!$lockAll)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforelse
              </div>
            </div>

            {{-- ========== Trial Uji Stabilitas ========== --}}
            <div class="border rounded p-1 mb-2">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <h6 class="mb-0">Trial Uji Stabilitas</h6>
                @if($canAddRow)
                  <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddStabil">+ Tambah Uji Stabilitas</button>
                @endif
              </div>

              <div id="rowsStabil">
                @forelse($ujiStabil as $i => $s)
                  @php $disabled = ($lockAll || $lockExisting) ? 'disabled' : ''; @endphp
                  <div class="row g-1 align-items-end mb-1 tr-stabil" data-index="{{ $i }}">
                    <div class="col-md-3">
                      <label class="form-label">Uji Stabilitas</label>
                      <input type="text" class="form-control" name="uji_stabilitas[{{ $i }}][nama]" value="{{ $s['nama'] ?? '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Tgl Mulai</label>
                      <input type="date" class="form-control" name="uji_stabilitas[{{ $i }}][mulai]"
                             value="{{ !empty($s['mulai']) ? Carbon::parse($s['mulai'])->format('Y-m-d') : '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Tgl Selesai</label>
                      <input type="date" class="form-control" name="uji_stabilitas[{{ $i }}][selesai]"
                             value="{{ !empty($s['selesai']) ? Carbon::parse($s['selesai'])->format('Y-m-d') : '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Status</label>
                      @php $v = $s['status'] ?? ''; @endphp
                      <select class="form-select status-select" name="uji_stabilitas[{{ $i }}][status]" {{ $disabled }}>
                        <option value="">Pilih...</option>
                        <option value="Sedang Uji" {{ $v==='Sedang Uji'?'selected':'' }}>Sedang Uji</option>
                        <option value="Selesai"   {{ $v==='Selesai'?'selected':'' }}>Selesai</option>
                        <option value="Gagal"     {{ $v==='Gagal'?'selected':'' }}>Gagal</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Keterangan</label>
                      <div class="input-group">
                        <input type="text" class="form-control" name="uji_stabilitas[{{ $i }}][keterangan]" value="{{ $s['keterangan'] ?? '' }}" {{ $disabled }}>
                        @if(!$lockAll && !$lockExisting)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @empty
                  <div class="row g-1 align-items-end mb-1 tr-stabil" data-index="0">
                    <div class="col-md-3">
                      <label class="form-label">Uji Stabilitas</label>
                      <input type="text" class="form-control" name="uji_stabilitas[0][nama]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Tgl Mulai</label>
                      <input type="date" class="form-control" name="uji_stabilitas[0][mulai]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Tgl Selesai</label>
                      <input type="date" class="form-control" name="uji_stabilitas[0][selesai]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label">Status</label>
                      <select class="form-select status-select" name="uji_stabilitas[0][status]" {{ $lockAll?'disabled':'' }}>
                        <option value="">Pilih...</option>
                        <option value="Sedang Uji">Sedang Uji</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Gagal">Gagal</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Keterangan</label>
                      <div class="input-group">
                        <input type="text" class="form-control" name="uji_stabilitas[0][keterangan]" {{ $lockAll?'disabled':'' }}>
                        @if(!$lockAll)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforelse
              </div>
            </div>

            {{-- ========== UJI BE (toggle) ========== --}}
            <div class="border rounded p-1 mb-2">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="chkBE" name="uji_be_active" value="1" {{ $beActive ? 'checked' : '' }} {{ $lockAll ? 'disabled' : '' }}>
                  <label class="form-check-label" for="chkBE"><strong>Aktifkan Uji BE</strong></label>
                </div>
                @if($canAddRow)
                  <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddBE">+ Tambah Uji BE</button>
                @endif
              </div>

              <div id="rowsBE" style="{{ $beActive ? '' : 'display:none' }}">
                @forelse($ujiBE as $i => $b)
                  @php $disabled = ($lockAll || $lockExisting) ? 'disabled' : ''; @endphp
                  <div class="row g-1 align-items-end mb-1 tr-be" data-index="{{ $i }}">
                    <div class="col-md-3">
                      <label class="form-label">Tgl Awal</label>
                      <input type="date" class="form-control" name="uji_be[{{ $i }}][awal]"
                             value="{{ !empty($b['awal']) ? Carbon::parse($b['awal'])->format('Y-m-d') : '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Tgl Selesai</label>
                      <input type="date" class="form-control" name="uji_be[{{ $i }}][selesai]"
                             value="{{ !empty($b['selesai']) ? Carbon::parse($b['selesai'])->format('Y-m-d') : '' }}" {{ $disabled }}>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Status Uji BE</label>
                      @php $v = $b['status'] ?? ''; @endphp
                      <select class="form-select status-select" name="uji_be[{{ $i }}][status]" {{ $disabled }}>
                        <option value="">Pilih...</option>
                        <option value="Sedang Uji" {{ $v==='Sedang Uji'?'selected':'' }}>Sedang Uji</option>
                        <option value="Selesai"   {{ $v==='Selesai'?'selected':'' }}>Selesai</option>
                        <option value="Gagal"     {{ $v==='Gagal'?'selected':'' }}>Gagal</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Keterangan</label>
                      <div class="input-group">
                        <input type="text" class="form-control" name="uji_be[{{ $i }}][keterangan]" value="{{ $b['keterangan'] ?? '' }}" {{ $disabled }}>
                        @if(!$lockAll && !$lockExisting)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @empty
                  <div class="row g-1 align-items-end mb-1 tr-be" data-index="0">
                    <div class="col-md-3">
                      <label class="form-label">Tgl Awal</label>
                      <input type="date" class="form-control" name="uji_be[0][awal]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Tgl Selesai</label>
                      <input type="date" class="form-control" name="uji_be[0][selesai]" {{ $lockAll?'disabled':'' }}>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Status Uji BE</label>
                      <select class="form-select status-select" name="uji_be[0][status]" {{ $lockAll?'disabled':'' }}>
                        <option value="">Pilih...</option>
                        <option value="Sedang Uji">Sedang Uji</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Gagal">Gagal</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Keterangan</label>
                      <div class="input-group">
                        <input type="text" class="form-control" name="uji_be[0][keterangan]" {{ $lockAll?'disabled':'' }}>
                        @if(!$lockAll)
                          <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforelse
              </div>
            </div>

            {{-- ====== Aksi ====== --}}
            <div class="mt-2 d-flex flex-wrap gap-50">
              <button type="submit" class="btn btn-success" {{ $lockAll ? 'disabled' : '' }}>
                Simpan Perubahan
              </button>

              @if(!$lockAll && ($row->can_confirm ?? true))
                <input type="hidden" name="tgl_selesai_trial" value="{{ now()->format('Y-m-d') }}">
                <select name="hasil_trial" class="form-select d-inline-block" style="width:220px">
                  <option value="Lulus Trial Keseluruhan">Lulus Trial Keseluruhan</option>
                  <option value="Tidak Lulus Trial Keseluruhan">Tidak Lulus Trial Keseluruhan</option>
                </select>

                <button
                  type="submit"
                  class="btn btn-primary ms-50"
                  formaction="{{ route('trial-rnd.confirm.update', $row->id) }}"
                  formmethod="POST">
                  Konfirmasi Trial
                </button>
              @endif
            </div>
          </form>

          @error('form')
            <div class="text-danger mt-75">{{ $message }}</div>
          @enderror
        </div>

        <div class="card-footer">
          <a href="{{ route('trial-rnd.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>

      </div>
    </div>
  </div>
</section>

@if($canAddRow)
{{-- ===== Templates untuk clone (tanpa template Bahan untuk Trial) ===== --}}
<template id="tplTrialBahan">
  <div class="row g-1 align-items-end mb-1 tr-bahan" data-index="__IDX__">
    <div class="col-md-3"><label class="form-label">Tgl Mulai</label><input type="date" class="form-control" name="trial_bahan[__IDX__][mulai]"></div>
    <div class="col-md-3"><label class="form-label">Tgl Selesai</label><input type="date" class="form-control" name="trial_bahan[__IDX__][selesai]"></div>
    <div class="col-md-2"><label class="form-label">Status</label>
      <select class="form-select status-select" name="trial_bahan[__IDX__][status]">
        <option value="">Pilih...</option><option value="Sedang Trial">Sedang Trial</option><option value="Selesai">Selesai</option><option value="Gagal">Gagal</option>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label">Keterangan</label>
      <div class="input-group">
        <input type="text" class="form-control" name="trial_bahan[__IDX__][keterangan]">
        <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
      </div>
    </div>
  </div>
</template>

<template id="tplFormulasi">
  <div class="row g-1 align-items-end mb-1 tr-formulasi" data-index="__IDX__">
    <div class="col-md-3"><label class="form-label">Nama Uji</label><input type="text" class="form-control" name="uji_formulasi[__IDX__][nama]"></div>
    <div class="col-md-2"><label class="form-label">Tgl Mulai</label><input type="date" class="form-control" name="uji_formulasi[__IDX__][mulai]"></div>
    <div class="col-md-2"><label class="form-label">Tgl Selesai</label><input type="date" class="form-control" name="uji_formulasi[__IDX__][selesai]"></div>
    <div class="col-md-2"><label class="form-label">Status</label>
      <select class="form-select status-select" name="uji_formulasi[__IDX__][status]">
        <option value="">Pilih...</option><option value="Sedang Uji">Sedang Uji</option><option value="Selesai">Selesai</option><option value="Gagal">Gagal</option>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Keterangan</label>
      <div class="input-group">
        <input type="text" class="form-control" name="uji_formulasi[__IDX__][keterangan]">
        <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
      </div>
    </div>
  </div>
</template>

<template id="tplStabil">
  <div class="row g-1 align-items-end mb-1 tr-stabil" data-index="__IDX__">
    <div class="col-md-3"><label class="form-label">Uji Stabilitas</label><input type="text" class="form-control" name="uji_stabilitas[__IDX__][nama]"></div>
    <div class="col-md-2"><label class="form-label">Tgl Mulai</label><input type="date" class="form-control" name="uji_stabilitas[__IDX__][mulai]"></div>
    <div class="col-md-2"><label class="form-label">Tgl Selesai</label><input type="date" class="form-control" name="uji_stabilitas[__IDX__][selesai]"></div>
    <div class="col-md-2"><label class="form-label">Status</label>
      <select class="form-select status-select" name="uji_stabilitas[__IDX__][status]">
        <option value="">Pilih...</option><option value="Sedang Uji">Sedang Uji</option><option value="Selesai">Selesai</option><option value="Gagal">Gagal</option>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Keterangan</label>
      <div class="input-group">
        <input type="text" class="form-control" name="uji_stabilitas[__IDX__][keterangan]">
        <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
      </div>
    </div>
  </div>
</template>

<template id="tplBE">
  <div class="row g-1 align-items-end mb-1 tr-be" data-index="__IDX__">
    <div class="col-md-3"><label class="form-label">Tgl Awal</label><input type="date" class="form-control" name="uji_be[__IDX__][awal]"></div>
    <div class="col-md-3"><label class="form-label">Tgl Selesai</label><input type="date" class="form-control" name="uji_be[__IDX__][selesai]"></div>
    <div class="col-md-3"><label class="form-label">Status Uji BE</label>
      <select class="form-select status-select" name="uji_be[__IDX__][status]">
        <option value="">Pilih...</option><option value="Sedang Uji">Sedang Uji</option><option value="Selesai">Selesai</option><option value="Gagal">Gagal</option>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Keterangan</label>
      <div class="input-group">
        <input type="text" class="form-control" name="uji_be[__IDX__][keterangan]">
        <button type="button" class="btn btn-danger btn-xxs btnRemoveRow ms-1" title="Hapus baris" aria-label="Hapus baris">&times;</button>
      </div>
    </div>
  </div>
</template>

<script>
(function () {
  const $  = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

  function nextIndex(sel){ let m=-1; $$(sel).forEach(e=>{const v=parseInt(e.dataset.index||'-1',10); if(!isNaN(v)&&v>m)m=v}); return m+1; }
  function addFromTpl(wrapSel, tplSel, rowSel){
    const idx = nextIndex(rowSel);
    const html = $(tplSel).innerHTML.replaceAll('__IDX__', String(idx));
    const tmp  = document.createElement('div'); tmp.innerHTML = html.trim();
    const node = tmp.firstElementChild; $(wrapSel).appendChild(node);
    bindRemove(node); bindStatusWatcher(node);
  }
  function bindRemove(scope){ (scope||document).querySelectorAll('.btnRemoveRow').forEach(b=>{ b.onclick=()=>b.closest('[data-index]')?.remove(); }); }

  function hasDone(wrapper){ return Array.from(wrapper.querySelectorAll('.status-select')).some(s=> (s.value||'').toLowerCase()==='selesai'); }
  function refreshAddButtons(){
    $('#btnAddTrialBahan')?.classList.toggle('d-none', hasDone($('#rowsTrialBahan')));
    $('#btnAddFormulasi')?.classList.toggle('d-none', hasDone($('#rowsFormulasi')));
    $('#btnAddStabil')?.classList.toggle('d-none', hasDone($('#rowsStabil')));
    $('#btnAddBE')?.classList.toggle('d-none', hasDone($('#rowsBE')));
  }
  function bindStatusWatcher(scope){ (scope||document).querySelectorAll('.status-select').forEach(s=> s.addEventListener('change', refreshAddButtons)); }

  // Add buttons (tanpa fitur Bahan untuk Trial)
  $('#btnAddTrialBahan')?.addEventListener('click', ()=> addFromTpl('#rowsTrialBahan', '#tplTrialBahan', '.tr-bahan'));
  $('#btnAddFormulasi')?.addEventListener('click', ()=> addFromTpl('#rowsFormulasi', '#tplFormulasi', '.tr-formulasi'));
  $('#btnAddStabil')?.addEventListener('click', ()=> addFromTpl('#rowsStabil', '#tplStabil', '.tr-stabil'));

  // BE toggle + add
  const chkBE = $('#chkBE'), rowsBE = $('#rowsBE'), btnAddBE = $('#btnAddBE');
  function toggleBE(){ if(chkBE?.checked){ rowsBE.style.display=''; btnAddBE?.removeAttribute('disabled'); }else{ rowsBE.style.display='none'; btnAddBE?.setAttribute('disabled','disabled'); } }
  chkBE?.addEventListener('change', toggleBE); toggleBE();
  btnAddBE?.addEventListener('click', ()=> addFromTpl('#rowsBE', '#tplBE', '.tr-be'));

  bindRemove(); bindStatusWatcher(); refreshAddButtons();
})();
</script>
@endif
@endsection
