<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RegistrasiController extends Controller
{
    /** Tabel registrasi NIE */
    protected string $tblReg   = 'registrasi_nie';
    /** Tabel trial */
    protected string $tblTrial = 'permintaan_bahan';

    /* ============================================================
     | Helpers
     * ============================================================ */

    /** Bentuk tampilan + flags kunci untuk Blade */
    private function decorate(object $r): object
    {
        // kode PB-XX(.N)
        $base  = 'PB-' . str_pad((string)($r->bahan_id ?? 0), 2, '0', STR_PAD_LEFT);
        $ulang = (int)($r->ulang_ke ?? 0);
        $r->kode = $ulang > 0 ? "{$base}.{$ulang}" : $base;

        // proses JSON → array
        $val = property_exists($r, 'proses') ? $r->proses : null;
        $r->proses = is_array($val) ? $val : ($val ? (json_decode($val, true) ?: []) : []);

        // status terakhir dari proses
        $last = end($r->proses) ?: null;
        $lastStatus = $last['status_dokumen'] ?? null;

        // label status (untuk list kalau dipakai)
        $label = $r->status_dokumen ?? $lastStatus ?? 'Registrasi';
        $r->status_label = $label;

        // KUNCI:
        // - final jika hasil sudah diisi, ATAU status dokumen Lengkap (top-level atau terakhir)
        $r->lock_all      = !empty($r->hasil) || ($label === 'Dokumen Lengkap');
        // Jika belum final & sudah punya riwayat → baris existing terkunci; boleh tambah baris baru
        $r->lock_existing = (!$r->lock_all && count($r->proses) > 0);
        $r->can_add_row   = !$r->lock_all;

        return $r;
    }

    /** Simpan hanya kolom yang ada */
    private function onlyExisting(string $table, array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (Schema::hasColumn($table, $k)) $out[$k] = $v;
        }
        return $out;
    }

    /** Buang duplikasi baris proses (bandingkan nilai fieldnya) */
    private function filterNewRowsOnly(array $existing, array $incoming): array
    {
        $keep = [];
        foreach ($incoming as $row) {
            $isDup = false;
            foreach ($existing as $ex) {
                $a = [
                    'tgl_submit'     => $row['tgl_submit']     ?? null,
                    'tgl_terbit'     => $row['tgl_terbit']     ?? null,
                    'status_dokumen' => $row['status_dokumen'] ?? '',
                    'keterangan'     => $row['keterangan']     ?? '',
                ];
                $b = [
                    'tgl_submit'     => $ex['tgl_submit']     ?? null,
                    'tgl_terbit'     => $ex['tgl_terbit']     ?? null,
                    'status_dokumen' => $ex['status_dokumen'] ?? '',
                    'keterangan'     => $ex['keterangan']     ?? '',
                ];
                if ($a === $b) { $isDup = true; break; }
            }
            if (!$isDup) $keep[] = $row;
        }
        return $keep;
    }

    /* ===================== INDEX ===================== */
    public function index()
    {
        // Pending: hasil NULL
        $pending = DB::table($this->tblReg.' as r')
            ->leftJoin($this->tblTrial.' as pb', 'pb.id', '=', 'r.trial_id')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->select(
                'r.*',
                'pb.tgl_selesai_trial as tgl_trial_selesai',
                'pb.ulang_ke',
                'pb.bahan_id',
                DB::raw('b.nama as bahan_nama')
            )
            ->whereNull('r.hasil')
            ->orderByDesc('r.updated_at')
            ->get()
            ->map(function ($r) {
                $r = $this->decorate($r);
                // badge kecil untuk tabel
                $label = $r->status_label ?: 'Registrasi';
                $r->status_badge = match ($label) {
                    'Dokumen Lengkap'        => 'bg-success',
                    'Dokumen Belum Lengkap'  => 'bg-warning text-dark',
                    'Dokumen Tidak Lengkap'  => 'bg-danger',
                    'Registrasi'             => 'bg-info',
                    default                  => 'bg-secondary',
                };
                return $r;
            });

        // History: hasil NOT NULL
        $history = DB::table($this->tblReg.' as r')
            ->leftJoin($this->tblTrial.' as pb', 'pb.id', '=', 'r.trial_id')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->select(
                'r.*',
                'pb.tgl_selesai_trial as tgl_trial_selesai',
                'pb.ulang_ke',
                'pb.bahan_id',
                DB::raw('b.nama as bahan_nama')
            )
            ->whereNotNull('r.hasil')
            ->orderByDesc('r.updated_at')
            ->get()
            ->map(function ($r) {
                $r = $this->decorate($r);
                $label = $r->hasil;
                $r->status_badge = match ($label) {
                    'Disetujui'    => 'bg-success',
                    'Perlu Revisi' => 'bg-warning text-dark',
                    'Ditolak'      => 'bg-danger',
                    default        => 'bg-secondary',
                };
                return $r;
            });

        return view('registrasi.registrasi', compact('pending', 'history'));
    }

    /* ===================== EDIT ===================== */
    public function edit($id)
    {
        $row = DB::table($this->tblReg.' as r')
            ->leftJoin($this->tblTrial.' as pb', 'pb.id', '=', 'r.trial_id')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->select('r.*','pb.ulang_ke','pb.bahan_id',DB::raw('b.nama as bahan_nama'))
            ->where('r.id', $id)
            ->first();
        abort_if(!$row, 404); // <- perbaikan

        $row = $this->decorate($row);
        return view('registrasi.edit_registrasi', compact('row'));
    }

    public function update(Request $req, $id)
    {
        $current = DB::table($this->tblReg)->where('id', $id)->first();
        abort_if(!$current, 404); // <- perbaikan
        $cur = $this->decorate($current);

        if ($cur->lock_all) {
            return back()->withErrors(['form' => 'Dokumen sudah lengkap / final. Form terkunci.'])->withInput();
        }

        // Ambil & bersihkan setiap baris proses
        $rows  = $req->input('proses', []);
        $clean = [];
        foreach ($rows as $row) {
            $row = is_array($row) ? $row : [];
            $has = trim($row['tgl_submit'] ?? '')     !== '' ||
                   trim($row['tgl_terbit'] ?? '')     !== '' ||
                   trim($row['status_dokumen'] ?? '') !== '' ||
                   trim($row['keterangan'] ?? '')     !== '';
            if ($has) {
                $clean[] = [
                    'tgl_submit'     => $row['tgl_submit']     ?? null,
                    'tgl_terbit'     => $row['tgl_terbit']     ?? null,
                    'status_dokumen' => $row['status_dokumen'] ?? '',
                    'keterangan'     => $row['keterangan']     ?? '',
                ];
            }
        }

        if ($cur->lock_existing) {
            $clean = $this->filterNewRowsOnly($cur->proses, $clean);
        }

        $newProses = $cur->lock_existing
            ? array_values(array_merge($cur->proses, $clean))
            : (count($clean) ? array_values($clean) : $cur->proses);

        // status terakhir
        $last = end($newProses) ?: null;
        $lastStatus = $last['status_dokumen'] ?? null;

        // ambil TGL SUBMIT pertama & TGL TERBIT terakhir (jika ada)
        $firstSubmit = null;
        $lastTerbit  = null;
        foreach ($newProses as $p) {
            if (!$firstSubmit && !empty($p['tgl_submit'])) $firstSubmit = $p['tgl_submit'];
            if (!empty($p['tgl_terbit'])) $lastTerbit = $p['tgl_terbit'];
        }

        $payload = [
            'proses'           => json_encode($newProses),
            'keterangan'       => $req->input('keterangan'),
            'status_dokumen'   => $lastStatus,          // supaya tabel pending kelihatan
            'tgl_nie_submit'   => $firstSubmit,         // fallback untuk kolom lama
            'tgl_nie_terbit'   => $lastTerbit,          // fallback untuk kolom lama
            'updated_at'       => now(),
        ];
        $payload = $this->onlyExisting($this->tblReg, $payload);

        DB::table($this->tblReg)->where('id', $id)->update($payload);

        $msg = 'Proses Registrasi disimpan.';
        if ($lastStatus === 'Dokumen Lengkap') {
            $msg = 'Dokumen Lengkap. Form terkunci.';
        }

        return redirect()->route('registrasi.edit', $id)->with('ok', $msg);
    }

    /* ===================== KONFIRMASI ===================== */
    public function confirmForm($id)
    {
        $row = DB::table($this->tblReg)->where('id', $id)->first();
        abort_if(!$row, 404); // <- perbaikan

        return view('registrasi.confirm_registrasi', compact('row'));
    }

    public function confirmUpdate(Request $req, $id)
    {
        $data = $req->validate([
            'registrasi_nie' => 'nullable|string|max:100',
            'tgl_verifikasi' => 'nullable|date',
            'hasil'          => 'required|in:Disetujui,Perlu Revisi,Ditolak',
            'keterangan'     => 'nullable|string|max:500',
        ]);
        $data['updated_at'] = now();

        DB::table($this->tblReg)->where('id', $id)->update($data);

        return redirect()->route('registrasi.index')->with('ok', 'Registrasi NIE dikonfirmasi.');
    }
}
