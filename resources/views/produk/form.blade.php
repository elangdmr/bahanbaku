@extends('layouts.app')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="card-title mb-0">
          {{ $mode === 'create' ? 'Tambah Produk' : 'Edit Produk' }}
        </h4>
        <a href="{{ route('produk.index') }}" class="btn btn-outline-secondary btn-sm">
          <i data-feather="arrow-left"></i><span class="ms-50">Kembali</span>
        </a>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ $mode==='create' ? route('produk.store') : route('produk.update',$row->id) }}">
          @csrf
          @if($mode==='edit') @method('PUT') @endif

          <div class="row g-2">
            <div class="col-md-3">
              <label class="form-label">Kode</label>
              <input type="text" name="kode" class="form-control @error('kode') is-invalid @enderror"
                     value="{{ old('kode',$row->kode) }}" placeholder="PRD-001">
              @error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror
              @if($mode==='create')
                <small class="text-muted">Kosongkan untuk auto-generate.</small>
              @endif
            </div>
            <div class="col-md-5">
              <label class="form-label">Nama Produk</label>
              <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                     value="{{ old('nama',$row->nama) }}" placeholder="Contoh: Paracetamol 500 mg Tablet">
              @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Brand</label>
              <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                     value="{{ old('brand',$row->brand) }}" placeholder="Contoh: SAMCO">
              @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mt-3">
            <button class="btn btn-primary" type="submit">
              <i data-feather="save"></i><span class="ms-50">{{ $mode==='create' ? 'Simpan' : 'Update' }}</span>
            </button>
            <a href="{{ route('produk.index') }}" class="btn btn-outline-secondary">
              <i data-feather="x"></i><span class="ms-50">Batal</span>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
