@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title">Tambah Permintaan Bahan Baku</h4>
          <a href="{{ route('show-permintaan') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>

        <div class="card-body">
          <form action="{{ url('/permintaan-bahan-baku') }}" method="POST" class="row g-1">
            @csrf

            {{-- Bahan --}}
            <div class="col-12">
              <label class="form-label">Bahan Baku</label>
              <select name="bahan_id" class="form-select">
                <option value="" {{ old('bahan_id') ? '' : 'selected' }}>— Pilih bahan —</option>
                @foreach(($bahans ?? []) as $b)
                  <option value="{{ $b->id }}" {{ old('bahan_id') == $b->id ? 'selected' : '' }}>
                    {{ $b->nama ?? $b->name }}
                  </option>
                @endforeach
              </select>
              @error('bahan_id') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Jumlah --}}
            <div class="col-md-6 col-12">
              <label class="form-label">Jumlah</label>
              <input type="number" step="0.01" min="0" name="jumlah" class="form-control"
                     value="{{ old('jumlah') }}" />
              @error('jumlah') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Satuan --}}
            <div class="col-md-6 col-12">
              <label class="form-label">Satuan</label>
              <select name="satuan" class="form-select">
                @php($satuans = $satuans ?? ['gr','kg','ml','L','pcs'])
                @foreach($satuans as $sat)
                  <option value="{{ $sat }}" {{ old('satuan') === $sat ? 'selected' : '' }}>{{ $sat }}</option>
                @endforeach
              </select>
              @error('satuan') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Kategori --}}
            <div class="col-md-6 col-12">
              <label class="form-label">Kategori</label>
              <select name="kategori" class="form-select">
                @php($kategoriOpts = $kategoriOpts ?? ['Bahan Aktif','Bahan Pembantu','Lainnya'])
                @foreach($kategoriOpts as $opt)
                  <option value="{{ $opt }}" {{ old('kategori') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
              </select>
              @error('kategori') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Tanggal Kebutuhan --}}
            <div class="col-md-6 col-12">
              <label class="form-label">Tanggal Kebutuhan</label>
              <input type="date" name="tanggal_kebutuhan" class="form-control"
                     value="{{ old('tanggal_kebutuhan') }}" />
              @error('tanggal_kebutuhan') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Alasan --}}
            <div class="col-12">
              <label class="form-label">Alasan / Keterangan</label>
              <textarea name="alasan" class="form-control" rows="3">{{ old('alasan') }}</textarea>
              @error('alasan') <div class="text-danger mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- (opsional) Kalau mau paksa default Pending dari form: --}}
            {{-- <input type="hidden" name="status" value="Pending"> --}}

            <div class="col-12 mt-1">
              <button type="submit" class="btn btn-primary me-50">Simpan</button>
              <a href="{{ route('show-permintaan') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
