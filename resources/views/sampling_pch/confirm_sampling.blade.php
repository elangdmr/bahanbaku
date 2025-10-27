@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Konfirmasi Penerimaan Sampling — {{ $row->kode }}</h4>
        </div>

        <div class="card-body">
          <form method="POST" action="{{ route('sampling-pch.confirm.update', $row->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-1">
              <label class="form-label">Realisasi Tanggal Sampling Diterima (Wajib diisi)</label>
              <input type="date" name="tgl_sampling_diterima" class="form-control"
                     value="{{ old('tgl_sampling_diterima') }}">
              @error('tgl_sampling_diterima') <div class="text-danger mt-25">{{ $message }}</div> @enderror
            </div>

            <div class="mt-2">
              <a href="{{ route('sampling-pch.index') }}" class="btn btn-outline-secondary">Batal</a>
              <button type="submit" class="btn btn-success">Konfirmasi</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
