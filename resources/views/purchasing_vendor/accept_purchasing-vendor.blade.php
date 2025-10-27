@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h4 class="card-title">Accept Permintaan — {{ $row->kode }}</h4>
          <a href="{{ route('purch-vendor.index') }}" class="btn btn-light">Kembali</a>
        </div>

        <div class="card-body">
          <div class="mb-2">
            <strong>{{ $row->bahan_nama ?? '-' }}</strong><br>
            Kategori: {{ $row->kategori }} • Jumlah: {{ rtrim(rtrim(number_format($row->jumlah,2,'.',''),'0'),'.') }} {{ $row->satuan }}
          </div>

          <form method="POST" action="{{ route('purch-vendor.accept.update', $row->id) }}">
            @csrf
            @method('PUT')

            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Tgl COA Diterima <span class="text-danger">*</span></label>
                <input name="tgl_coa_diterima" type="date" required class="form-control"
                       value="{{ old('tgl_coa_diterima', $row->tgl_coa_diterima ? \Carbon\Carbon::parse($row->tgl_coa_diterima)->format('Y-m-d') : '') }}">
              </div>
            </div>

            <div class="mt-3">
              <button class="btn btn-success" type="submit">Setujui</button>
              <a href="{{ route('purch-vendor.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
