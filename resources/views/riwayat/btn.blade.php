@php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/* ===== default utk @include ===== */
$kode      = $kode      ?? null;      // ex: "PB-01"
$type      = $type      ?? 'pb';      // 'pb' | 'reg'
$refId     = $refId     ?? null;      // id pb / reg
$modul     = $modul     ?? 'ALL';     // 'ALL' | 'Purchasing' | 'Uji COA' | 'Halal' | 'Sampling PCH' | 'Trial R&D' | 'Registrasi'
$origin    = $origin    ?? null;      // asal halaman (opsional)
$originTab = $originTab ?? null;      // 'pending' | 'history' (opsional)
$title     = $title     ?? 'Riwayat';
$size      = $size      ?? 'sm';
$icon      = array_key_exists('icon', get_defined_vars()) ? (bool)$icon : true;
$target    = $target    ?? null;

// kelas tambahan dari parent via @include(['class' => '...'])
$extraClass = isset($class) ? trim($class) : '';

/* ===== deteksi origin dari route saat ini (fallback) ===== */
if (!$origin) {
  $name = (string) (Route::currentRouteName() ?? '');
  if      (Str::startsWith($name, ['purch-','purchasing'])) $origin = 'Purchasing';
  elseif  (Str::startsWith($name, 'uji-coa'))               $origin = 'Uji COA';
  elseif  (Str::startsWith($name, 'halal'))                 $origin = 'Halal';
  elseif  (Str::startsWith($name, 'sampling-pch'))          $origin = 'Sampling PCH';
  elseif  (Str::startsWith($name, ['trial-rnd','trial-rd']))$origin = 'Trial R&D';
  elseif  (Str::startsWith($name, 'registrasi'))            $origin = 'Registrasi';
  elseif  (Str::startsWith($name, 'permintaan-bahan'))      $origin = 'Permintaan';
  else                                                      $origin = 'Riwayat';
}

/* ===== tab aktif asal (pending/history) ===== */
if ($originTab === null) {
  $tabParam  = strtolower((string) request()->query('tab', ''));
  $originTab = in_array($tabParam, ['pending','history'], true) ? $tabParam : 'pending';
}

/* ===== build URL ===== */
if ($refId && Route::has('riwayat.detail')) {
  $url = route('riwayat.detail', [
    'type'       => $type,
    'id'         => $refId,
    'modul'      => $modul ?? 'ALL',
    'origin'     => $origin,
    'origin_tab' => $originTab,
  ]);
} elseif ($kode && Route::has('riwayat.show')) {
  $url = route('riwayat.show', $kode);
} else {
  $url = route('riwayat.index', ['q' => $kode]);
}

/* ===== kelas tombol final ===== */
$btnClasses = trim('btn btn-outline-secondary btn-' . e($size) . ' ' . $extraClass);
@endphp

<a href="{{ $url }}" class="{{ $btnClasses }}" @if($target) target="{{ $target }}" rel="noopener" @endif>
  @if($icon)<i data-feather="activity"></i>@endif
  <span class="ms-25">{{ $title }}</span>
</a>
