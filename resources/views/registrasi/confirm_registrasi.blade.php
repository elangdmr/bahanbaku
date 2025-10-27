@extends('layouts.app')

@php
  use Carbon\Carbon;
  $tglVerif = !empty($row->tgl_verifikasi) ? Carbon::parse($row->tgl_verifikasi)->format('Y-m-d') : '';
@endphp

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header">
          <h4 class="card-title">Konfirmasi Registrasi NIE — {{ $row->kode }}</h4>
        </div>

        <div class="card-body">
          <form method="POST" action="{{ route('registrasi.confirm.update', $row->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-1">
              <strong>ID Permintaan:</strong> {{ $row->kode }}
            </div>

            <div class="mb-2">
              <label class="form-label">Registrasi NIE</label>
              <input type="text" class="form-control" name="registrasi_nie"
                     placeholder="Masukkan nomor registrasi NIE..."
                     value="{{ old('registrasi_nie', $row->registrasi_nie ?? '') }}">
              @error('registrasi_nie') <div class="text-danger mt-25">{{ $message }}</div> @enderror
            </div>

            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Tanggal Verifikasi</label>
                <input type="date" class="form-control" name="tgl_verifikasi" value="{{ old('tgl_verifikasi', $tglVerif) }}">
                @error('tgl_verifikasi') <div class="text-danger mt-25">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Hasil</label>
                @php $hs = old('hasil', $row->hasil ?? ''); @endphp
                <select class="form-select" name="hasil">
                  <option value="">Pilih hasil...</option>
                  <option value="Disetujui" {{ $hs==='Disetujui'?'selected':'' }}>Disetujui</option>
                  <option value="Perlu Revisi" {{ $hs==='Perlu Revisi'?'selected':'' }}>Perlu Revisi</option>
                  <option value="Ditolak" {{ $hs==='Ditolak'?'selected':'' }}>Ditolak</option>
                </select>
                @error('hasil') <div class="text-danger mt-25">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="mt-2">
              <label class="form-label">Keterangan</label>
              <textarea class="form-control" name="keterangan" rows="2"
                        placeholder="Isi keterangan tambahan jika ada...">{{ old('keterangan', $row->keterangan ?? '') }}</textarea>
              @error('keterangan') <div class="text-danger mt-25">{{ $message }}</div> @enderror
            </div>

            <div class="mt-3 d-flex gap-1">
              <a href="{{ route('registrasi.index') }}" class="btn btn-outline-secondary">Batal</a>
              <button type="submit" class="btn btn-success flex-fill">Konfirmasi</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
