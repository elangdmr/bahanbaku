@extends('layouts.app')

@php
  $title = $isEdit ? 'Edit Bahan' : 'Tambah Bahan';
@endphp

@section('content')
<section class="app-user-list">
  <div class="row"><div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">{{ $title }}</h4>
        <a href="{{ route('bahan.index') }}" class="btn btn-outline-secondary">Kembali</a>
      </div>

      <div class="card-body">
        <form method="POST"
              action="{{ $isEdit ? route('bahan.update',$bahan->id) : route('bahan.store') }}">
          @csrf
          @if($isEdit) @method('PUT') @endif

          <div class="row g-1">
            <div class="col-md-6">
              <label class="form-label">Nama Bahan <span class="text-danger">*</span></label>
              <input type="text" name="nama" class="form-control"
                     value="{{ old('nama',$bahan->nama) }}" required>
              @error('nama')<div class="text-danger mt-25">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
              <label class="form-label">Satuan Default <span class="text-danger">*</span></label>
              <select name="satuan_default" class="form-select" required>
                @foreach($satuanOptions as $opt)
                  <option value="{{ $opt }}" {{ old('satuan_default',$bahan->satuan_default ?? 'gr')==$opt?'selected':'' }}>
                    {{ $opt }}
                  </option>
                @endforeach
              </select>
              @error('satuan_default')<div class="text-danger mt-25">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
              <label class="form-label">Kategori Default <span class="text-danger">*</span></label>
              <select name="kategori_default" class="form-select" required>
                @foreach($kategoriOptions as $opt)
                  <option value="{{ $opt }}" {{ old('kategori_default',$bahan->kategori_default ?? 'Bahan Aktif')==$opt?'selected':'' }}>
                    {{ $opt }}
                  </option>
                @endforeach
              </select>
              @error('kategori_default')<div class="text-danger mt-25">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mt-2 d-grid">
            <button class="btn btn-success">Simpan</button>
          </div>
        </form>
      </div>

    </div>
  </div></div>
</section>
@endsection
