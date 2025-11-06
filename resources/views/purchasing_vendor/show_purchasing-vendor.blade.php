@extends('layouts.app')

@section('content')
<section class="app-user-list">
  <div class="row" id="basic-table">
    <div class="col-12">
      <div class="card">

        <div class="card-header d-flex align-items-center justify-content-between">
          <h4 class="card-title">Purchasing Vendor</h4>
          <ul class="nav nav-tabs" id="pv-tabs">
            <li class="nav-item">
              <a class="nav-link active" data-bs-toggle="tab" href="#tab-pending" id="link-pending">Perlu Diproses</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="tab" href="#tab-history" id="link-history">Riwayat Transaksi</a>
            </li>
          </ul>
        </div>

        <div class="tab-content">
          {{-- ====== Perlu Diproses ====== --}}
          <div class="tab-pane active" id="tab-pending">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Tgl Submit</th>
                    <th>ID</th>
                    <th>Nama Bahan</th>
                    <th>Kategori</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Alasan</th>
                    <th>Tgl Kebutuhan</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($pending as $r)
                  <tr data-row-id="{{ $r->id }}">
                    <td>{{ $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') : '-' }}</td>
                    <td><strong>{{ $r->kode }}</strong></td>
                    <td>{{ $r->bahan_nama ?? '-' }}</td>
                    <td>{{ $r->kategori ?? '-' }}</td>
                    <td>{{ isset($r->jumlah) ? rtrim(rtrim(number_format((float)$r->jumlah, 2, '.', ''), '0'), '.') : '-' }}</td>
                    <td>{{ $r->satuan ?? '-' }}</td>
                    <td>{{ $r->alasan ?? '-' }}</td>
                    <td>{{ !empty($r->tanggal_kebutuhan) ? \Carbon\Carbon::parse($r->tanggal_kebutuhan)->format('d/m/Y') : '-' }}</td>
                    <td class="text-end text-nowrap">
                      {{-- Riwayat: tetap kembali ke tab "pending" --}}
                      <a class="btn btn-outline-secondary btn-sm"
                         href="{{ route('riwayat.detail', [
                           'type' => 'pb',
                           'id' => $r->id,
                           'modul' => 'ALL',
                           'origin' => 'Purchasing',
                           'origin_tab' => 'pending'
                         ]) }}">
                        Riwayat
                      </a>

                      {{-- Edit --}}
                      <a href="{{ route('purch-vendor.edit', $r->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>

          {{-- ====== Riwayat ====== --}}
          <div class="tab-pane" id="tab-history">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Tgl Update</th>
                    <th>ID</th>
                    <th>Nama Bahan</th>
                    <th>Status</th>
                    <th>Tgl COA Diterima</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($history as $r)
                  <tr>
                    <td>{{ $r->updated_at ? \Carbon\Carbon::parse($r->updated_at)->format('d/m/Y') : '-' }}</td>
                    <td><strong>{{ $r->kode }}</strong></td>
                    <td>{{ $r->bahan_nama ?? '-' }}</td>
                    <td>
                      <span class="badge rounded-pill {{ $r->status_badge }}">
                        {{ $r->status_label }}
                      </span>
                    </td>
                    <td>{{ !empty($r->tgl_coa_diterima) ? \Carbon\Carbon::parse($r->tgl_coa_diterima)->format('d/m/Y') : '-' }}</td>
                    <td class="text-end text-nowrap">
                      {{-- Riwayat: tetap kembali ke tab "history" --}}
                      <a class="btn btn-outline-secondary btn-sm"
                         href="{{ route('riwayat.detail', [
                           'type' => 'pb',
                           'id' => $r->id,
                           'modul' => 'Purchasing',
                           'origin' => 'Purchasing',
                           'origin_tab' => 'history'
                         ]) }}">
                        Riwayat
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center text-muted">Belum ada riwayat</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- Auto tab & focus (inline supaya pasti jalan) --}}
<script>
(function(){
  const params = new URLSearchParams(window.location.search);
  const tab = (params.get('tab') || '').toLowerCase();
  const hash = window.location.hash;

  if (tab === 'history' || hash === '#tab-history') {
    document.getElementById('link-history')?.click();
  } else if (tab === 'pending' || hash === '#tab-pending') {
    document.getElementById('link-pending')?.click();
  }

  const focusId = params.get('focus');
  if (focusId) {
    const row = document.querySelector(`[data-row-id="${focusId}"]`);
    if (row) {
      row.classList.add('table-warning');
      row.scrollIntoView({behavior: 'smooth', block: 'center'});
      setTimeout(()=>row.classList.remove('table-warning'), 4000);
    }
  }
})();
</script>
@endsection
