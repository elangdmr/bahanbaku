<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UjiCoaController extends Controller
{
    /** Tabel lintas modul */
    protected string $tbl = 'permintaan_bahan';

    /** Status lintas modul */
    private const STATUS_COA   = 'Proses Uji COA';
    private const STATUS_PURCH = 'Purchasing Vendor'; // balik ke Purchasing bila TL
    private const STATUS_HALAL = 'Proses Halal';      // lanjut ke Halal bila Lulus
    private const STATUS_OK    = 'Approved';
    private const STATUS_NOK   = 'Rejected';

    /* --------------------------------- helpers -------------------------------- */

    private function isAdmin(): bool
    {
        $role = strtolower(Auth::user()->role ?? '');
        return in_array($role, ['admin','administrator','superadmin'], true);
    }

    /** Hias record untuk tampilan */
    private function decorate(object $r): object
    {
        // Kode PB-XX(.N)
        $base    = 'PB-' . str_pad((string)($r->bahan_id ?? 0), 2, '0', STR_PAD_LEFT);
        $ulang   = (int)($r->ulang_ke ?? 0);
        $r->kode = $ulang > 0 ? "{$base}.{$ulang}" : $base;

        // detail_uji → array
        $arr = [];
        if (property_exists($r, 'detail_uji') && !is_null($r->detail_uji)) {
            $arr = is_array($r->detail_uji) ? $r->detail_uji : (json_decode($r->detail_uji, true) ?: []);
        }
        $r->detail_uji = $arr;

        // hasil terakhir (draft)
        $last              = end($arr) ?: null;
        $r->hasil_terakhir = $last['hasil'] ?? ($r->hasil_uji ?? null);

        // flags
        $r->lock_all      = ($r->hasil_terakhir === 'Lulus') || (($r->status ?? null) === self::STATUS_OK);
        $r->lock_existing = (!$r->lock_all && count($arr) > 0);
        $r->can_add_row   = !$r->lock_all;

        if ($this->isAdmin()) {
            $r->lock_all      = false;
            $r->lock_existing = false;
            $r->can_add_row   = true;
        }

        // label & badge
        if (($r->status ?? null) === self::STATUS_OK) {
            $r->status_label = 'Lulus Uji COA';
        } elseif (($r->status ?? null) === self::STATUS_NOK) {
            $r->status_label = 'Tidak Lulus Uji COA';
        } elseif ($r->hasil_terakhir === 'Lulus') {
            $r->status_label = 'Lulus Uji COA';
        } elseif ($r->hasil_terakhir === 'Tidak Lulus') {
            $r->status_label = 'Tidak Lulus Uji COA';
        } else {
            $r->status_label = self::STATUS_COA;
        }

        $r->status_badge = match ($r->status_label) {
            'Lulus Uji COA'       => 'badge-light-success',
            'Tidak Lulus Uji COA' => 'badge-light-danger',
            default               => 'badge-light-primary',
        };

        return $r;
    }

    /* ----------------------------------- list ---------------------------------- */

    public function index()
    {
        // Pending: status COA ATAU ada kolom COA terisi tetapi belum Approved/Rejected/Proses Halal
        $pending = DB::table($this->tbl.' as p')
            ->leftJoin('bahans as b', 'b.id', '=', 'p.bahan_id')
            ->select('p.*', 'b.nama as bahan_nama')
            ->where(function ($q) {
                $q->where('p.status', self::STATUS_COA)
                  ->orWhere(function ($w) {
                      $w->whereNotNull('p.tgl_permintaan_coa')
                        ->orWhereNotNull('p.est_coa_diterima')
                        ->orWhereNotNull('p.tgl_coa_diterima')
                        ->orWhereNotNull('p.detail_uji')
                        ->orWhereNotNull('p.hasil_uji');
                  });
            })
            ->whereNotIn('p.status', [self::STATUS_OK, self::STATUS_NOK, self::STATUS_HALAL])
            ->orderByDesc('p.updated_at')
            ->get()
            ->map(fn ($r) => $this->decorate($r));

        // History (final di COA)
        $history = DB::table($this->tbl.' as p')
            ->leftJoin('bahans as b', 'b.id', '=', 'p.bahan_id')
            ->select('p.*', 'b.nama as bahan_nama')
            ->whereIn('p.status', [self::STATUS_OK, self::STATUS_NOK])
            ->orderByDesc('p.updated_at')
            ->get()
            ->map(fn ($r) => $this->decorate($r));

        return view('uji_coa.show_uji-coa', compact('pending', 'history'));
    }

    /* ----------------------------------- edit ---------------------------------- */

    public function edit($id)
    {
        $row = DB::table($this->tbl.' as p')
            ->leftJoin('bahans as b', 'b.id', '=', 'p.bahan_id')
            ->select('p.*', 'b.nama as bahan_nama')
            ->where('p.id', $id)
            ->first();
        abort_if(!$row, 404);

        $row = $this->decorate($row);
        return view('uji_coa.edit_show-coa', compact('row'));
    }

    /** Simpan draft; set status COA jika kosong */
    public function update(Request $r, $id)
    {
        $raw = DB::table($this->tbl)->where('id', $id)->first();
        abort_if(!$raw, 404);

        $isAdmin = $this->isAdmin();
        $deco    = $this->decorate(clone $raw);

        $alreadyFinal = (($raw->hasil_uji ?? null) === 'Lulus') || (($raw->status ?? null) === self::STATUS_OK);
        if ($alreadyFinal && !$isAdmin) {
            return back()->withErrors(['form' => 'Data sudah Lulus dan terkunci.'])->withInput();
        }

        // Ambil detail dari form
        $inputDetails = $r->input('details', []);
        $clean = [];
        foreach ($inputDetails as $row) {
            $row = is_array($row) ? $row : [];
            $hasVal = trim($row['pengujian'] ?? '') !== '' ||
                      trim($row['hasil'] ?? '')      !== '' ||
                      trim($row['keterangan'] ?? '') !== '' ||
                      trim($row['mulai'] ?? '')      !== '';
            if ($hasVal) {
                $clean[] = [
                    'pengujian'       => $row['pengujian'] ?? '',
                    'hasil'           => $row['hasil'] ?? '',
                    'keterangan'      => $row['keterangan'] ?? '',
                    'mulai_pengujian' => $row['mulai'] ?? null,
                ];
            }
        }

        // Non-admin tak boleh ubah baris lama jika ada riwayat
        if ($deco->lock_existing && !$isAdmin) {
            $existing   = is_array($deco->detail_uji) ? $deco->detail_uji : [];
            $newDetails = array_values(array_merge($existing, $clean));
        } else {
            $newDetails = count($clean) ? array_values($clean) : (is_array($deco->detail_uji) ? $deco->detail_uji : []);
        }

        $last      = end($newDetails) ?: null;
        $lastHasil = $last['hasil'] ?? null;

        $update = ['updated_at' => now()];

        // tanggal COA (draft / edit)
        if (Schema::hasColumn($this->tbl, 'tgl_permintaan_coa')) $update['tgl_permintaan_coa'] = $r->tgl_permintaan_coa ?: $raw->tgl_permintaan_coa;
        if (Schema::hasColumn($this->tbl, 'est_coa_diterima'))  $update['est_coa_diterima']  = $r->est_coa_diterima  ?: $raw->est_coa_diterima;
        if (Schema::hasColumn($this->tbl, 'tgl_coa_diterima'))  $update['tgl_coa_diterima']  = $r->tgl_coa_diterima  ?: $raw->tgl_coa_diterima;

        if (Schema::hasColumn($this->tbl, 'keterangan')) $update['keterangan'] = $r->keterangan;
        if (Schema::hasColumn($this->tbl, 'detail_uji')) $update['detail_uji'] = json_encode($newDetails);
        if (Schema::hasColumn($this->tbl, 'hasil_uji'))  $update['hasil_uji']  = $lastHasil; // jejak draft

        // Pastikan status masuk COA bila masih kosong / tidak sesuai
        $allowed = [self::STATUS_COA, self::STATUS_OK, self::STATUS_NOK, self::STATUS_PURCH, self::STATUS_HALAL];
        if (Schema::hasColumn($this->tbl, 'status') && !in_array($raw->status ?? '', $allowed, true)) {
            $update['status'] = self::STATUS_COA;
        } elseif (Schema::hasColumn($this->tbl, 'status') && empty($raw->status)) {
            $update['status'] = self::STATUS_COA;
        }

        DB::table($this->tbl)->where('id', $id)->update($update);

        return back()->with('success', 'Hasil uji disimpan.');
    }

    /* ------------------------------- konfirmasi ------------------------------- */

    public function confirmForm(Request $r, $id)
    {
        $row = DB::table($this->tbl.' as p')
            ->leftJoin('bahans as b', 'b.id', '=', 'p.bahan_id')
            ->select('p.*', 'b.nama as bahan_nama')
            ->where('p.id', $id)
            ->first();
        abort_if(!$row, 404);

        $row = $this->decorate($row);

        if ($r->ajax() || $r->boolean('modal')) {
            return view('uji_coa._confirm_modal_body', compact('row'));
        }
        return view('uji_coa.confirm_show-coa', compact('row'));
    }

    public function confirmUpdate(Request $r, $id)
    {
        $r->validate([
            'hasil_uji'        => ['required', 'in:Lulus,Tidak Lulus'],
            'tgl_coa_diterima' => ['nullable', 'date'],
        ]);

        $now     = now();
        $current = DB::table($this->tbl)->where('id', $id)->first();
        abort_if(!$current, 404);

        $approved = $r->hasil_uji === 'Lulus';

        DB::beginTransaction();
        try {
            $update = [
                'updated_at' => $now,
                'hasil_uji'  => $approved ? 'Lulus' : 'Tidak Lulus',
            ];

            // pastikan ada tanggal COA diterima
            if (Schema::hasColumn($this->tbl, 'tgl_coa_diterima')) {
                $update['tgl_coa_diterima'] = $r->tgl_coa_diterima ?: ($current->tgl_coa_diterima ?: $now->toDateString());
            }

            if ($approved) {
                // FINAL COA → lanjut Halal pada ROW yang sama (tanpa duplikasi)
                if (Schema::hasColumn($this->tbl, 'status'))         $update['status'] = self::STATUS_HALAL;
                if (Schema::hasColumn($this->tbl, 'detail_uji'))     { /* biarkan jejak uji */ }
                if (Schema::hasColumn($this->tbl, 'tgl_pengajuan'))  $update['tgl_pengajuan'] = $now->toDateString(); // seed Halal
                if (Schema::hasColumn($this->tbl, 'proses'))         $update['proses'] = json_encode([]);             // kosongkan jejak Halal
                if (Schema::hasColumn($this->tbl, 'keterangan'))     { /* biarkan kalau mau */ }
                if (Schema::hasColumn($this->tbl, 'hasil_halal'))    $update['hasil_halal'] = null;
                if (Schema::hasColumn($this->tbl, 'tgl_verifikasi')) $update['tgl_verifikasi'] = null;
            } else {
                // TIDAK LULUS → kembali ke Purchasing pada ROW yang sama (tanpa duplikasi)
                if (Schema::hasColumn($this->tbl, 'status')) $update['status'] = self::STATUS_PURCH;

                // reset bidang COA agar Purchasing mulai ulang
                if (Schema::hasColumn($this->tbl, 'tgl_permintaan_coa')) $update['tgl_permintaan_coa'] = null;
                if (Schema::hasColumn($this->tbl, 'est_coa_diterima'))   $update['est_coa_diterima']   = null;
                if (Schema::hasColumn($this->tbl, 'tgl_coa_diterima'))   $update['tgl_coa_diterima']   = null;
                if (Schema::hasColumn($this->tbl, 'detail_uji'))         $update['detail_uji']         = json_encode([]);
                if (Schema::hasColumn($this->tbl, 'keterangan'))         $update['keterangan']         = null;

                // naikkan ulang_ke bila kolomnya ada
                if (Schema::hasColumn($this->tbl, 'ulang_ke')) {
                    $update['ulang_ke'] = DB::raw('COALESCE(ulang_ke,0)+1');
                }
            }

            DB::table($this->tbl)->where('id', $id)->update($update);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['form' => 'Gagal menyimpan: '.$e->getMessage()]);
        }

        return redirect()
            ->route('uji-coa.index')
            ->with('success', $approved
                ? 'Hasil Uji COA: Lulus. Data diteruskan ke modul Halal (tanpa duplikasi).'
                : 'Hasil Uji COA: Tidak Lulus. Data dikembalikan ke Purchasing (tanpa duplikasi).'
            );
    }
}
