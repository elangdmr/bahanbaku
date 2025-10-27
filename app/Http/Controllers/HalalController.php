<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HalalController extends Controller
{
    protected string $tbl = 'permintaan_bahan';

    // Status Halal
    private const STATUS_HALAL_PENDING = 'Proses Halal';
    private const STATUS_HALAL_OK      = 'Halal Approved';
    private const STATUS_HALAL_NOK     = 'Halal Rejected';

    // Status modul Purchasing (lempar balik saat tidak lulus)
    private const STATUS_PURCH = 'Purchasing Vendor';

    public function __construct()
    {
        // Batasi modul Halal untuk Admin & PPIC saja
        $this->middleware(function ($request, $next) {
            $role = strtolower((string) (Auth::user()->role ?? ''));
            if (!in_array($role, ['admin','ppic','administrator','superadmin'], true)) {
                abort(403, 'Anda tidak punya akses ke modul Halal PPIC.');
            }
            return $next($request);
        });
    }

    /* ======================= Helpers ======================= */

    /** Admin checker (bebaskan semua kuncian untuk Admin) */
    private function isAdmin(): bool
    {
        $role = strtolower((string) (Auth::user()->role ?? ''));
        return in_array($role, ['admin','administrator','superadmin'], true);
    }

    /** Simpan hanya kolom yang memang ada */
    private function onlyExisting(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (Schema::hasColumn($this->tbl, $k)) $out[$k] = $v;
        }
        return $out;
    }

    /** Bentuk tampilan + flags penguncian untuk Blade (role-aware) */
    private function decorate(object $r): object
    {
        // Kode PB-XX(.N)
        $base    = 'PB-' . str_pad((string)($r->bahan_id ?? 0), 2, '0', STR_PAD_LEFT);
        $ulang   = (int)($r->ulang_ke ?? 0);
        $r->kode = $ulang > 0 ? "{$base}.{$ulang}" : $base;

        // Proses -> array
        $arr = [];
        if (property_exists($r, 'proses') && !is_null($r->proses)) {
            $arr = is_array($r->proses) ? $r->proses : (json_decode($r->proses, true) ?: []);
        }
        $r->proses = $arr;

        // Status terakhir baris proses (kalau kamu pakai)
        $last = end($arr) ?: null;
        $r->last_status = $last['status_dokumen'] ?? null;

        // Label/badge untuk list
        if (($r->status ?? '') === self::STATUS_HALAL_OK) {
            $r->status_label = 'Lulus Halal';
            $r->status_badge = 'badge-light-success';
        } elseif (($r->status ?? '') === self::STATUS_HALAL_NOK) {
            $r->status_label = 'Tidak Lulus Halal';
            $r->status_badge = 'badge-light-danger';
        } else {
            $map = [
                'Dokumen Lengkap'       => ['label' => 'Dokumen Lengkap',       'badge' => 'badge-light-success'],
                'Dokumen Belum Lengkap' => ['label' => 'Dokumen Belum Lengkap', 'badge' => 'badge-light-warning'],
                'Dokumen Tidak Lengkap' => ['label' => 'Dokumen Tidak Lengkap', 'badge' => 'badge-light-danger'],
            ];
            if (isset($map[$r->last_status])) {
                $r->status_label = $map[$r->last_status]['label'];
                $r->status_badge = $map[$r->last_status]['badge'];
            } else {
                $r->status_label = self::STATUS_HALAL_PENDING;
                $r->status_badge = 'badge-light-primary';
            }
        }

        // ===== KUNCIAN (ROLE-AWARE) =====
        $r->is_admin = $this->isAdmin();

        if ($r->is_admin) {
            // Admin bebas edit semuanya
            $r->lock_all      = false;
            $r->lock_existing = false;
            $r->can_add_row   = true;
            $r->can_confirm   = ($r->last_status === 'Dokumen Lengkap');
        } else {
            // PPIC: kunci global hanya jika status sudah "Dokumen Lengkap".
            $r->lock_all      = ($r->last_status === 'Dokumen Lengkap');
            $r->lock_existing = false; // kunci per-field dilakukan saat simpan
            $r->can_add_row   = !$r->lock_all;
            $r->can_confirm   = ($r->last_status === 'Dokumen Lengkap');
        }

        // Distributor list -> array untuk badge (opsional)
        $list = [];
        if (!empty($r->distributor_list)) {
            $dec = json_decode($r->distributor_list, true);
            if (is_array($dec)) {
                $list = collect($dec)->map(fn($v)=>trim((string)$v))->filter()->values()->all();
            }
        }
        if (empty($list) && !empty($r->distributor)) {
            $list = collect(explode(',', (string)$r->distributor))
                    ->map(fn($v)=>trim($v))->filter()->values()->all();
        }
        $r->distributor_list = $list;

        return $r;
    }

    /* ======================= LIST ======================= */

    public function index()
    {
        $pending = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->where("{$this->tbl}.status", self::STATUS_HALAL_PENDING)
            ->orderByDesc("{$this->tbl}.updated_at")
            ->get()
            ->map(fn ($r) => $this->decorate($r));

        $history = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->whereIn("{$this->tbl}.status", [self::STATUS_HALAL_OK, self::STATUS_HALAL_NOK])
            ->orderByDesc("{$this->tbl}.updated_at")
            ->get()
            ->map(fn ($r) => $this->decorate($r));

        return view('halal.halal', compact('pending', 'history'));
    }

    /* ======================= EDIT ======================= */

    public function edit($id)
    {
        $row = DB::table($this->tbl.' as p')
            ->leftJoin('bahans as b', 'b.id', '=', 'p.bahan_id')
            ->select('p.*', 'b.nama as bahan_nama')
            ->where('p.id', $id)
            ->first();

        abort_if(!$row, 404);

        $row = $this->decorate($row);
        return view('halal.edit_halal', compact('row'));
    }

    public function update(Request $r, $id)
    {
        $current = DB::table($this->tbl)->where('id', $id)->first();
        abort_if(!$current, 404);
        $cur = $this->decorate($current);

        // PPIC diblok kalau sudah lengkap; Admin boleh lanjut
        if ($cur->lock_all && !$cur->is_admin) {
            return back()->withErrors(['form' => 'Dokumen sudah lengkap. Edit dinonaktifkan.'])->withInput();
        }

        // Ambil semua baris dari form
        $rowsIn = $r->input('proses', []);
        $clean  = [];
        foreach ($rowsIn as $row) {
            $row = is_array($row) ? $row : [];
            $has = trim($row['tgl_pengajuan'] ?? '')  !== '' ||
                   trim($row['tgl_terima'] ?? '')     !== '' ||
                   trim($row['status_dokumen'] ?? '') !== '' ||
                   trim($row['keterangan'] ?? '')     !== '';
            if ($has) {
                $clean[] = [
                    'tgl_pengajuan'  => $row['tgl_pengajuan'] ?? null,
                    'tgl_terima'     => $row['tgl_terima'] ?? null,
                    'status_dokumen' => $row['status_dokumen'] ?? '',
                    'keterangan'     => $row['keterangan'] ?? '',
                ];
            }
        }

        $existing = $cur->proses;

        // ===== KUNCI PER-FIELD SAAT SIMPAN (server-side) =====
        if (!$cur->is_admin) {
            $max = max(count($existing), count($clean));
            for ($i = 0; $i < $max; $i++) {
                $e = $existing[$i] ?? null;
                if (!isset($clean[$i])) continue; // baris yg tidak dikirim diabaikan

                if ($e && !empty($e['tgl_terima'])) {
                    $clean[$i]['tgl_pengajuan'] = $e['tgl_pengajuan'] ?? $clean[$i]['tgl_pengajuan'];
                    $clean[$i]['tgl_terima']    = $e['tgl_terima'];
                } elseif ($e && !empty($e['tgl_pengajuan'])) {
                    $clean[$i]['tgl_pengajuan'] = $e['tgl_pengajuan'];
                    // tgl_terima boleh diisi/diubah
                }
            }
        }
        // Admin: tidak dikunci apa pun

        /* ====== VALIDASI BISNIS: jika tgl_terima diisi, status_dokumen wajib diisi ====== */
        $violations = [];
        foreach ($clean as $idx => $row) {
            $terima = trim((string)($row['tgl_terima'] ?? ''));
            $status = trim((string)($row['status_dokumen'] ?? ''));
            if ($terima !== '' && $status === '') {
                $violations[] = $idx;
            }
        }
        if (!empty($violations)) {
            return back()
                ->withErrors(['form' => 'Status Dokumen wajib diisi pada setiap baris yang memiliki Tanggal Terima Dokumen.'])
                ->withInput();
        }
        /* =================================================================== */

        // Simpan
        $data = $this->onlyExisting([
            'proses'     => json_encode(array_values($clean)),
            'keterangan' => $r->keterangan,
            'updated_at' => now(),
        ]);

        // Kolom agregat tgl_pengajuan (ambil TANGGAL TERBARU dari proses)
        if (Schema::hasColumn($this->tbl, 'tgl_pengajuan')) {
            $latestPengajuan = null;
            foreach ($clean as $p) {
                if (!empty($p['tgl_pengajuan'])) {
                    $latestPengajuan = is_null($latestPengajuan)
                        ? $p['tgl_pengajuan']
                        : (max($latestPengajuan, $p['tgl_pengajuan'])); // format Y-m-d aman dibanding string
                }
            }
            $data['tgl_pengajuan'] = $latestPengajuan;
        }

        DB::table($this->tbl)->where('id', $id)->update($data);

        return redirect()->route('halal.edit', $id)->with('success', 'Proses Halal disimpan.');
    }

    /* ======================= KONFIRMASI ======================= */

    /** Tampilkan form konfirmasi. Jika dipanggil via AJAX/?modal=1 kirim partial untuk modal. */
    public function confirmForm(Request $r, $id)
    {
        $row = DB::table($this->tbl)
            ->leftJoin('bahans', 'bahans.id', '=', "{$this->tbl}.bahan_id")
            ->select("{$this->tbl}.*", 'bahans.nama as bahan_nama')
            ->where("{$this->tbl}.id", $id)
            ->first();

        abort_if(!$row, 404);

        $row = $this->decorate($row);

        if ($r->ajax() || $r->boolean('modal')) {
            return view('halal.parts.confirm_modal', compact('row')); // isi modal saja
        }

        // fallback halaman penuh
        return view('halal.confirm_halal', compact('row'));
    }

    /** Submit hasil konfirmasi */
    public function confirmUpdate(Request $r, $id)
    {
        $r->validate([
            'tgl_verifikasi' => ['required', 'date'],
            'hasil_halal'    => ['required', 'in:Lulus Halal,Tidak Lulus Halal'],
        ]);

        $now = now();
        $current = DB::table($this->tbl)->where('id', $id)->first();
        abort_if(!$current, 404);

        $ok = $r->hasil_halal === 'Lulus Halal';

        DB::beginTransaction();
        try {
            // Finalisasi record saat ini
            $update = [
                'status'     => $ok ? self::STATUS_HALAL_OK : self::STATUS_PURCH, // <= TIDAK LULUS: kembali ke PURCH pada record YANG SAMA
                'updated_at' => $now,
            ];
            if (Schema::hasColumn($this->tbl, 'hasil_halal'))    $update['hasil_halal']    = $ok ? 'Lulus' : 'Tidak Lulus';
            if (Schema::hasColumn($this->tbl, 'tgl_verifikasi')) $update['tgl_verifikasi'] = $r->tgl_verifikasi;

            // Kalau TIDAK LULUS: reset jejak halal & naikkan ulang_ke (jika kolom ada)
            if (!$ok) {
                if (Schema::hasColumn($this->tbl, 'ulang_ke')) {
                    $update['ulang_ke'] = (int)($current->ulang_ke ?? 0) + 1;
                }
                if (Schema::hasColumn($this->tbl, 'proses'))        $update['proses'] = json_encode([]);
                if (Schema::hasColumn($this->tbl, 'tgl_pengajuan')) $update['tgl_pengajuan'] = null;
                if (Schema::hasColumn($this->tbl, 'keterangan'))    $update['keterangan'] = null;
            }

            DB::table($this->tbl)->where('id', $id)->update($update);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['form' => 'Gagal menyimpan: '.$e->getMessage()]);
        }

        // Redirect sesuai hasil (tanpa membuat baris baru)
        if ($ok) {
            return redirect()->route('halal.index')->with('success', 'Hasil Halal: Lulus.');
        }

        // Tidak Lulus → balik ke Purchasing (tab pending) fokus ke item yang sama
        return redirect()
            ->to(route('purch-vendor.index', ['tab' => 'pending', 'focus' => $id]) . '#tab-pending')
            ->with('success', 'Hasil Halal: Tidak Lulus. Dikembalikan ke Purchasing tanpa membuat baris baru.');
    }
}
