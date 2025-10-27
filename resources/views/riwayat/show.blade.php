@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0">{{ $judul ?? 'Riwayat Proses' }}</h5>
      <small class="text-muted">
        <strong>Kode PB:</strong> {{ $kode }} &nbsp; | &nbsp;
        <strong>Nama Bahan:</strong> {{ $bahan }} &nbsp; | &nbsp;
        <strong>Modul:</strong> {{ $modul }}
      </small>
    </div>
    <div class="d-flex gap-1">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.history.back()">Kembali</button>
    </div>
  </div>

  <div class="card-body">
    <ul class="timeline">
      @forelse($events as $ev)
        <li class="timeline-item">
          <div class="timeline-point"></div>
          <div class="timeline-content">
            <div class="d-flex justify-content-between flex-wrap">
              <h6 class="mb-25">{{ $ev['peristiwa'] ?? '-' }}</h6>
              <small class="text-muted">{{ \Carbon\Carbon::parse($ev['tanggal'])->format('d/m/Y H:i') }}</small>
            </div>
            <div class="mb-25"><span class="badge bg-light-secondary text-dark">{{ $ev['modul'] ?? '-' }}</span></div>
            @php
              $st = (string)($ev['status'] ?? '');
              $badge = 'bg-light-secondary';
              if (preg_match('/(Lengkap|Approved|Lulus|Diterima)/i', $st)) $badge='bg-success';
              elseif (preg_match('/(Belum|Menunggu|Estimasi)/i', $st)) $badge='bg-warning text-dark';
              elseif (preg_match('/(Tidak|Rejected|Ditolak|Gagal)/i', $st)) $badge='bg-danger';
              elseif (preg_match('/(Diproses|Proses)/i', $st)) $badge='bg-info';
            @endphp
            <div class="mb-25">
              <strong>Status:</strong>
              <span class="badge {{ $badge }}">{{ $st !== '' ? $st : '-' }}</span>
            </div>
            @if(!empty($ev['keterangan']))
              <div class="text-muted small">{!! nl2br(e($ev['keterangan'])) !!}</div>
            @endif
          </div>
        </li>
      @empty
        <li class="timeline-item">
          <div class="timeline-point"></div>
          <div class="timeline-content text-muted">Belum ada event.</div>
        </li>
      @endforelse
    </ul>
    <div class="text-end text-muted small mt-2">
      Dibangkitkan: {{ \Carbon\Carbon::parse($generated ?? now())->format('d/m/Y H:i') }}
    </div>
  </div>
</div>

<style>
/* timeline sederhana */
.timeline { position:relative; padding-left:1.5rem; list-style:none; }
.timeline:before { content:''; position:absolute; left:.6rem; top:0; bottom:0; width:2px; background:#e9ecef; }
.timeline-item { position:relative; margin-bottom:1rem; }
.timeline-point { position:absolute; left:0; top:.3rem; width:.8rem; height:.8rem; border-radius:50%; background:#7367f0; }
.timeline-content { margin-left:1.5rem; padding:.5rem .75rem; background:#fff; border:1px solid #eee; border-radius:.5rem; }
</style>
@endsection
