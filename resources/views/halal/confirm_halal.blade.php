@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h4 class="card-title">Konfirmasi Hasil Halal</h4>
          <a href="{{ route('halal.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
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
        </div>

        <div class="card-body border-top">
          @include('halal.parts.confirm_modal', ['row' => $row])
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
