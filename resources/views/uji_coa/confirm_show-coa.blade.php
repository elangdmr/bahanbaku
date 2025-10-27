@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h4 class="card-title">Konfirmasi Hasil Uji COA</h4>
          <a href="{{ route('uji-coa.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>

        <div class="card-body">
          <div class="row mb-2">
            <div class="col-md-4"><strong>ID Permintaan</strong></div>
            <div class="col-md-8">{{ $row->kode }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-md-4"><strong>Nama Bahan</strong></div>
            <div class="col-md-8">{{ $row->bahan_nama ?? '-' }}</div>
          </div>
          <div class="row mb-2">
            <div class="col-md-4"><strong>Tanggal COA Diterima</strong></div>
            <div class="col-md-8">
              {{ $row->tgl_coa_diterima ? \Carbon\Carbon::parse($row->tgl_coa_diterima)->format('d/m/Y') : '-' }}
            </div>
          </div>
        </div>

        <div class="card-body border-top">
          <form action="{{ route('uji-coa.confirm.update', $row->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-2">
              <label class="form-label">Hasil Uji</label>
              {{-- value HARUS "Lulus" / "Tidak Lulus" agar cocok dengan validasi Controller --}}
              <select name="hasil_uji" class="form-select @error('hasil_uji') is-invalid @enderror" required>
                <option value="" disabled selected>Pilih Hasil...</option>
                <option value="Lulus" {{ old('hasil_uji')==='Lulus' ? 'selected' : '' }}>Lulus Uji COA</option>
                <option value="Tidak Lulus" {{ old('hasil_uji')==='Tidak Lulus' ? 'selected' : '' }}>Tidak Lulus Uji COA</option>
              </select>
              @error('hasil_uji') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-success">Konfirmasi</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
