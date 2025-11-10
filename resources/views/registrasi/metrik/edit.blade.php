{{-- resources/views/registrasi/metrik/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<section class="app-user-edit">
  <div class="row">
    <div class="col-12">
      <div class="card">

        {{-- ===== Header ===== --}}
        <div class="card-header d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-25">Edit Metrik Registrasi</h4>
            <div class="text-muted small">
              Kode: <strong>{{ $row->kode ?? '-' }}</strong> •
              Bahan: <strong>{{ $row->bahan_nama ?? '-' }}</strong> •
              Negara: <strong>{{ $row->negara_nama ?? '-' }}</strong>
            </div>
          </div>

          @php
            $isSelesai   = trim((string)($row->on_process_bpom ?? '')) === 'Selesai';
            $isApprove   = trim((string)($row->hasil ?? '')) === 'Disetujui';
            $hasNIE      = !empty($row->tgl_nie_terbit ?? null);
            $hasMasa     = !empty($row->masa_berlaku_nie ?? null);
            $canConfirm  = $isSelesai || $isApprove || $hasNIE || $hasMasa;
            $whyDisabled = $canConfirm ? '' : 'Belum Selesai/Disetujui. Konfirmasi tersedia jika NIE terbit atau status Disetujui.';
          @endphp

          <div class="text-end d-flex gap-50">
            <a class="btn btn-outline-secondary btn-sm"
               href="{{ route('registrasi.metrik') }}">
              <i data-feather="arrow-left"></i><span class="ms-50">Kembali</span>
            </a>

            <a class="btn btn-outline-info btn-sm"
               href="{{ route('riwayat.detail', [
                  'type'  => !empty($row->pb_id ?? null) ? 'pb' : 'reg',
                  'id'    => !empty($row->pb_id ?? null) ? $row->pb_id : $row->id,
                  'modul' => 'Registrasi',
                  'origin'=> 'metrik-registrasi'
               ]) }}">
               <i data-feather="activity"></i><span class="ms-50">Riwayat</span>
            </a>

            {{-- Tombol Konfirmasi ke Produk --}}
            @if($canConfirm)
              <a class="btn btn-primary btn-sm"
                 href="{{ route('registrasi.metrik.confirm.form', $row->id) }}">
                <i data-feather="check-circle"></i>
                <span class="ms-50">Konfirmasi ke Produk</span>
              </a>
            @else
              <button type="button" class="btn btn-primary btn-sm" disabled
                      data-bs-toggle="tooltip" title="{{ $whyDisabled }}">
                <i data-feather="check-circle"></i>
                <span class="ms-50">Konfirmasi ke Produk</span>
              </button>
            @endif
          </div>
        </div>

        {{-- ===== Banner sukses + CTA konfirmasi (muncul setelah simpan) ===== --}}
        @if(session('success'))
          <div class="px-2">
            <div class="alert alert-success d-flex align-items-center justify-content-between">
              <div>
                <strong>Berhasil.</strong> {{ session('success') }}
                @if(!$canConfirm)
                  <span class="ms-1 text-muted">
                    (Konfirmasi ke produk akan tersedia jika status sudah <em>Selesai/Disetujui</em>.)
                  </span>
                @endif
              </div>
              @if($canConfirm)
                <a class="btn btn-sm btn-success"
                   href="{{ route('registrasi.metrik.confirm.form', $row->id) }}">
                  Konfirmasi sekarang
                </a>
              @endif
            </div>
          </div>
        @endif

        {{-- ===== Body / Form ===== --}}
        <div class="card-body">
          <form method="POST" action="{{ route('registrasi.metrik.update', $row->id) }}">
            @csrf
            @method('PUT')

            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Approve Vendor Lama</label>
                <input type="text" name="approve_vendor_lama" class="form-control"
                       value="{{ old('approve_vendor_lama', $row->approve_vendor_lama) }}"
                       placeholder="ex: Ongoing / Approved / -">
              </div>

              <div class="col-md-6">
                <label class="form-label">Source Tersedia</label>
                <input type="text" name="source_tersedia" class="form-control"
                       value="{{ old('source_tersedia', $row->source_tersedia) }}"
                       placeholder="ex: Lokal / Impor / -">
              </div>

              <div class="col-md-12">
                <label class="form-label">Perubahan Desain Kemasan</label>
                <input type="text" name="perubahan_desain_kemasan" class="form-control"
                       value="{{ old('perubahan_desain_kemasan', $row->perubahan_desain_kemasan) }}"
                       placeholder="ex: Desain Baru mulai Jul 2025 / Tidak ada">
              </div>

              <div class="col-md-6">
                <label class="form-label">On Process BPOM</label>
                <input type="text" name="on_process_bpom" class="form-control"
                       value="{{ old('on_process_bpom', $row->on_process_bpom) }}"
                       placeholder="ex: Proses, Belum Ada, Selesai" disabled>
                <small class="text-muted">Info ini dihitung otomatis dari riwayat proses/NIE terbit.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Masa Berlaku NIE</label>
                <input type="text" name="masa_berlaku_nie" id="masa_berlaku_nie" class="form-control"
                       value="{{ old('masa_berlaku_nie', $row->masa_berlaku_nie ? \Carbon\Carbon::parse($row->masa_berlaku_nie)->format('Y-m-d') : '') }}"
                       placeholder="YYYY-MM-DD">
              </div>

              <div class="col-md-12">
                <label class="form-label">Keterangan</label>
                <textarea class="form-control" rows="3" name="keterangan" placeholder="Opsional...">{{ old('keterangan', $row->keterangan) }}</textarea>
              </div>
            </div>

            <div class="mt-2 d-flex align-items-center gap-1">
              <button class="btn btn-primary" type="submit">
                <i data-feather="save"></i><span class="ms-50">Simpan</span>
              </button>
              <a class="btn btn-outline-secondary" href="{{ route('registrasi.metrik') }}">
                <i data-feather="x"></i><span class="ms-50">Batal</span>
              </a>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- Inisialisasi datepicker & tooltip --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.flatpickr) {
    flatpickr('#masa_berlaku_nie', { dateFormat: 'Y-m-d', allowInput: true });
  }
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl);
  });
});
</script>
@endsection
