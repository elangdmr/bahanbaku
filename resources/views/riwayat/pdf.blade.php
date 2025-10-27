<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $judul ?? 'Riwayat Proses' }}</title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#333; }
    h1 { font-size:18px; margin:0 0 8px 0; }
    .meta { margin-bottom:10px; }
    .meta div { margin:2px 0; }
    table { width:100%; border-collapse:collapse; }
    th, td { border:1px solid #ddd; padding:6px 8px; }
    th { background:#f6f6f6; text-align:left; }
    .small { color:#777; font-size:11px; }
    /* toolbar */
    .toolbar { margin-bottom:10px; }
    .btn-back {
      display:inline-block; padding:6px 10px; border:1px solid #ddd; border-radius:6px;
      text-decoration:none; color:#333; background:#fafafa;
      font-size:12px;
    }
    .btn-back:hover { background:#f0f0f0; }
  </style>
</head>
<body>

  {{-- === Tombol Kembali (jika tersedia) === --}}
  @if(!empty($back_url))
    <div class="toolbar">
      <a href="{{ $back_url }}" class="btn-back">← Kembali ke {{ $back_label ?? 'menu' }}</a>
    </div>
  @endif

  <h1>{{ $judul ?? 'Riwayat Proses' }}</h1>
  <div class="meta">
    <div><strong>Kode PB:</strong> {{ $kode }}</div>
    <div><strong>Nama Bahan:</strong> {{ $bahan }}</div>
    <div><strong>Modul:</strong> {{ $modul }}</div>
    <div class="small">Dibuat: {{ \Carbon\Carbon::parse($generated ?? now())->format('d/m/Y H:i') }}</div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:90px;">Tanggal</th>
        <th style="width:120px;">Modul</th>
        <th>Peristiwa</th>
        <th style="width:160px;">Status</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
      @forelse($events as $e)
        <tr>
          <td>{{ \Carbon\Carbon::parse($e['tanggal'])->format('d/m/Y') }}</td>
          <td>{{ $e['modul'] ?? '-' }}</td>
          <td>{{ $e['peristiwa'] ?? '-' }}</td>
          <td>{{ $e['status'] ?? '-' }}</td>
          <td>{{ $e['keterangan'] ?? '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="small">Tidak ada data.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
