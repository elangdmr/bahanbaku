@extends('layouts.app')

@section('content')
<section id="multiple-column-form">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Edit Akun R&amp;D</h4>
        </div>
        <div class="card-body">
          <form class="form" action="{{ url('/show-rnd/'.$rnd->id) }}" method="POST">
            @csrf @method('put')
            <div class="row">
              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Nama</label>
                  <input type="text" class="form-control" name="name" value="{{ $rnd->name }}" placeholder="Masukkan Nama Baru">
                </div>
                @error('name') <div class="text-danger mt-1">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" name="email" value="{{ $rnd->email }}" placeholder="Masukkan Email Baru">
                </div>
                @error('email') <div class="text-danger mt-1">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6 col-12">
                <div class="mb-1">
                  <label class="form-label">Password (opsional)</label>
                  <input type="password" class="form-control" name="password" placeholder="Masukkan Password Baru">
                </div>
                @error('password') <div class="text-danger mt-1">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary me-1">Submit</button>
                <button type="reset" class="btn btn-outline-secondary">Reset</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
