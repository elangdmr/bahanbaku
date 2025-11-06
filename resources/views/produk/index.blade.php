@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h4 class="card-title mb-0">Master Produk</h4>
    <a href="{{ route('produk.create') }}" class="btn btn-primary btn-sm">
      <i data-feather="plus"></i><span class="ms-50">Tambah Produk</span>
    </a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th style="width:120px">Kode</th>
            <th>Nama Produk</th>
            <th style="width:160px">Brand</th>
            <th style="width:140px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr>
              <td><strong>{{ $r->kode }}</strong></td>
              <td>{{ $r->nama }}</td>
              <td>{{ $r->brand ?: '-' }}</td>
              <td>
                <a href="{{ route('produk.edit',$r->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                <form action="{{ route('produk.destroy',$r->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Hapus produk ini?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted">Belum ada data</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">
    {{ $rows->links() }}
  </div>
</div>
@endsection
