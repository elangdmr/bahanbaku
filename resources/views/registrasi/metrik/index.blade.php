@extends('layouts.app')

@section('content')
@php
  $filter     = $filter     ?? [];
  $negaraList = $negaraList ?? [];
  $rows       = $rows       ?? collect();
  $produkList = $produkList ?? collect();
  $komposisi  = $komposisi  ?? collect();
  $canEdit    = (bool)($canEdit ?? false);

  $pgView = \Illuminate\Support\Facades\View::exists('pagination::bootstrap-5')
            ? 'pagination::bootstrap-5'
            : null;
@endphp

<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        {{-- ===== Header + Filter ===== --}}
        <div class="card-header d-block d-md-flex align-items-center justify-content-between">
          <h4 class="card-title mb-1 mb-md-0">Metrik Registrasi (Matrix Perbandingan)</h4>

          <form class="w-100 w-md-auto" method="GET" action="{{ route('registrasi.metrik') }}">
            <div class="row g-1 g-md-2">
              <div class="col-12 col-md">
                <input type="text" name="q" value="{{ $filter['q'] ?? '' }}" class="form-control"
                       placeholder="Cari kode/bahan/negara/status/ket...">
              </div>
              <div class="col-6 col-md-auto">
                <select class="form-select" name="negara">
                  <option value="">— Semua Negara —</option>
                  @foreach($negaraList as $id => $nama)
                    <option value="{{ $id }}" {{ (string)($filter['negara'] ?? '')===(string)$id ? 'selected' : '' }}>
                      {{ $nama }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-6 col-md-auto">
                <select class="form-select" name="bpom">
                  <option value="">— BPOM —</option>
                  <option value="proses"  {{ ($filter['bpom'] ?? '')==='proses'  ? 'selected' : '' }}>On Process</option>
                  <option value="belum"   {{ ($filter['bpom'] ?? '')==='belum'   ? 'selected' : '' }}>Belum Ada</option>
                  <option value="selesai" {{ ($filter['bpom'] ?? '')==='selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
              </div>
              <div class="col-12 col-md-auto d-flex gap-1">
                <button class="btn btn-primary" type="submit">
                  <i data-feather="search"></i><span class="ms-50">Filter</span>
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('registrasi.metrik') }}">
                  <i data-feather="rotate-ccw"></i><span class="ms-50">Reset</span>
                </a>
              </div>
            </div>
          </form>
        </div>

        {{-- ===== Tabel Utama ===== --}}
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="min-width:110px">Kode</th>
                <th>Nama Bahan</th>
                <th>Negara</th>
                <th>Approve Vendor Lama</th>
                <th>Source Tersedia</th>
                <th>Perubahan Desain Kemasan</th>
                <th>On Process BPOM</th>
                <th>Masa Berlaku NIE</th>
                <th class="text-end" style="min-width:120px">Aksi</th>
              </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
              @php
                $masa = !empty($r->masa_berlaku_nie)
                        ? \Carbon\Carbon::parse($r->masa_berlaku_nie)->format('d/m/Y')
                        : '-';

                $bpomRaw = trim((string)($r->on_process_bpom ?? ''));
                $bpomKey = $bpomRaw === '' ? 'belum'
                         : (stripos($bpomRaw,'proses')!==false ? 'proses'
                         : (stripos($bpomRaw,'selesai')!==false ? 'selesai' : 'lain'));
                $badge = match($bpomKey) {
                  'proses'  => 'badge-light-warning',
                  'selesai' => 'badge-light-success',
                  'belum'   => 'badge-light-secondary',
                  default   => 'badge-light-primary',
                };
              @endphp
              <tr data-row-id="{{ $r->id }}">
                <td><strong>{{ $r->kode ?? '-' }}</strong></td>
                <td>{{ $r->bahan_nama ?? '-' }}</td>
                <td>{{ $r->negara_nama ?? '-' }}</td>
                <td>{{ $r->approve_vendor_lama ?? '-' }}</td>
                <td>{{ $r->source_tersedia ?? '-' }}</td>
                <td>{{ $r->perubahan_desain_kemasan ?? '-' }}</td>
                <td>
                  <span class="badge rounded-pill {{ $badge }}">
                    {{ $bpomRaw !== '' ? $bpomRaw : 'Belum Ada' }}
                  </span>
                </td>
                <td>{{ $masa }}</td>
                <td class="text-end text-nowrap">
                  <a class="btn btn-outline-secondary btn-sm me-50"
                     href="{{ route('riwayat.detail', [
                       'type'   => $r->pb_id ? 'pb' : 'reg',
                       'id'     => $r->pb_id ?: $r->id,
                       'modul'  => 'Registrasi',
                       'origin' => 'metrik-registrasi'
                     ]) }}">Riwayat</a>
                  <a href="{{ route('registrasi.metrik.edit', $r->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>

        @if(method_exists($rows,'links'))
          <div class="card-footer">
            {{ $rows->withQueryString()->links($pgView) }}
          </div>
        @endif
      </div>
    </div>
  </div>
</section>

{{-- === MASTER PRODUK & KOMPOSISI === --}}
<div class="card mt-2" id="panel-produk">
  <div class="card-header d-block d-md-flex align-items-center justify-content-between">
    <div>
      <h4 class="card-title mb-1 mb-md-0">Master Produk & Komposisi</h4>
      <small class="text-muted">Menampilkan bahan yang SUDAH TERBIT dan otomatis tersinkron ke produk</small>
    </div>
    <div class="mt-1 mt-md-0 d-flex gap-1">
      @if(Route::has('registrasi.metrik.export'))
        <a class="btn btn-outline-success btn-sm" href="{{ route('registrasi.metrik.export') }}">
          <i data-feather="download"></i><span class="ms-50">Export Excel/Sheets</span>
        </a>
      @endif
      @if(Route::has('registrasi.metrik.produk_bahan.confirm'))
        <form method="POST" action="{{ route('registrasi.metrik.produk_bahan.confirm') }}">
          @csrf
          <button class="btn btn-success btn-sm" type="submit">
            <i data-feather="check-circle"></i><span class="ms-50">Konfirmasi Perubahan</span>
          </button>
        </form>
      @endif
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahProduk">
        <i data-feather="plus"></i><span class="ms-50">Tambah Produk</span>
      </button>
      @if(Route::has('produk.index'))
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('produk.index') }}">Kelola Semua</a>
      @endif
    </div>
  </div>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th style="min-width:120px">Kode</th>
          <th>Nama Produk</th>
          <th>Brand</th>
          <th class="text-center" style="min-width:120px">Jumlah Bahan</th>
          <th>Komposisi (Urut)</th>
        </tr>
      </thead>
      <tbody>
      @forelse(($produkList ?? []) as $p)
        @php
          $list = $komposisi instanceof \Illuminate\Support\Collection
                  ? $komposisi->get($p->id, collect())
                  : (is_array($komposisi) && isset($komposisi[$p->id]) ? collect($komposisi[$p->id]) : collect());

          $chips = $list->map(function ($c) use ($canEdit) {
            $urutan     = data_get($c, 'urutan');
            $bahanNama  = data_get($c, 'bahan_nama');
            $peran      = data_get($c, 'peran');
            $qtyRaw     = data_get($c, 'qty');
            $satuan     = data_get($c, 'satuan');
            $linkId     = data_get($c, 'link_id');

            $qty = ($qtyRaw !== null && $qtyRaw !== '')
                    ? number_format((float)$qtyRaw, 3).' '.(string)($satuan ?? '')
                    : '';

            $del = '';
            if ($canEdit && !empty($linkId) && Route::has('registrasi.metrik.produk_bahan.destroy')) {
              $del = "<form method='POST' action='".route('registrasi.metrik.produk_bahan.destroy',$linkId)."' class='d-inline ms-25'>"
                   . csrf_field().method_field('DELETE')
                   . "<button type='submit' class='btn btn-sm btn-link p-0 text-danger' "
                   . "onclick=\"return confirm('Hapus bahan ini dari komposisi?')\" "
                   . "title='Hapus' aria-label='Hapus'>&times;</button></form>";
            }

            return "<span class='badge rounded-pill bg-light-secondary me-50 mb-50'>"
                   . e($urutan) . ". " . e($bahanNama)
                   . ($peran ? " <small class='text-muted'>(".e($peran).")</small>" : '')
                   . ($qty ? " <small class='text-muted'>— ".e($qty)."</small>" : '')
                   . "</span>".$del;
          })->implode(' ');
        @endphp
        <tr>
          <td><strong>{{ $p->kode }}</strong></td>
          <td>{{ $p->nama }}</td>
          <td>{{ $p->brand ?? '-' }}</td>
          <td class="text-center">
            <span class="badge rounded-pill bg-light-primary text-primary">{{ (int)($p->jml_bahan ?? 0) }}</span>
          </td>
          <td>{!! $chips !!}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-center text-muted">Belum ada produk</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @if($produkList instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 pt-0">
      <div class="small text-muted mb-0">
        Menampilkan <strong>{{ $produkList->firstItem() }}</strong>–<strong>{{ $produkList->lastItem() }}</strong>
        dari <strong>{{ $produkList->total() }}</strong> data •
        Halaman <strong>{{ $produkList->currentPage() }}</strong>/<strong>{{ $produkList->lastPage() }}</strong>
      </div>
      <div class="mb-0">
        {!! $produkList->appends(request()->query())->onEachSide(1)->links($pgView) !!}
      </div>
    </div>
  @endif
</div>

<div class="modal fade" id="modalTambahProduk" tabindex="-1" aria-labelledby="modalTambahProdukLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('registrasi.metrik.produk.store') }}">
        @csrf
        <input type="hidden" name="redirect_to_metrik" value="1">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTambahProdukLabel">Tambah Produk</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-1">
            <label class="form-label">Kode</label>
            <input type="text" name="kode" class="form-control" placeholder="(otomatis)">
            <small class="text-muted">Kosongkan untuk auto-generate (PRD-xxx).</small>
          </div>
          <div class="mb-1">
            <label class="form-label">Nama Produk</label>
            <input type="text" name="nama" class="form-control" required placeholder="cth. Paracetamol 500 mg Tablet">
          </div>
          <div>
            <label class="form-label">Brand</label>
            <input type="text" name="brand" class="form-control" placeholder="cth. SAMCO">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">
            <i data-feather="save"></i><span class="ms-50">Simpan</span>
          </button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  #panel-produk .card-footer { padding-top: .5rem; padding-bottom: .75rem; }
  #panel-produk .pagination   { margin-bottom: 0; }
</style>
@endsection
