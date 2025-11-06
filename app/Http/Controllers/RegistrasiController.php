<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RegistrasiController extends Controller
{
    protected string $tblReg   = 'registrasi_nie';
    protected string $tblTrial = 'permintaan_bahan';

    /* ====================== Helpers ====================== */

    /** Deteksi apakah user saat ini Admin (toleran ke berbagai skema kolom) */
   /** Deteksi apakah user saat ini Admin (aman untuk Intelephense) */
private function isAdmin(): bool
{
    $u = auth()->user();
    if (!$u) return false;

    // Spatie/permission atau trait sejenis
    if (is_callable([$u, 'hasRole'])) {
        try { return (bool) call_user_func([$u, 'hasRole'], 'Admin'); } catch (\Throwable $e) {}
    }
    if (is_callable([$u, 'getRoleNames'])) {
        try {
            $roles = call_user_func([$u, 'getRoleNames']);
            foreach ($roles as $r) {
                if (strcasecmp((string)$r, 'Admin') === 0) return true;
            }
        } catch (\Throwable $e) {}
    }

    // Fallback: cek beberapa kemungkinan nama kolom role
    foreach (['role','level','jabatan','tipe','type'] as $f) {
        if (isset($u->$f) && is_string($u->$f) && strcasecmp($u->$f, 'Admin') === 0) {
            return true;
        }
    }

    return false;
}

    /** Format tampilan + flags kunci untuk Blade */
    private function decorate(object $r): object
    {
        // Kode PB-XX(.N)
        $base  = 'PB-' . str_pad((string)($r->bahan_id ?? 0), 2, '0', STR_PAD_LEFT);
        $ulang = (int)($r->ulang_ke ?? 0);
        $r->kode = $ulang > 0 ? "{$base}.{$ulang}" : $base;

        // proses JSON → array
        $val = property_exists($r, 'proses') ? $r->proses : null;
        $r->proses = is_array($val) ? $val : ($val ? (json_decode($val, true) ?: []) : []);

        // status label
        $last       = end($r->proses) ?: null;
        $lastStatus = $last['status_dokumen'] ?? null;
        $label      = $r->status_dokumen ?? $lastStatus ?? 'Registrasi';
        $r->status_label = $label;

        // default lock rules
        $r->lock_all      = !empty($r->hasil) || ($label === 'Dokumen Lengkap');
        $r->lock_existing = (!$r->lock_all && count($r->proses) > 0);
        $r->can_add_row   = !$r->lock_all;

        // ==== Admin override: semuanya boleh edit ====
        if ($this->isAdmin()) {
            $r->lock_all      = false;
            $r->lock_existing = false;
            $r->can_add_row   = true;
        }

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

    /** Buang duplikasi baris proses (untuk R&D saat baris lama terkunci) */
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
        // Pending (hasil NULL)
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

        // History (hasil NOT NULL)
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
        abort_if(!$row, 404);

        $row = $this->decorate($row);
        $isAdmin = $this->isAdmin();

        return view('registrasi.edit_registrasi', compact('row','isAdmin'));
    }

    /* ===================== UPDATE ===================== */
    public function update(Request $req, $id)
    {
        $isAdmin = $this->isAdmin();

        $current = DB::table($this->tblReg)->where('id', $id)->first();
        abort_if(!$current, 404);
        $cur = $this->decorate($current); // sudah mengandung flag admin

        // R&D diblokir saat final; Admin boleh lanjut
        if (!$isAdmin && $cur->lock_all) {
            return back()->withErrors(['form' => 'Dokumen sudah lengkap / final. Form terkunci.'])->withInput();
        }

        // Ambil & bersihkan input baris proses
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

        // Jika R&D dan baris existing terkunci → hanya boleh tambah yang benar2 baru
        if (!$isAdmin && $cur->lock_existing) {
            $clean = $this->filterNewRowsOnly($cur->proses, $clean);
        }

        // Susun proses baru
        if ($isAdmin) {
            // Admin boleh overwrite penuh jika ada input; jika tidak, jaga yang lama
            $newProses = count($clean) ? array_values($clean) : $cur->proses;
        } elseif ($cur->lock_existing) {
            $newProses = array_values(array_merge($cur->proses, $clean));
        } else {
            $newProses = count($clean) ? array_values($clean) : $cur->proses;
        }

        // status terakhir
        $last       = end($newProses) ?: null;
        $lastStatus = $last['status_dokumen'] ?? null;

        // TGL SUBMIT pertama & TGL TERBIT terakhir
        $firstSubmit = null;
        $lastTerbit  = null;
        foreach ($newProses as $p) {
            if (!$firstSubmit && !empty($p['tgl_submit'])) $firstSubmit = $p['tgl_submit'];
            if (!empty($p['tgl_terbit'])) $lastTerbit = $p['tgl_terbit'];
        }

        $payload = [
            'proses'           => json_encode($newProses),
            'keterangan'       => $req->input('keterangan'),
            'status_dokumen'   => $lastStatus,
            'tgl_nie_submit'   => $firstSubmit,
            'tgl_nie_terbit'   => $lastTerbit,
            'updated_at'       => now(),
        ];
        $payload = $this->onlyExisting($this->tblReg, $payload);

        DB::table($this->tblReg)->where('id', $id)->update($payload);

        $msg = 'Proses Registrasi disimpan.';
        if ($lastStatus === 'Dokumen Lengkap' && !$isAdmin) {
            // kalau R&D yang mem-finalkan, beri info terkunci;
            // admin tetap bisa lanjut edit setelah final
            $msg = 'Dokumen Lengkap. Form terkunci.';
        }

        return redirect()->route('registrasi.edit', $id)->with('ok', $msg);
    }

    /* ===================== KONFIRMASI ===================== */
    public function confirmForm($id)
    {
        $row = DB::table($this->tblReg)->where('id', $id)->first();
        abort_if(!$row, 404);

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
