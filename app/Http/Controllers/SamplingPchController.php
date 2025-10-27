<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SamplingPchController extends Controller
{
    /** Tabel utama lintas modul */
    protected string $tbl = 'permintaan_bahan';

    /** Status dari modul Halal yang menandakan Lulus */
    private const STATUS_HALAL_OK = 'Halal Approved';

    /** Status target setelah sampling diterima (modul berikutnya) */
    private const STATUS_TRIAL_PENDING = 'Trial R&D';

    /* ======================================================================
     | Helpers
     * ====================================================================== */

    /** Cek admin sederhana via kolom role */
    private function isAdmin(): bool
    {
        $u = auth()->user();
        if (!$u) return false;

        $role = strtolower((string) ($u->role ?? ''));
        return in_array($role, ['admin', 'administrator', 'superadmin'], true);
    }

    /** Deteksi tiket yang berasal dari Trial (tanpa ubah schema) */
    private function isFromTrial(object $r): bool
    {
        $ket = (string)($r->keterangan ?? '');
        return stripos($ket, '[FROM-TRIAL]') !== false;
    }

    /** Parser angka lokal (jika suatu saat dipakai) */
    private function parseLocaleNumber(?string $s): float
    {
        if ($s === null) return 0.0;
        $s = trim($s);
        if ($s === '') return 0.0;

        $s = str_replace(["\u{00A0}", ' '], '', $s);
        $hasComma = strpos($s, ',') !== false;
        $hasDot   = strpos($s, '.') !== false;

        if ($hasComma && $hasDot) {
            $posComma = strrpos($s, ',');
            $posDot   = strrpos($s, '.');
            $last     = max($posComma === false ? -1 : $posComma, $posDot === false ? -1 : $posDot);
            $dec      = $s[$last];
            $thou     = $dec === ',' ? '.' : ',';
            $s = str_replace($thou, '', $s);
            $s = str_replace($dec, '.', $s);
        } elseif ($hasComma) {
            $s = str_replace(',', '.', $s);
        }

        return (float) $s;
    }

    /** Hanya simpan kolom yang memang ada di tabel */
    private function onlyExisting(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (Schema::hasColumn($this->tbl, $k)) $out[$k] = $v;
        }
        return $out;
    }

    /** Dekorasi record untuk tampilan (kode, label, badge, flags) */
    private function decorate(object $r): object
    {
        // Kode tampilan PB-XX(.N)
        $base    = 'PB-' . str_pad((string)($r->bahan_id ?? 0), 2, '0', STR_PAD_LEFT);
        $ulang   = (int)($r->ulang_ke ?? 0);
        $r->kode = $ulang > 0 ? "{$base}.{$ulang}" : $base;

        // Status/Badge
        $done = !empty($r->tgl_sampling_diterima);
        $r->status_label = $done ? 'Sampling Diterima' : 'Sampling';
        $r->status_badge = $done ? 'badge-light-success' : 'badge-light-primary';

        // Flags asal Trial & penguncian
        $r->from_trial      = $this->isFromTrial($r);
        $r->lock_all        = $done && !$this->isAdmin();          // total lock kalau sudah diterima (kecuali admin)
        $r->lock_from_trial = $r->from_trial && !$this->isAdmin(); // kunci sebagian field jika asal Trial (non-admin)

        return $r;
    }

    /* ======================================================================
     | LIST
     * ====================================================================== */
    public function index()
    {
        // Perlu diproses
        $pending = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->where("{$this->tbl}.status", self::STATUS_HALAL_OK)
            ->whereNull("{$this->tbl}.tgl_sampling_diterima")
            ->orderByDesc("{$this->tbl}.updated_at")
            ->get()
            ->map(fn ($r) => $this->decorate($r));

        // Riwayat
        $history = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->whereNotNull("{$this->tbl}.tgl_sampling_diterima")
            ->orderByDesc("{$this->tbl}.tgl_sampling_diterima")
            ->get()
            ->map(fn ($r) => $this->decorate($r));

        return view('sampling_pch.sampling', compact('pending', 'history'));
    }

    /* ======================================================================
     | EDIT
     * ====================================================================== */
    public function edit($id)
    {
        $row = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->where("{$this->tbl}.id", $id)
            ->first();

       abort_if(!$row, 404);


        $row = $this->decorate($row);
        return view('sampling_pch.edit_sampling', compact('row'));
    }

    public function update(Request $r, $id)
    {
        $row = DB::table($this->tbl)->where('id', $id)->first();
    abort_if(!$row, 404);


        $isAdmin    = $this->isAdmin();
        $fromTrial  = $this->isFromTrial($row);

        // Jika sudah diterima, kunci total (non-admin)
        if (!empty($row->tgl_sampling_diterima) && !$isAdmin) {
            return back()->withErrors(['form' => 'Sampling sudah diterima. Form terkunci.'])->withInput();
        }

        // Validasi umum
        $r->validate([
            'tgl_sampling_permintaan' => ['nullable', 'date'],
            'est_sampling_diterima'   => ['nullable', 'date', 'after_or_equal:tgl_sampling_permintaan'],
            'tgl_sampling_dikirim'    => ['nullable', 'date'],
            'keterangan'              => ['nullable', 'string', 'max:255'],
        ]);

        // Non-admin & asal Trial: hanya boleh ubah tgl dikirim
        if ($fromTrial && !$isAdmin) {
            $data = $this->onlyExisting([
                'tgl_sampling_dikirim' => $r->tgl_sampling_dikirim,
            ]);
        } else {
            $data = $this->onlyExisting([
                'tgl_sampling_permintaan' => $r->tgl_sampling_permintaan,
                'est_sampling_diterima'   => $r->est_sampling_diterima,
                'tgl_sampling_dikirim'    => $r->tgl_sampling_dikirim,
                'keterangan'              => $r->keterangan,
            ]);
        }

        $data['updated_at'] = now();
        DB::table($this->tbl)->where('id', $id)->update($data);

        return redirect()->route('sampling-pch.edit', $id)->with('success', 'Data Sampling disimpan.');
    }

    /* ======================================================================
     | TAMBAH PROSES (inline, tetap di halaman edit)
     * ====================================================================== */
    public function processInline(Request $r, $id)
    {
        $row = DB::table($this->tbl)->where('id', $id)->first();
      abort_if(!$row, 404);


        // Non-admin tidak boleh bikin proses baru jika sudah diterima (admin bebas)
        if (!empty($row->tgl_sampling_diterima) && !$this->isAdmin()) {
            return back()->withErrors(['form' => 'Sampling sudah diterima. Tidak dapat menambah proses baru.']);
        }

        $r->validate([
            'new_tgl_permintaan'        => ['required', 'date'],
            'new_est_sampling_diterima' => ['nullable', 'date', 'after_or_equal:new_tgl_permintaan'],
            'new_tgl_sampling_dikirim'  => ['nullable', 'date'],
            'new_keterangan'            => ['nullable', 'string', 'max:255'],
        ]);

        // Update nilai → reset diterima agar tetap di tahap Sampling
        $data = $this->onlyExisting([
            'tgl_sampling_permintaan' => $r->new_tgl_permintaan,
            'est_sampling_diterima'   => $r->new_est_sampling_diterima,
            'tgl_sampling_dikirim'    => $r->new_tgl_sampling_dikirim,
            'keterangan'              => $r->new_keterangan,
        ]);
        $data['tgl_sampling_diterima'] = null;                   // reset
        $data['status']                = self::STATUS_HALAL_OK;  // tetap di Sampling
        $data['updated_at']            = now();

        DB::table($this->tbl)->where('id', $id)->update($data);

        return redirect()->route('sampling-pch.edit', $id)
            ->with('success', 'Proses baru ditambahkan. Tanggal & keterangan diperbarui.');
    }

    /* ======================================================================
     | KONFIRMASI SAMPLING DITERIMA
     * ====================================================================== */
    public function confirmForm($id)
    {
        $row = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->where("{$this->tbl}.id", $id)
            ->first();

     abort_if(!$row, 404);


        $row = $this->decorate($row);
        return view('sampling_pch.confirm_sampling', compact('row'));
    }

    public function confirmUpdate(Request $r, $id)
    {
        $r->validate([
            'tgl_sampling_diterima' => ['required', 'date'],
        ]);

        $row = DB::table($this->tbl)->where('id', $id)->first();
        abort_if(!$row, 404);


        $data = $this->onlyExisting([
            'tgl_sampling_diterima' => $r->tgl_sampling_diterima,
        ]);
        $data['status']     = self::STATUS_TRIAL_PENDING; // Lanjut ke Trial R&D
        $data['updated_at'] = now();

        DB::table($this->tbl)->where('id', $id)->update($data);

        return redirect()->route('sampling-pch.index')
            ->with('success', 'Sampling dikonfirmasi diterima & dialihkan ke Trial R&D.');
    }
}
