<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchasingVendorController extends Controller
{
    /* =================== Konfigurasi =================== */
    protected string $tbl = 'permintaan_bahan';

    // Samakan status lintas modul
    private const STATUS_PURCH   = 'Purchasing Vendor';
    private const STATUS_COA     = 'Proses Uji COA';
    private const STATUS_APPROVE = 'Approved';
    private const STATUS_REJECT  = 'Rejected';

    /** Field opsional terkait vendor/COA (di-hydrate agar aman dipakai Blade) */
    private array $vendorFields = [
        'pabrik_pembuat',
        'negara_asal',
        'distributor',        // legacy kolom string
        'distributor_list',   // kolom json baru (opsional)
        'tgl_permintaan_coa',
        'est_coa_diterima',
        'tgl_coa_diterima',
    ];

    /* =================== LIST =================== */
    public function index()
    {
        $pending = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->whereRaw("TRIM({$this->tbl}.status) = ?", [self::STATUS_PURCH])
            ->orderByDesc("{$this->tbl}.created_at")
            ->get()
            ->map(fn ($r) => $this->decorate($this->hydrateVendorFields($r)));

        $history = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->whereIn("{$this->tbl}.status", [self::STATUS_COA, self::STATUS_APPROVE, self::STATUS_REJECT])
            ->orderByDesc("{$this->tbl}.updated_at")
            ->get()
            ->map(fn ($r) => $this->decorate($this->hydrateVendorFields($r)));

        return view('purchasing_vendor.show_purchasing-vendor', compact('pending', 'history'));
    }

    /* =================== EDIT vendor/COA =================== */
    public function edit($id)
    {
        $row = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->where("{$this->tbl}.id", $id)
            ->first();

        abort_if(!$row, 404);

        $row = $this->decorate($this->hydrateVendorFields($row));

        // Siapkan array distributor untuk repeater:
        $dists = [];
        if (Schema::hasColumn($this->tbl, 'distributor_list') && !empty($row->distributor_list)) {
            $dists = json_decode($row->distributor_list, true) ?: [];
        } elseif (!empty($row->distributor)) {
            // fallback pecah koma -> array
            $dists = array_values(array_filter(array_map('trim', explode(',', (string)$row->distributor))));
        }

        return view('purchasing_vendor.edit_purchasing-vendor', compact('row', 'dists'));
    }

    public function update(Request $r, $id)
    {
        $r->validate([
            'pabrik_pembuat'     => ['nullable', 'string', 'max:255'],
            'negara_asal'        => ['nullable', 'string', 'max:255'],

            // repeater: distributor[]
            'distributor'        => ['array'],
            'distributor.*'      => ['nullable', 'string', 'max:255'],

            'tgl_permintaan_coa' => ['nullable', 'date'],
            'est_coa_diterima'   => ['nullable', 'date', 'after_or_equal:tgl_permintaan_coa'],
        ]);

        // Bersihkan list distributor (hapus kosong, trim, reindex)
        $distArr = array_values(array_filter(array_map(
            fn($v) => trim((string)$v),
            $r->input('distributor', [])
        )));

        // Payload umum
        $payload = [
            'pabrik_pembuat'     => $r->pabrik_pembuat,
            'negara_asal'        => $r->negara_asal,
            'tgl_permintaan_coa' => $r->tgl_permintaan_coa,
            'est_coa_diterima'   => $r->est_coa_diterima,
            'updated_at'         => now(),
        ];

        // Simpan distributor: utamakan kolom JSON bila ada
        if (Schema::hasColumn($this->tbl, 'distributor_list')) {
            $payload['distributor_list'] = json_encode($distArr, JSON_UNESCAPED_UNICODE);
        }
        // Tetap isi kolom lama "distributor" (string) agar kompatibel dengan list lama
        if (Schema::hasColumn($this->tbl, 'distributor')) {
            $payload['distributor'] = implode(', ', $distArr);
        }

        // Filter hanya kolom yang benar ada
        $payload = $this->onlyExistingColumns($payload);

        DB::table($this->tbl)->where('id', $id)->update($payload);

        return redirect()->route('purch-vendor.index')->with('success', 'Data vendor/COA tersimpan.');
    }

    /* =================== ACCEPT (lanjut ke COA) =================== */
    public function acceptForm($id)
    {
        $row = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->where("{$this->tbl}.id", $id)
            ->first();

        abort_if(!$row, 404);

        $row = $this->decorate($this->hydrateVendorFields($row));
        return view('purchasing_vendor.accept_purchasing-vendor', compact('row'));
    }

   public function acceptUpdate(Request $r, $id)
{
    $r->validate([
        'tgl_coa_diterima'   => ['required', 'date'],

        // field vendor opsional (ikut tersimpan kalau diisi di form)
        'pabrik_pembuat'     => ['nullable', 'string', 'max:255'],
        'negara_asal'        => ['nullable', 'string', 'max:255'],
        'tgl_permintaan_coa' => ['nullable', 'date'],
        'est_coa_diterima'   => ['nullable', 'date'],

        // repeater distributor[]
        'distributor'        => ['array'],
        'distributor.*'      => ['nullable', 'string', 'max:255'],
    ]);

$current = DB::table($this->tbl)->where('id', $id)->first();
abort_if(!$current, 404);

    // Normalisasi distributor dari form (kalau ada)
    $list = collect($r->input('distributor', []))
        ->map(fn ($v) => trim((string) $v))
        ->filter()
        ->values()
        ->all();

    $payload = [
        'pabrik_pembuat'     => $r->pabrik_pembuat,
        'negara_asal'        => $r->negara_asal,
        'tgl_permintaan_coa' => $r->tgl_permintaan_coa,
        'est_coa_diterima'   => $r->est_coa_diterima,
        'tgl_coa_diterima'   => $r->tgl_coa_diterima,
        'status'             => self::STATUS_COA,   // lanjut ke modul Uji COA
        'updated_at'         => now(),
    ];

    // Hanya ubah data distributor bila user memang kirim isian distributor[]
    if (count($list) > 0) {
        if (Schema::hasColumn($this->tbl, 'distributor_list')) {
            $payload['distributor_list'] = json_encode($list);
        }
        if (Schema::hasColumn($this->tbl, 'distributor')) {
            // simpan SEMUA distributor dalam kolom string (dipisah koma) untuk kompatibilitas
            $payload['distributor'] = implode(', ', $list);
        }
    }

    $payload = $this->onlyExistingColumns($payload);

    DB::table($this->tbl)->where('id', $id)->update($payload);

    return redirect()
        ->route('uji-coa.index')
        ->with('success', 'Accept berhasil. Data vendor tersimpan & diarahkan ke Hasil Uji COA.');
}

    private function decorate(object $r): object
    {
        $base    = 'PB-' . str_pad((string)($r->bahan_id ?? 0), 2, '0', STR_PAD_LEFT);
        $ulangKe = (int)($r->ulang_ke ?? 0);
        $r->kode = $ulangKe > 0 ? "{$base}.{$ulangKe}" : $base;

        $r->status_label = $r->status ?? '-';
        $r->status_badge = match ($r->status) {
            self::STATUS_APPROVE => 'badge-light-success',
            self::STATUS_REJECT  => 'badge-light-danger',
            self::STATUS_COA     => 'badge-light-primary',
            default              => 'badge-light-secondary',
        };

        return $r;
    }

    /** Pastikan properti vendor ada di objek (default null) agar aman di Blade */
    private function hydrateVendorFields(object $row): object
    {
        foreach ($this->vendorFields as $f) {
            if (!property_exists($row, $f)) {
                $row->{$f} = null;
            }
        }
        return $row;
    }

    /** Simpan hanya kolom yang benar-benar ada di tabel */
    private function onlyExistingColumns(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (Schema::hasColumn($this->tbl, $k)) {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
