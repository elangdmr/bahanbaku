@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h4 class="card-title">Daftar Permintaan Bahan Baku</h4>

          {{-- gunakan route name, bukan url manual --}}
          <a href="{{ route('permintaan.create') }}" class="btn btn-primary">Tambah Permintaan</a>
        </div>

        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Bahan</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Kategori</th>
                <th>Tanggal Kebutuhan</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @php $start = $requests->firstItem() ?? 1; @endphp
              @forelse ($requests as $r)
                <tr>
                  <td>{{ $start + $loop->index }}</td>
                  <td>{{ $r->bahan_nama ?? '-' }}</td>
                  <td>{{ rtrim(rtrim(number_format($r->jumlah, 2, '.', ''), '0'), '.') }}</td>
                  <td>{{ $r->satuan }}</td>
                  <td>{{ $r->kategori }}</td>
                  <td>{{ $r->tanggal_kebutuhan ? \Carbon\Carbon::parse($r->tanggal_kebutuhan)->format('d M Y') : '-' }}</td>
                  <td>
                    <span class="badge rounded-pill badge-light-warning">{{ $r->status }}</span>
                  </td>
                  
                  <td class="text-end text-nowrap">
                    <a href="{{ route('edit-permintaan', $r->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>

                    {{-- Accept -> forward ke Purchasing --}}
                    <form action="{{ route('accept-permintaan', $r->id) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Teruskan ke Purchasing?');">
                      @csrf
                      @method('PUT')
                      <button type="submit" class="btn btn-success btn-sm">Accept</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-muted">Tidak ada data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="card-body pt-0">
          {{ $requests->links() }}
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
