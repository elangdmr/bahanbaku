@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Konfirmasi Hasil Trial — {{ $row->kode }}</h4>
        </div>

        <div class="card-body">
          <form method="POST" action="{{ route('trial-rnd.confirm.update', $row->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-1">
              <label class="form-label">Tanggal Selesai Trial (Wajib diisi)</label>
              <input type="date" name="tgl_selesai_trial" class="form-control" value="{{ old('tgl_selesai_trial') }}">
              @error('tgl_selesai_trial') <div class="text-danger mt-25">{{ $message }}</div> @enderror
            </div>

            <div class="mb-1">
              <label class="form-label">Konfirmasi Hasil Trial</label>
              <select name="hasil_trial" class="form-select">
                <option value="">Pilih Hasil..</option>
                <option value="Lulus Trial Keseluruhan"     {{ old('hasil_trial')==='Lulus Trial Keseluruhan'?'selected':'' }}>Lulus Trial Keseluruhan</option>
                <option value="Tidak Lulus Trial Keseluruhan" {{ old('hasil_trial')==='Tidak Lulus Trial Keseluruhan'?'selected':'' }}>Tidak Lulus Trial Keseluruhan</option>
              </select>
              @error('hasil_trial') <div class="text-danger mt-25">{{ $message }}</div> @enderror
            </div>

            <div class="mt-2">
              <a href="{{ route('trial-rnd.index') }}" class="btn btn-outline-secondary">Batal</a>
              <button type="submit" class="btn btn-success">Konfirmasi</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
