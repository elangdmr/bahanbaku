@extends('layouts.app')

@section('content')
<section class="app-user-edit">
  <div class="row">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-25">Konfirmasi Tujuan Produk</h4>
            <div class="text-muted small">
              Kode: <strong>{{ $kode }}</strong> •
              Bahan: <strong>{{ $bahan }}</strong>
            </div>
          </div>
          <div class="text-end">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('registrasi.metrik') }}">
              <i data-feather="arrow-left"></i><span class="ms-50">Kembali</span>
            </a>
          </div>
        </div>

        <div class="card-body">
          @if(!$boleh)
            <div class="alert alert-warning mb-2">
              Registrasi bahan ini belum <b>Terbit/Disetujui</b>, penautan produk sebaiknya dilakukan setelah statusnya final.
            </div>
          @endif

          <form method="POST" action="{{ route('registrasi.metrik.confirm.update', $row->id) }}">
            @csrf
            @method('PUT')

            <div class="row g-2">
              <div class="col-md-8">
                <label class="form-label">Pilih Produk Tujuan</label>
                <select name="produk_id" class="form-select" {{ $boleh ? '' : 'disabled' }}>
                  <option value="">— pilih produk —</option>
                  @foreach($produkList as $pid => $label)
                    <option value="{{ $pid }}" {{ (int)($row->pb_produk_id ?? 0) === (int)$pid ? 'selected' : '' }}>
                      {{ $label }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted d-block mt-50">
                  Setelah konfirmasi, bahan akan ditautkan ke produk terpilih. Pengisian <i>Peran/Qty/Satuan</i> dilakukan di halaman Edit.
                </small>
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit" {{ $boleh ? '' : 'disabled' }}>
                  <i data-feather="check-circle"></i><span class="ms-50">Konfirmasi</span>
                </button>
              </div>
            </div>
          </form>

          {{-- Ringkasan komposisi (kalau bahan ini sudah pernah ditautkan) --}}
          <hr class="my-2">
          <h6 class="mb-1">Sudah Tertaut ke Produk</h6>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th style="min-width:160px">Produk</th>
                  <th>Urutan</th>
                </tr>
              </thead>
              <tbody>
                @forelse($komposisiBahan as $k)
                  <tr>
                    <td><strong>{{ $k->produk_kode }}</strong> — {{ $k->produk_nama }}</td>
                    <td>{{ $k->urutan ?: '-' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="2" class="text-muted text-center">Belum ada penautan.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

        </div>

      </div>
    </div>
  </div>
</section>
@endsection
