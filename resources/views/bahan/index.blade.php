@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row">
    <div class="col-12">
      <div class="card">

        {{-- ===== Header ===== --}}
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h4 class="card-title mb-0">Master Bahan</h4>
            <small class="text-muted">Kelola daftar nama bahan untuk dipakai modul lain.</small>
          </div>
          <a href="{{ route('bahan.create') }}" class="btn btn-primary">+ Tambah Bahan</a>
        </div>

        {{-- ===== Body ===== --}}
        <div class="card-body">

          {{-- Notifikasi sukses --}}
          @if(session('ok'))
            <div class="alert alert-success py-1 mb-2">{{ session('ok') }}</div>
          @endif

          {{-- Filter / Search Bar --}}
          <form class="row g-1 mb-2 align-items-center" method="get" action="{{ route('bahan.index') }}">
            <div class="col-md-6">
              <input class="form-control" name="q" value="{{ $q }}" placeholder="Cari nama / satuan / kategori...">
            </div>
            <div class="col-md-2">
              <select class="form-select" name="per_page" onchange="this.form.submit()">
                @foreach([10,15,25,50,100] as $n)
                  <option value="{{ $n }}" {{ request('per_page',15)==$n?'selected':'' }}>{{ $n }}/hal</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <button class="btn btn-outline-secondary w-100">Cari</button>
            </div>
            <div class="col-md-2">
              <a href="{{ route('bahan.index') }}" class="btn btn-outline-dark w-100">Reset</a>
            </div>
          </form>

          {{-- ===== Table ===== --}}
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr class="text-muted">
                  <th width="50">#</th>
                  <th>Nama Bahan</th>
                  <th width="150">Satuan Default</th>
                  <th width="180">Kategori Default</th>
                  <th width="160">Dibuat</th>
                  <th width="160" class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $i => $b)
                  <tr>
                    <td>{{ $rows->firstItem() + $i }}</td>
                    <td>{{ $b->nama }}</td>
                    <td>{{ $b->satuan_default }}</td>
                    <td>{{ $b->kategori_default }}</td>
                    <td>{{ $b->created_at?->format('d/m/Y') }}</td>
                    <td class="text-end">
                      <div class="d-inline-flex align-items-center gap-1">
                        <a href="{{ route('bahan.edit',$b->id) }}" 
                           class="btn btn-sm btn-outline-primary px-2 d-flex align-items-center gap-1">
                          <i data-feather="edit-3" class="icon-14"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('bahan.destroy',$b->id) }}" 
                              onsubmit="return confirm('Hapus bahan ini?');">
                          @csrf @method('DELETE')
                          <button type="submit" 
                                  class="btn btn-sm btn-outline-danger px-2 d-flex align-items-center gap-1">
                            <i data-feather="trash-2" class="icon-14"></i> Hapus
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center text-muted">Belum ada data.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Pagination --}}
          <div class="mt-1">
            {{ $rows->links() }}
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- ===== Custom Style ===== --}}
<style>
  .btn-sm {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.78rem !important;
    border-radius: 0.4rem !important;
  }

  .d-inline-flex.gap-1 .btn {
    height: 28px;
  }

  .icon-14 {
    width: 14px;
    height: 14px;
  }

  table.table th {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
  }

  table.table td {
    vertical-align: middle;
  }

  /* Hover effect untuk tombol */
  .btn-outline-primary:hover {
    color: #fff !important;
  }
  .btn-outline-danger:hover {
    color: #fff !important;
  }
</style>
@endsection
