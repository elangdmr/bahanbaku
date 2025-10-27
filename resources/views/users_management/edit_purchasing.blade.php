@extends('layouts.app')

@section('content')
<section id="multiple-column-form">
  <div class="row">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0">Edit Akun Purchasing</h4>
          <a href="{{ route('show-purchasing') }}" class="btn btn-outline-secondary">
            Kembali
          </a>
        </div>

        <div class="card-body">
          {{-- Alert validasi global (opsional) --}}
          @if ($errors->any())
            <div class="alert alert-danger">
              <div class="fw-bold mb-1">Periksa kembali isian berikut:</div>
              <ul class="mb-0">
                @foreach ($errors->all() as $err)
                  <li>{{ $err }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form class="form" action="{{ url('/show-purchasing/'.$purchasing->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
              {{-- Nama --}}
              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Nama</label>
                  <input
                    type="text"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="Masukkan Nama"
                    value="{{ old('name', $purchasing->name) }}"
                  />
                  @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>

              {{-- Email --}}
              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Email</label>
                  <input
                    type="email"
                    name="email"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="Masukkan Email"
                    value="{{ old('email', $purchasing->email) }}"
                  />
                  @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>

              {{-- Password (opsional) --}}
              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Password (opsional)</label>
                  <input
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Kosongkan jika tidak diubah"
                    autocomplete="new-password"
                  />
                  @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>

              {{-- Role (read-only untuk kejelasan) --}}
              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Role</label>
                  <input type="text" class="form-control" value="Purchasing" disabled>
                </div>
              </div>

              <div class="col-12 mt-2 text-end">
                <button type="submit" class="btn btn-primary">
                  Simpan Perubahan
                </button>
                <a href="{{ route('show-purchasing') }}" class="btn btn-outline-secondary">
                  Batal
                </a>
              </div>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
@endsection
