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

    private function isAdmin(): bool
    {
        $u = auth()->user();
        if (!$u) return false;

        if (is_callable([$u, 'hasRole'])) {
            try { return (bool) call_user_func([$u, 'hasRole'], 'Admin'); } catch (\Throwable $e) {}
        }
        if (is_callable([$u, 'getRoleNames'])) {
            try {
                $roles = call_user_func([$u, 'getRoleNames']);
                foreach ($roles as $r) if (strcasecmp((string)$r, 'Admin') === 0) return true;
            } catch (\Throwable $e) {}
        }
        foreach (['role','level','jabatan','tipe','type'] as $f) {
            if (isset($u->$f) && is_string($u->$f) && strcasecmp((string)$u->$f, 'Admin') === 0) return true;
        }
        return false;
    }

    /** Ambil kandidat kolom pertama yang eksis di $this->tblReg */
    private function pickCol(array $candidates): ?string
    {
        foreach ($candidates as $c) if (Schema::hasColumn($this->tblReg, $c)) return $c;
        return null;
    }

    /** COALESCE lintas tabel: r.cols → pb.cols */
    private function coalesceRegPb(string $alias, array $regCols, array $pbCols)
    {
        $parts = [];
        foreach ($regCols as $c) if (Schema::hasColumn($this->tblReg, $c))  $parts[] = 'r.`'.$c.'`';
        foreach ($pbCols  as $c) if (Schema::hasColumn($this->tblTrial, $c)) $parts[] = 'pb.`'.$c.'`';
        $sql = count($parts) ? ('COALESCE('.implode(',', $parts).') AS '.$alias) : ('NULL AS '.$alias);
        return DB::raw($sql);
    }

    /** Format tampilan + flags kunci untuk Blade */
    private function decorate(object $r): object
    {
        $base  = 'PB-' . str_pad((string)($r->bahan_id ?? 0), 2, '0', STR_PAD_LEFT);
        $ulang = (int)($r->ulang_ke ?? 0);
        $r->kode = $ulang > 0 ? "{$base}.{$ulang}" : $base;

        $val = property_exists($r, 'proses') ? $r->proses : null;
        $r->proses = is_array($val) ? $val : ($val ? (json_decode($val, true) ?: []) : []);

        $last       = end($r->proses) ?: null;
        $lastStatus = $last['status_dokumen'] ?? null;
        $label      = $r->status_dokumen ?? $lastStatus ?? 'Registrasi';
        $r->status_label = $label;

        $r->lock_all      = !empty($r->hasil) || ($label === 'Dokumen Lengkap');
        $r->lock_existing = (!$r->lock_all && count($r->proses) > 0);
        $r->can_add_row   = !$r->lock_all;

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
        foreach ($data as $k => $v) if (Schema::hasColumn($table, $k)) $out[$k] = $v;
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
        $statusColReg = $this->pickCol(['status_dokumen','status']);
        $ketColReg    = $this->pickCol(['keterangan','keterangan_umum']);

        // Pending (hasil NULL di registrasi_nie)
        $pending = DB::table($this->tblReg.' as r')
            ->leftJoin($this->tblTrial.' as pb', 'pb.id', '=', 'r.trial_id')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->select([
                'r.id',
                Schema::hasColumn($this->tblReg,'trial_id') ? 'r.trial_id' : DB::raw('NULL AS trial_id'),
                Schema::hasColumn($this->tblReg,'hasil')    ? 'r.hasil'    : DB::raw('NULL AS hasil'),
                Schema::hasColumn($this->tblReg,'proses')   ? 'r.proses'   : DB::raw('NULL AS proses'),
                $ketColReg ? DB::raw('r.`'.$ketColReg.'` AS keterangan') : $this->coalesceRegPb('keterangan', [], ['nie_keterangan']),
                'r.updated_at',
                // status_dokumen: r.status_dokumen → r.status → pb.nie_status_dokumen
                $this->coalesceRegPb('status_dokumen', array_filter([$statusColReg]), ['nie_status_dokumen']),
                // tanggal submit/terbit: r.* → pb.nie_*
                $this->coalesceRegPb('tgl_nie_submit', ['tgl_nie_submit','tanggal_nie_submit'], ['nie_tgl_submit']),
                $this->coalesceRegPb('tgl_nie_terbit', ['tgl_nie_terbit','tgl_terbit_nie','tanggal_terbit_nie'], ['nie_tgl_terbit']),
                DB::raw('pb.tgl_selesai_trial AS tgl_trial_selesai'),
                'pb.ulang_ke','pb.bahan_id',
                DB::raw('b.nama AS bahan_nama'),
            ])
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
            ->select([
                'r.id',
                Schema::hasColumn($this->tblReg,'trial_id') ? 'r.trial_id' : DB::raw('NULL AS trial_id'),
                Schema::hasColumn($this->tblReg,'hasil')    ? 'r.hasil'    : DB::raw('NULL AS hasil'),
                Schema::hasColumn($this->tblReg,'proses')   ? 'r.proses'   : DB::raw('NULL AS proses'),
                $ketColReg ? DB::raw('r.`'.$ketColReg.'` AS keterangan') : $this->coalesceRegPb('keterangan', [], ['nie_keterangan']),
                'r.updated_at',
                $this->coalesceRegPb('tgl_nie_submit', ['tgl_nie_submit','tanggal_nie_submit'], ['nie_tgl_submit']),
                $this->coalesceRegPb('tgl_nie_terbit', ['tgl_nie_terbit','tgl_terbit_nie','tanggal_terbit_nie'], ['nie_tgl_terbit']),
                DB::raw('pb.tgl_selesai_trial AS tgl_trial_selesai'),
                'pb.ulang_ke','pb.bahan_id',
                DB::raw('b.nama AS bahan_nama'),
            ])
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
        $cur = $this->decorate($current);

        if (!$isAdmin && $cur->lock_all) {
            return back()->withErrors(['form' => 'Dokumen sudah lengkap / final. Form terkunci.'])->withInput();
        }

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

        if (!$isAdmin && $cur->lock_existing) {
            $clean = $this->filterNewRowsOnly($cur->proses, $clean);
        }

        if ($isAdmin) {
            $newProses = count($clean) ? array_values($clean) : $cur->proses;
        } elseif ($cur->lock_existing) {
            $newProses = array_values(array_merge($cur->proses, $clean));
        } else {
            $newProses = count($clean) ? array_values($clean) : $cur->proses;
        }

        $last       = end($newProses) ?: null;
        $lastStatus = $last['status_dokumen'] ?? null;

        $firstSubmit = null;
        $lastTerbit  = null;
        foreach ($newProses as $p) {
            if (!$firstSubmit && !empty($p['tgl_submit'])) $firstSubmit = $p['tgl_submit'];
            if (!empty($p['tgl_terbit'])) $lastTerbit = $p['tgl_terbit'];
        }

        // Simpan ke registrasi_nie
        $payload = [
            'proses'     => Schema::hasColumn($this->tblReg,'proses') ? json_encode($newProses, JSON_UNESCAPED_UNICODE) : null,
            'keterangan' => $req->input('keterangan'),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn($this->tblReg,'status_dokumen')) $payload['status_dokumen'] = $lastStatus;
        elseif (Schema::hasColumn($this->tblReg,'status'))     $payload['status']         = $lastStatus;

        foreach ([
            'tgl_nie_submit'     => $firstSubmit,
            'tanggal_nie_submit' => $firstSubmit,
            'tgl_terbit_nie'     => $lastTerbit,
            'tanggal_terbit_nie' => $lastTerbit,
            'tgl_nie_terbit'     => $lastTerbit,
        ] as $col => $val) {
            if (Schema::hasColumn($this->tblReg, $col)) $payload[$col] = $val;
        }

        $payload = $this->onlyExisting($this->tblReg, $payload);
        DB::table($this->tblReg)->where('id', $id)->update($payload);

        // Mirror ke permintaan_bahan (jika ada trial_id & kolomnya ada)
        if (!empty($current->trial_id) && Schema::hasTable($this->tblTrial)) {
            $pbUpdate = [];
            if (Schema::hasColumn($this->tblTrial, 'nie_status_dokumen')) $pbUpdate['nie_status_dokumen'] = $lastStatus;
            if (Schema::hasColumn($this->tblTrial, 'nie_tgl_submit'))     $pbUpdate['nie_tgl_submit']     = $firstSubmit;
            if (Schema::hasColumn($this->tblTrial, 'nie_tgl_terbit'))     $pbUpdate['nie_tgl_terbit']     = $lastTerbit;
            if (Schema::hasColumn($this->tblTrial, 'nie_keterangan'))     $pbUpdate['nie_keterangan']     = $req->input('keterangan');
            if ($pbUpdate) {
                $pbUpdate['updated_at'] = now();
                DB::table($this->tblTrial)->where('id', $current->trial_id)->update($pbUpdate);
            }
        }

        $msg = 'Proses Registrasi disimpan.';
        if ($lastStatus === 'Dokumen Lengkap' && !$isAdmin) $msg = 'Dokumen Lengkap. Form terkunci.';

        return redirect()->route('registrasi.edit', $id)->with('ok', $msg);
    }

    /* ===================== KONFIRMASI ===================== */
    public function confirmForm($id)
    {
        $row = DB::table($this->tblReg.' as r')
            ->leftJoin($this->tblTrial.' as pb', 'pb.id', '=', 'r.trial_id')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->select('r.*','pb.ulang_ke','pb.bahan_id',DB::raw('b.nama as bahan_nama'))
            ->where('r.id', $id)
            ->first();
        abort_if(!$row, 404);

        $row = $this->decorate($row); // supaya $row->kode tersedia
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

        // Simpan ke registrasi_nie
        DB::table($this->tblReg)->where('id', $id)->update($this->onlyExisting($this->tblReg, $data));

        // Mirror ke permintaan_bahan jika ada kolomnya
        $current = DB::table($this->tblReg)->where('id', $id)->first();
        if (!empty($current->trial_id) && Schema::hasTable($this->tblTrial)) {
            $pb = [];
            if (Schema::hasColumn($this->tblTrial, 'nie_registrasi'))  $pb['nie_registrasi']  = $data['registrasi_nie'] ?? null;
            if (Schema::hasColumn($this->tblTrial, 'nie_tgl_verifikasi')) $pb['nie_tgl_verifikasi'] = $data['tgl_verifikasi'] ?? null;
            if (Schema::hasColumn($this->tblTrial, 'nie_hasil'))       $pb['nie_hasil']       = $data['hasil'] ?? null;
            if (Schema::hasColumn($this->tblTrial, 'nie_keterangan'))  $pb['nie_keterangan']  = $data['keterangan'] ?? null;
            if ($pb) {
                $pb['updated_at'] = now();
                DB::table($this->tblTrial)->where('id', $current->trial_id)->update($pb);
            }
        }

        return redirect()->route('registrasi.index')->with('ok', 'Registrasi NIE dikonfirmasi.');
    }
}
