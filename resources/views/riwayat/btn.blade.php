@props([
  // identitas
  'kode'  => null,         // ex: "PB-01"
  'type'  => 'pb',         // 'pb' | 'reg' (atau 'auto' kalau kamu mau set manual di parent)
  'refId' => null,         // id pb atau id reg (sesuai type)
  'modul' => null,         // ex: 'Purchasing', 'Uji COA', 'Registrasi', dll

  // tampilan
  'title'   => 'Riwayat',
  'size'    => 'sm',
  'icon'    => true,
  'target'  => '_blank',   // buka tab baru biar user nggak “kehilangan” list
])

@php
  $hasDetail = \Illuminate\Support\Facades\Route::has('riwayat.detail');
  $hasShow   = \Illuminate\Support\Facades\Route::has('riwayat.show');
  $url = '#';

  if ($refId && $hasDetail) {
    // contoh: /riwayat/pb/{id}/detail?modul=Purchasing
    $url = route('riwayat.detail', ['type' => $type, 'id' => $refId, 'modul' => $modul]);
  } elseif ($kode && $hasShow) {
    // contoh: /riwayat/{kode}/show (timeline web)
    $url = route('riwayat.show', $kode);
  } else {
    // fallback aman
    $url = route('riwayat.index', ['q' => $kode]);
  }
@endphp

<a href="{{ $url }}"
   class="btn btn-outline-secondary btn-{{ $size }} {{ $attributes->get('class') }}"
   target="{{ $target }}" rel="noopener">
  @if($icon)<i data-feather="activity"></i>@endif
  <span class="ms-25">{{ $title }}</span>
</a>
