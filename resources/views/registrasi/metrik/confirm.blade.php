@extends('layouts.app')

@section('content')
<section class="app-user-edit">
  <div class="row">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-25">Konfirmasi ke Produk & Peran</h4>
            <div class="text-muted small">
              Kode: <strong>{{ $kode }}</strong> •
              Bahan: <strong>{{ $bahan }}</strong>
            </div>
          </div>

          <a class="btn btn-outline-secondary btn-sm" href="{{ route('registrasi.metrik.edit', $row->id) }}">
            <i data-feather="arrow-left"></i><span class="ms-50">Kembali</span>
          </a>
        </div>

        <div class="card-body">
          @if(!$boleh)
            <div class="alert alert-warning">
              Konfirmasi hanya tersedia jika status sudah <em>Disetujui</em> / NIE <em>Terbit</em>.
            </div>
          @endif

          {{-- IMPORTANT: spoofing ke PUT agar cocok dgn route --}}
          <form method="POST" action="{{ route('registrasi.metrik.confirm.update', $row->id) }}"
                onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            @method('PUT')

            {{-- supaya habis simpan balik ke halaman metrik (bukan tab baru) --}}
            <input type="hidden" name="redirect" value="edit"> {{-- edit | index --}}

            <div class="row g-2">
              <div class="col-md-7">
                <label class="form-label">Tujuan Produk</label>
                <select name="produk_id" class="form-select" required {{ $boleh ? '' : 'disabled' }}>
                  <option value="" disabled {{ empty($row->pb_produk_id) ? 'selected' : '' }}>— Pilih Produk —</option>
                  @foreach($produkList as $pid => $label)
                    <option value="{{ $pid }}" {{ (int)($row->pb_produk_id ?? 0)===(int)$pid ? 'selected' : '' }}>
                      {{ $label }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Bila sudah terhubung, boleh ganti ke produk yang benar.</small>
              </div>

              <div class="col-md-5">
                <label class="form-label">Peran Bahan di Produk</label>
                <input type="text" name="peran" class="form-control" list="peranSuggestions"
                       value="{{ old('peran', $row->pb_peran ?? '') }}"
                       placeholder="cth. API / Eksipien / Pengikat" {{ $boleh ? '' : 'disabled' }}>
                <datalist id="peranSuggestions">
                  @foreach(($peranOptions ?? []) as $opt)
                    <option value="{{ $opt }}"></option>
                  @endforeach
                </datalist>
                <small class="text-muted">Opsional, tapi disarankan diisi agar komposisi rapi.</small>
              </div>
            </div>

            <div class="mt-2">
              <button class="btn btn-primary" type="submit" {{ $boleh ? '' : 'disabled' }}>
                <i data-feather="check-circle"></i><span class="ms-50">Simpan & Tautkan</span>
              </button>
              <a class="btn btn-outline-secondary" href="{{ route('registrasi.metrik.edit', $row->id) }}">
                Batal
              </a>
            </div>
          </form>

          <hr>

          <h6 class="mb-50">Bahan ini sudah dipakai di:</h6>
          <ul class="mb-0">
            @forelse($komposisiBahan as $k)
              <li>
                <strong>{{ $k->produk_kode }}</strong> — {{ $k->produk_nama }}
                <small class="text-muted"> (urutan {{ $k->urutan }})</small>
              </li>
            @empty
              <li class="text-muted">Belum ada komposisi.</li>
            @endforelse
          </ul>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
