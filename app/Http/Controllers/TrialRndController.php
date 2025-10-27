<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrialRndController extends Controller
{
    /** Tabel utama */
    protected string $tbl = 'permintaan_bahan';

    /** Status lintas modul Trial R&D */
    public const STATUS_TRIAL_PENDING = 'Trial R&D';
    public const STATUS_TRIAL_OK      = 'Trial Approved';
    public const STATUS_TRIAL_NOK     = 'Trial Rejected';

    /** Status modul Purchasing (tujuan balik saat Tidak Lulus) */
    private const STATUS_PURCH = 'Purchasing Vendor';

    /** Status yang dipakai modul Sampling (supaya muncul di list Sampling) */
    private const STATUS_HALAL_OK = 'Halal Approved';

    /* ======================================================================
     | Helpers
     * ====================================================================== */

    /** Cek admin sederhana: baca kolom role di users */
    private function isAdmin(): bool
    {
        $u = auth()->user();
        if (!$u) return false;

        $role = strtolower((string) ($u->role ?? ''));
        return in_array($role, ['admin', 'administrator', 'superadmin'], true);
    }

    /** Parser angka lokal (tanpa str_contains, aman untuk PHP 7/8) */
    private function parseLocaleNumber(?string $s): float
    {
        if ($s === null) return 0.0;
        $s = trim($s);
        if ($s === '') return 0.0;

        // hapus spasi & nbsp
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

    /** Bentuk tampilan + flags penguncian untuk Blade */
    private function decorate(object $r): object
    {
        $isAdmin = $this->isAdmin();

        // Kode tampilan PB-XX(.N)
        $base    = 'PB-' . str_pad((string)($r->bahan_id ?? 0), 2, '0', STR_PAD_LEFT);
        $ulang   = (int)($r->ulang_ke ?? 0);
        $r->kode = $ulang > 0 ? "{$base}.{$ulang}" : $base;

        // Normalisasi JSON → array
        foreach (['trial_bahan','uji_formulasi','uji_stabilitas','uji_be','trial_bahan_list'] as $col) {
            $val = property_exists($r, $col) ? $r->{$col} : null;
            $r->{$col} = is_array($val) ? $val : ($val ? (json_decode($val, true) ?: []) : []);
        }
        $r->uji_be_active = (bool)($r->uji_be_active ?? false);

        // Label & badge status
        $hasil = $r->hasil_trial ?? null;
        if (($r->status ?? '') === self::STATUS_TRIAL_OK || $hasil === 'Lulus Trial Keseluruhan') {
            $r->status_label = 'Lulus Trial';
            $r->status_badge = 'badge-light-success';
        } elseif (($r->status ?? '') === self::STATUS_TRIAL_NOK || $hasil === 'Tidak Lulus Trial Keseluruhan') {
            $r->status_label = 'Tidak Lulus Trial';
            $r->status_badge = 'badge-light-danger';
        } else {
            $r->status_label = self::STATUS_TRIAL_PENDING;
            $r->status_badge = 'badge-light-primary';
        }

        // Default locking (untuk R&D)
        $r->lock_all = in_array(($r->status ?? ''), [self::STATUS_TRIAL_OK, self::STATUS_TRIAL_NOK], true);

        $anyHistory       = count($r->trial_bahan) || count($r->uji_formulasi) || count($r->uji_stabilitas) || count($r->uji_be);
        $r->lock_existing = !$r->lock_all && $anyHistory;
        $r->can_add_row   = !$r->lock_all;

        // Boleh konfirmasi jika ada salah satu status “Selesai” dan belum final
        $doneFlag = false;
        foreach (['trial_bahan','uji_formulasi','uji_stabilitas','uji_be'] as $col) {
            foreach ($r->{$col} as $row) {
                if (strtolower(($row['status'] ?? '')) === 'selesai') { $doneFlag = true; break 2; }
            }
        }
        $r->can_confirm = !$r->lock_all && ($doneFlag || $isAdmin); // Admin boleh konfirmasi kapan pun selama belum final

        // ===================== ADMIN OVERRIDE =====================
        if ($isAdmin) {
            $r->lock_all      = false;
            $r->lock_existing = false;
            $r->can_add_row   = true;
            // $r->can_confirm sudah di-set di atas (true meski belum ada "Selesai", selama belum final)
        }
        // ==========================================================

        return $r;
    }

    /** Simpan hanya kolom yang memang ada di tabel */
    private function onlyExisting(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (Schema::hasColumn($this->tbl, $k)) {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    /** Buang duplikasi item */
    private function filterNewRowsOnly(array $existing, array $incoming): array
    {
        $keep = [];
        foreach ($incoming as $row) {
            $isDup = false;
            foreach ($existing as $ex) {
                if ($row == $ex) { $isDup = true; break; }
            }
            if (!$isDup) $keep[] = $row;
        }
        return $keep;
    }

    /* ======================================================================
     | LIST
     * ====================================================================== */
    public function index()
    {
        $pending = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->where("{$this->tbl}.status", self::STATUS_TRIAL_PENDING)
            ->orderByDesc("{$this->tbl}.updated_at")
            ->get()
            ->map(fn ($r) => $this->decorate($r));

        $history = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->whereIn("{$this->tbl}.status", [self::STATUS_TRIAL_OK, self::STATUS_TRIAL_NOK])
            ->orderByDesc("{$this->tbl}.updated_at")
            ->get()
            ->map(fn ($r) => $this->decorate($r));

        return view('trial_rnd.trialrnd', compact('pending', 'history'));
    }

    /* ======================================================================
     | EDIT (Proses Trial)
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
        return view('trial_rnd.edit_trial', compact('row'));
    }

    public function update(Request $r, $id)
    {
        // Jika klik tombol TAMBAH qty, lempar langsung ke addQty()
        if ($r->input('aksi') === 'tambah') {
            return $this->addQty($r, $id);
        }

        $current = DB::table($this->tbl)->where('id', $id)->first();
  abort_if(!$current, 404);
        $cur = $this->decorate($current);

        // Blokir hanya untuk non-admin saat final
        if ($cur->lock_all && !$this->isAdmin()) {
            return back()->withErrors(['form' => 'Trial sudah final. Form terkunci.'])->withInput();
        }

        // Ambil & bersihkan setiap kelompok array
        $getClean = function (array $rows, array $fields) {
            $out = [];
            foreach ($rows as $row) {
                $row = is_array($row) ? $row : [];
                $has = false;
                foreach ($fields as $f) { if (trim($row[$f] ?? '') !== '') { $has = true; break; } }
                if ($has) {
                    $tmp = [];
                    foreach ($fields as $f) { $tmp[$f] = $row[$f] ?? null; }
                    $out[] = $tmp;
                }
            }
            return $out;
        };

        $trialBahan   = $getClean($r->input('trial_bahan', []),   ['mulai','selesai','status','keterangan']);
        $ujiFormulasi = $getClean($r->input('uji_formulasi', []), ['nama','mulai','selesai','status','keterangan']);
        $ujiStabil    = $getClean($r->input('uji_stabilitas', []),['nama','mulai','selesai','status','keterangan']);

        // repeater bahan untuk trial (opsional; hanya disimpan jika ada kolom di DB)
        $bahanList    = $getClean($r->input('trial_bahan_list', []), ['nama','jumlah','satuan','keterangan']);

        $beActive     = (bool)$r->input('uji_be_active', false);
        $ujiBE        = $beActive ? $getClean($r->input('uji_be', []), ['awal','selesai','status','keterangan']) : [];

        // Jika R&D (non-admin) dan ada riwayat → existing dikunci; admin bebas edit
        if ($cur->lock_existing && !$this->isAdmin()) {
            $trialBahan   = $this->filterNewRowsOnly($cur->trial_bahan,   $trialBahan);
            $ujiFormulasi = $this->filterNewRowsOnly($cur->uji_formulasi, $ujiFormulasi);
            $ujiStabil    = $this->filterNewRowsOnly($cur->uji_stabilitas,$ujiStabil);
            $ujiBE        = $this->filterNewRowsOnly($cur->uji_be,        $ujiBE);

            $trialBahan   = array_values(array_merge($cur->trial_bahan,   $trialBahan));
            $ujiFormulasi = array_values(array_merge($cur->uji_formulasi, $ujiFormulasi));
            $ujiStabil    = array_values(array_merge($cur->uji_stabilitas,$ujiStabil));
            $ujiBE        = array_values(array_merge($cur->uji_be,        $ujiBE));
        }

        $data = $this->onlyExisting([
            'trial_bahan'      => json_encode($trialBahan),
            'uji_formulasi'    => json_encode($ujiFormulasi),
            'uji_stabilitas'   => json_encode($ujiStabil),
            'uji_be'           => json_encode($ujiBE),
            'uji_be_active'    => $beActive ? 1 : 0,
            'trial_bahan_list' => json_encode($bahanList),
        ]);
        $data['updated_at'] = now();

        // ❗Tidak mengubah kolom `jumlah` di sini (penambahan qty hanya lewat tombol "Tambah")
        DB::table($this->tbl)->where('id', $id)->update($data);

        return redirect()->route('trial-rnd.edit', $id)->with('success', 'Proses Trial disimpan.');
    }

    /* ======================================================================
     | TAMBAH QTY dari halaman Trial (kirim balik ke Sampling)
     * ====================================================================== */
    public function addQty(Request $r, $id)
    {
        // tidak pakai 'numeric' agar format lokal tetap lolos; sanitasi manual
        $r->validate(['tambah_jumlah' => ['required']]);

        $row = DB::table($this->tbl)->where('id', $id)->first();
        abort_if(!$row, 404);

        $inc = $this->parseLocaleNumber((string) $r->tambah_jumlah);
        if ($inc <= 0) {
            return back()->withErrors(['tambah_jumlah' => 'Nilai harus lebih besar dari 0.'])->withInput();
        }

        $now     = now();
        $isAdmin = $this->isAdmin();

        // Helper buat menyisipkan catatan [FROM-TRIAL] ke keterangan
        $appendFromTrialNote = function (?string $old, float $jumlah, ?string $satuan = 'gr'): string {
            $old = trim((string)($old ?? ''));
            $qty = rtrim(rtrim(number_format($jumlah, 2, '.', ''), '0'), '.');
            $note = "[FROM-TRIAL] Tambahan qty {$qty} {$satuan}";
            return $old ? ($old . ' ' . $note) : $note;
        };

        if ($isAdmin) {
            // ADMIN: timpa pada tiket yang sama & reset siklus Sampling
            $update = [
                'jumlah'                   => ((float)($row->jumlah ?? 0)) + $inc,
                'status'                   => self::STATUS_HALAL_OK,
                'tgl_sampling_permintaan'  => $now->toDateString(),
                'est_sampling_diterima'    => null,
                'tgl_sampling_dikirim'     => null,
                'tgl_sampling_diterima'    => null,
                'updated_at'               => $now,
            ];
            if (Schema::hasColumn($this->tbl, 'keterangan')) {
                $update['keterangan'] = $appendFromTrialNote($row->keterangan ?? null, $inc, $row->satuan ?? 'gr');
            }

            DB::table($this->tbl)->where('id', $id)->update($this->onlyExisting($update));

            return redirect()->to(route('sampling-pch.index') . '#tab-pending')
                ->with('success', "Jumlah ditambah {$inc} ".($row->satuan ?? 'gr')." pada ".$this->decorate($row)->kode." (Admin).");
        }

        // R&D (non-admin): buat tiket BARU untuk Sampling
        $new = [
            'bahan_id'                 => $row->bahan_id,
            'status'                   => self::STATUS_HALAL_OK,
            'ulang_ke'                 => (int)($row->ulang_ke ?? 0),
            'jumlah'                   => $inc,
            'satuan'                   => $row->satuan ?? 'gr',
            'kategori'                 => $row->kategori ?? 'Bahan Aktif',
            'user_id'                  => Schema::hasColumn($this->tbl, 'user_id') ? (int)($row->user_id ?? auth()->id()) : null,
            'tanggal_kebutuhan'       => $row->tanggal_kebutuhan ?? $now->toDateString(),
            'tgl_sampling_permintaan'  => $now->toDateString(),
            'created_at'               => $now,
            'updated_at'               => $now,
        ];

        foreach (['vendor_id','pabrik_pembuat','negara_asal','distributor','harga','mata_uang','lead_time','no_po','tgl_po'] as $f) {
            if (Schema::hasColumn($this->tbl, $f)) $new[$f] = $row->{$f} ?? null;
        }

        foreach ([
            'est_coa_diterima','tgl_coa_diterima','hasil_uji','detail_uji',
            'tgl_sampling_dikirim','tgl_sampling_diterima',
            'trial_bahan','uji_formulasi','uji_stabilitas','uji_be','hasil_trial','tgl_selesai_trial'
        ] as $f) {
            if (!Schema::hasColumn($this->tbl, $f)) continue;
            $new[$f] = in_array($f, ['trial_bahan','uji_formulasi','uji_stabilitas','uji_be']) ? json_encode([]) : null;
        }
        if (Schema::hasColumn($this->tbl, 'uji_be_active')) $new['uji_be_active'] = 0;

        if (Schema::hasColumn($this->tbl, 'keterangan')) {
            $new['keterangan'] = "[FROM-TRIAL] Tambahan qty dari Trial R&D";
        }

        $newId = DB::table($this->tbl)->insertGetId($this->onlyExisting($new));

        return redirect()->to(route('sampling-pch.index') . '#tab-pending')
            ->with('success', "Permintaan Sampling baru dibuat (ID: {$newId}) sebanyak {$inc} ".($row->satuan ?? 'gr').".");
    }

    /* ======================================================================
     | KONFIRMASI TRIAL
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
        return view('trial_rnd.confirm_trial', compact('row'));
    }

    public function confirmUpdate(Request $r, $id)
    {
        $r->validate([
            'tgl_selesai_trial' => ['required','date'],
            'hasil_trial'       => ['required','in:Lulus Trial Keseluruhan,Tidak Lulus Trial Keseluruhan'],
        ]);

        $now = now();

        $current = DB::table($this->tbl)->where('id', $id)->first();
      abort_if(!$current, 404);
        $cur = $this->decorate($current);

        $ok = $r->hasil_trial === 'Lulus Trial Keseluruhan';

        DB::beginTransaction();
        try {
            // 1) Finalisasi record saat ini
            $update = [
                'status'     => $ok ? self::STATUS_TRIAL_OK : self::STATUS_TRIAL_NOK,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn($this->tbl, 'hasil_trial'))       $update['hasil_trial']       = $r->hasil_trial;
            if (Schema::hasColumn($this->tbl, 'tgl_selesai_trial')) $update['tgl_selesai_trial'] = $r->tgl_selesai_trial;

            DB::table($this->tbl)->where('id', $id)->update($update);

            $newId   = null;
            $newCode = null;

            if (!$ok) {
                // 2) Tidak lulus → buat tiket baru ke Purchasing
                $bahan       = DB::table('bahans')->where('id', $current->bahan_id)->first();
                $ulangKeBaru = (int)($current->ulang_ke ?? 0) + 1;

                $new = [
                    'bahan_id'   => $current->bahan_id,
                    'status'     => self::STATUS_PURCH,
                    'ulang_ke'   => $ulangKeBaru,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (Schema::hasColumn($this->tbl, 'user_id'))           $new['user_id']           = $current->user_id ?? auth()->id();
                if (Schema::hasColumn($this->tbl, 'tanggal_kebutuhan')) $new['tanggal_kebutuhan'] = $current->tanggal_kebutuhan ?? $now->toDateString();
                if (Schema::hasColumn($this->tbl, 'jumlah'))            $new['jumlah']            = $current->jumlah ?? 0;
                if (Schema::hasColumn($this->tbl, 'satuan'))            $new['satuan']            = $current->satuan ?? ($bahan?->satuan_default ?? 'gr');
                if (Schema::hasColumn($this->tbl, 'kategori'))          $new['kategori']          = $current->kategori ?? ($bahan?->kategori_default ?? 'Bahan Aktif');

                foreach (['vendor_id','pabrik_pembuat','negara_asal','distributor','harga','mata_uang','lead_time','no_po','tgl_po'] as $f) {
                    if (Schema::hasColumn($this->tbl, $f)) $new[$f] = $current->{$f} ?? null;
                }

                foreach (['trial_bahan','uji_formulasi','uji_stabilitas','uji_be','trial_bahan_list'] as $col) {
                    if (Schema::hasColumn($this->tbl, $col)) $new[$col] = json_encode([]);
                }
                if (Schema::hasColumn($this->tbl, 'uji_be_active'))     $new['uji_be_active']     = 0;
                if (Schema::hasColumn($this->tbl, 'hasil_trial'))       $new['hasil_trial']       = null;
                if (Schema::hasColumn($this->tbl, 'tgl_selesai_trial')) $new['tgl_selesai_trial'] = null;

                $newId = DB::table($this->tbl)->insertGetId($new);

                $base    = 'PB-' . str_pad((string)($current->bahan_id ?? 0), 2, '0', STR_PAD_LEFT);
                $newCode = $ulangKeBaru > 0 ? "{$base}.{$ulangKeBaru}" : $base;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['form' => 'Gagal menyimpan: '.$e->getMessage()]);
        }

        if ($ok) {
            // Bootstrap entry registrasi (opsional)
            $tblReg = 'registrasi_nie';
            $exists = DB::table($tblReg)->where('trial_id', $id)->first();
            $payload = [
                'trial_id'          => $id,
                'kode'              => $cur->kode,
                'bahan_nama'        => $cur->bahan_nama ?? null,
                'tgl_trial_selesai' => $r->tgl_selesai_trial,
                'status_dokumen'    => 'Registrasi',
                'updated_at'        => now(),
            ];
            if ($exists) {
                DB::table($tblReg)->where('id', $exists->id)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table($tblReg)->insert($payload);
            }

            return redirect()->route('registrasi.index')
                ->with('success', 'Trial Lulus. Data dipindahkan ke Registrasi.');
        }

        return redirect()
            ->to(route('purch-vendor.index', ['tab' => 'pending', 'focus' => $newId]) . '#tab-pending')
            ->with('success', "Trial: Tidak Lulus. Dibuat permintaan baru: {$newCode} (ID: {$newId}) untuk Purchasing.");
    }
}
