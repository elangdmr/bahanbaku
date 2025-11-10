<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route as RouteFacade;

class RiwayatController extends Controller
{
    /* =================== Tabel sumber =================== */
    protected string $tblPB  = 'permintaan_bahan';
    protected string $tblReg = 'registrasi_nie';

    /* =================== Helpers =================== */

    private function pbCode($bahanId, $ulangKe): string
    {
        $base = 'PB-' . str_pad((string)($bahanId ?? 0), 2, '0', STR_PAD_LEFT);
        return ((int)($ulangKe ?? 0) > 0) ? "{$base}.{$ulangKe}" : $base;
    }

    private function jsonArr(mixed $val): array
    {
        if (is_array($val)) return $val;
        if (is_string($val) && $val !== '') return json_decode($val, true) ?: [];
        return [];
    }

    private function withSubmitter(?string $ket, ?string $email): string
    {
        $ket = trim((string)($ket ?? ''));
        $e   = trim((string)($email ?? ''));
        if ($e !== '') return $ket === '' ? "by {$e}" : "{$ket} — by {$e}";
        return $ket;
    }

    private function csvSanitize(?string $s): string
    {
        $s = (string)($s ?? '');
        $s = str_replace(["\r\n", "\r", "\n"], ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /** Safe getter untuk properti yang mungkin tidak ada */
    private function col(object $row, string $name, $default = null)
    {
        return property_exists($row, $name) ? $row->{$name} : $default;
    }

    /** Format Y-m-d singkat */
    private function d($v): string
    {
        return $v ? substr((string)$v, 0, 10) : '';
    }

    /** Normalisasi nama modul/origin untuk pencocokan route */
    private function norm(?string $s): string
    {
        $s = strtolower(trim((string)$s));
        $s = str_replace(['_', '-'], ' ', $s);
        return $s;
    }

    /**
     * Bangun event dari satu baris PB (support SEMUA tanggal yang ada).
     * @param object      $row
     * @param string|null $filterModul  null = semua modul, selain itu filter spesifik
     * @param string|null $origin       origin untuk diteruskan ke link detail (agar tombol kembali tahu pulang kemana)
     */
    private function buildPbEvents(object $row, ?string $filterModul = null, ?string $origin = null): array
    {
        $kode  = $this->pbCode($row->bahan_id, $row->ulang_ke);
        $email = $row->pb_user_email ?? null;
        $out   = [];
        $seen  = [];

        $want = fn(string $m) =>
            $filterModul === null || $filterModul === '' || $filterModul === $m;

        $push = function (string $modul, ?string $tanggal, string $peristiwa, ?string $status, ?string $ket)
            use (&$out, &$seen, $row, $kode, $email, $origin)
        {
            if (!$tanggal) return;
            $key = "{$modul}|{$peristiwa}|".substr((string)$tanggal, 0, 10);
            if (isset($seen[$key])) return;
            $seen[$key] = 1;

            $paramsAll  = ['type' => 'pb', 'id' => $row->id, 'modul' => 'ALL'];
            $paramsMod  = ['type' => 'pb', 'id' => $row->id, 'modul' => $modul];
            if ($origin) { $paramsAll['origin'] = $origin; $paramsMod['origin'] = $origin; }

            $out[] = [
                'tanggal'     => $tanggal,
                'kode'        => $kode,
                'bahan'       => $row->bahan_nama,
                'modul'       => $modul,
                'peristiwa'   => $peristiwa,
                'status'      => $status,
                'keterangan'  => $this->withSubmitter($ket, $email),
                'pb_id'       => $row->id,
                'link'        => route('riwayat.detail', $paramsAll),
                'link_modul'  => route('riwayat.detail', $paramsMod),
            ];
        };

        $mapDates = [
            'Permintaan' => [
                ['created_at',         'Permintaan dibuat',   fn($r)=>$r->status ?? '-',            fn($r)=>$r->keterangan ?? null],
                ['tanggal_kebutuhan',  'Tanggal kebutuhan',   fn($r)=>null,                         fn($r)=>$r->keterangan ?? null],
            ],
            'Sampling PCH' => [
                ['tgl_sampling_permintaan','Permintaan sampling',       fn($r)=>null,                  fn($r)=>$r->keterangan ?? null],
                ['est_sampling_diterima',  'Estimasi sampling diterima',fn($r)=>null,                  fn($r)=>$r->keterangan ?? null],
                ['tgl_sampling_dikirim',   'Sampling dikirim',          fn($r)=>null,                  fn($r)=>$r->keterangan ?? null],
                ['tgl_sampling_diterima',  'Sampling diterima',         fn($r)=>'Lanjut Trial R&D',    fn($r)=>$r->keterangan ?? null],
            ],
            'Uji COA' => [
                ['tgl_permintaan_coa', 'Permintaan COA',       fn($r)=>null,                  fn($r)=>$r->detail_uji ?? null],
                ['est_coa_diterima',   'Estimasi COA diterima',fn($r)=>null,                  fn($r)=>$r->detail_uji ?? null],
                ['tgl_coa_diterima',   'COA diterima',         fn($r)=>$r->hasil_uji ?? null, fn($r)=>$r->detail_uji ?? null],
            ],
            'Halal' => [
                ['tgl_pengajuan', 'Pengajuan halal',  fn($r)=>$r->hasil_halal ?? null, fn($r)=>$r->keterangan ?? null],
                ['tgl_verifikasi','Verifikasi halal', fn($r)=>$r->hasil_halal ?? null, fn($r)=>$r->keterangan ?? null],
            ],
            'Trial R&D' => [
                ['tgl_trial',        'Trial R&D mulai',   fn($r)=>$r->hasil_trial ?? null, fn($r)=>$r->keterangan ?? null],
                ['tgl_selesai_trial','Trial R&D selesai', fn($r)=>$r->hasil_trial ?? null, fn($r)=>$r->keterangan ?? null],
            ],
            'Registrasi' => [
                ['nie_verifikasi',        'NIE Verifikasi',     fn($r)=>$r->nie_hasil ?? null, fn($r)=>$r->nie_keterangan ?? null],
                ['nie_tgl_trial_selesai', 'NIE Trial Selesai',  fn($r)=>$r->nie_hasil ?? null, fn($r)=>$r->nie_keterangan ?? null],
                ['nie_tgl_submit',        'NIE Submit',         fn($r)=>$r->nie_hasil ?? null, fn($r)=>$r->nie_keterangan ?? null],
                ['nie_tgl_terbit',        'NIE Terbit',         fn($r)=>$r->nie_hasil ?? null, fn($r)=>$r->nie_keterangan ?? null],
            ],
        ];

        // Permintaan
        if ($want('Permintaan')) {
            $push('Permintaan', $row->created_at ?? null, 'Permintaan dibuat', $row->status ?? '-', $row->keterangan ?? null);
            foreach ($mapDates['Permintaan'] as [$col, $label, $fs, $fk]) {
                if ($col === 'created_at') continue;
                $push('Permintaan', $row->{$col} ?? null, $label, $fs($row), $fk($row));
            }
        }

        // Purchasing
        if ($want('Purchasing')) {
            if (!empty($row->vendor_id)) {
                $push('Purchasing', $row->updated_at ?? null, 'Vendor dipilih', null, $row->distributor ?? null);
            }
            $tglPO = $this->col($row, 'tgl_po');
            $noPO  = $this->col($row, 'no_po');
            $push('Purchasing', $tglPO, 'PO dibuat', $noPO ? ('PO: ' . $noPO) : null, $this->col($row, 'distributor'));
        }

        // Sampling PCH
        if ($want('Sampling PCH')) {
            foreach ($mapDates['Sampling PCH'] as [$col, $label, $fs, $fk]) {
                $push('Sampling PCH', $row->{$col} ?? null, $label, $fs($row), $fk($row));
            }
        }

        // Uji COA
        if ($want('Uji COA')) {
            foreach ($mapDates['Uji COA'] as [$col, $label, $fs, $fk]) {
                $push('Uji COA', $row->{$col} ?? null, $label, $fs($row), $fk($row));
            }
        }

        // Halal (+ JSON lama)
        if ($want('Halal')) {
            foreach ($mapDates['Halal'] as [$col, $label, $fs, $fk]) {
                $push('Halal', $row->{$col} ?? null, $label, $fs($row), $fk($row));
            }
            $prosesHalal = $this->jsonArr($row->proses ?? null);
            foreach ($prosesHalal as $p) {
                if (!empty($p['tgl_pengajuan']))
                    $push('Halal', $p['tgl_pengajuan'], 'Pengajuan dokumen', $p['status_dokumen'] ?? null, $p['keterangan'] ?? null);
                if (!empty($p['tgl_terima']))
                    $push('Halal', $p['tgl_terima'], 'Dokumen diterima', $p['status_dokumen'] ?? null, $p['keterangan'] ?? null);
            }
        }

        // Trial R&D (+ JSON lama)
        if ($want('Trial R&D')) {
            foreach ($mapDates['Trial R&D'] as [$col, $label, $fs, $fk]) {
                $push('Trial R&D', $row->{$col} ?? null, $label, $fs($row), $fk($row));
            }
            foreach ([
                ['col' => 'trial_bahan',    'label' => 'Trial Bahan',        'start' => 'mulai', 'end' => 'selesai'],
                ['col' => 'uji_formulasi',  'label' => 'Uji Formulasi',      'start' => 'mulai', 'end' => 'selesai'],
                ['col' => 'uji_stabilitas', 'label' => 'Uji Stabilitas',     'start' => 'awal',  'end' => 'selesai'],
                ['col' => 'uji_be',         'label' => 'Uji Bioequivalence', 'start' => 'awal',  'end' => 'selesai'],
            ] as $cfg) {
                $rows = $this->jsonArr($row->{$cfg['col']} ?? null);
                foreach ($rows as $it) {
                    if (!empty($it[$cfg['start']]))
                        $push('Trial R&D', $it[$cfg['start']], "{$cfg['label']} mulai", $it['status'] ?? null, $it['keterangan'] ?? null);
                    if (!empty($it[$cfg['end']]))
                        $push('Trial R&D', $it[$cfg['end']], "{$cfg['label']} selesai", $it['status'] ?? null, $it['keterangan'] ?? null);
                }
            }
        }

        // Registrasi (kolom NIE yg nempel di PB)
        if ($want('Registrasi')) {
            foreach ($mapDates['Registrasi'] as [$col, $label, $fs, $fk]) {
                $push('Registrasi', $row->{$col} ?? null, $label, $fs($row), $fk($row));
            }
        }

        return $out;
    }

    /** URL & label tombol Kembali — prioritaskan 'origin' kalau ada */
    private function backMeta(?string $modul, string $type, int $id, ?string $origin = null, ?string $originTab = null): array
    {
        $key = $this->norm($origin ?: $modul);

        // Peta route yang tersedia (lihat web.php)
        $routes = [
            'riwayat'            => ['route' => 'riwayat.index',      'label' => 'Riwayat Proses'],
            'permintaan'         => ['route' => 'show-permintaan',    'label' => 'Permintaan Bahan Baku'],
            'purchasing'         => ['route' => 'purch-vendor.index', 'label' => 'Purchasing Vendor'],
            'sampling pch'       => ['route' => 'sampling-pch.index', 'label' => 'Sampling PCH'],
            'uji coa'            => ['route' => 'uji-coa.index',      'label' => 'Hasil Uji COA'],
            'halal'              => ['route' => 'halal.index',        'label' => 'Halal PPIC'],
            'trial r&d'          => ['route' => 'trial-rnd.index',    'label' => 'Trial R&D'],
            'registrasi'         => ['route' => 'registrasi.index',   'label' => 'Registrasi'],
            // >>> Tambahan agar balik ke Metrik Registrasi:
            'metrik registrasi'  => ['route' => 'registrasi.metrik',  'label' => 'Metrik Registrasi'],
        ];

        // toleransi sinonim umum
        if ($key === 'trial rnd') $key = 'trial r&d';
        if ($key === 'sampling')  $key = 'sampling pch';
        if ($key === 'metrik registrasi' || $key === 'metrik  registrasi') {
            $key = 'metrik registrasi';
        }
        if ($key === 'metrik registrasi ') $key = 'metrik registrasi';
        if ($key === 'metrik-registrasi')  $key = 'metrik registrasi';

        $meta = $routes[$key] ?? $routes['riwayat'];

        // query tambahan (mis. tab aktif, fokus row)
        $query = ['focus' => $id];
        if ($originTab) $query['tab'] = $originTab;

        return ['route' => $meta['route'], 'label' => $meta['label'], 'query' => $query];
    }

    /* =================== INDEX (list gabungan + filter) =================== */
    public function index(Request $r)
    {
        $events = collect();
        $origin = $r->query('origin'); // jika index ini juga dipanggil dari modul lain dan ingin meneruskan origin

        // PB
        $pbs = DB::table($this->tblPB.' as pb')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->leftJoin('users  as u', 'u.id', '=', 'pb.user_id')
            ->select('pb.*', DB::raw('b.nama as bahan_nama'), DB::raw('u.email as pb_user_email'))
            ->get();

        foreach ($pbs as $row) {
            foreach ($this->buildPbEvents($row, null, $origin) as $ev) {
                // pastikan link ALL (sudah include origin dari buildPbEvents)
                $events->push($ev);
            }
        }

        // Registrasi (tabel khusus)
        $joinUserReg = Schema::hasColumn($this->tblReg, 'user_id');
        $regs = DB::table($this->tblReg.' as r')
            ->leftJoin($this->tblPB.' as pb', 'pb.id', '=', 'r.trial_id')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->leftJoin('users  as upb', 'upb.id', '=', 'pb.user_id')
            ->when($joinUserReg, fn($q) => $q->leftJoin('users as ur', 'ur.id', '=', 'r.user_id'))
            ->select(
                'r.*','pb.id as pb_id','pb.bahan_id','pb.ulang_ke',
                DB::raw('b.nama as bahan_nama'),
                DB::raw('upb.email as pb_user_email'),
                DB::raw($joinUserReg ? 'ur.email as reg_user_email' : 'NULL as reg_user_email')
            )
            ->get();

        foreach ($regs as $rr) {
            $kode   = $this->pbCode($rr->bahan_id, $rr->ulang_ke);
            $email  = $rr->reg_user_email ?: $rr->pb_user_email;
            $proses = $this->jsonArr($rr->proses ?? null);

            foreach ($proses as $p) {
                if (!empty($p['tgl_submit'])) {
                    $paramsAll = $rr->pb_id
                        ? ['type' => 'pb',  'id' => $rr->pb_id, 'modul' => 'ALL']
                        : ['type' => 'reg', 'id' => $rr->id,    'modul' => 'Registrasi'];
                    $paramsMod = ['type' => 'reg', 'id' => $rr->id, 'modul' => 'Registrasi'];
                    if ($origin) { $paramsAll['origin'] = $origin; $paramsMod['origin'] = $origin; }

                    $events->push([
                        'tanggal'    => $p['tgl_submit'],
                        'kode'       => $kode,
                        'bahan'      => $rr->bahan_nama,
                        'modul'      => 'Registrasi',
                        'peristiwa'  => 'NIE Submit',
                        'status'     => $p['status_dokumen'] ?? null,
                        'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $email),
                        'pb_id'      => $rr->pb_id,
                        'link'       => route('riwayat.detail', $paramsAll),
                        'link_modul' => route('riwayat.detail', $paramsMod),
                    ]);
                }
                if (!empty($p['tgl_terbit'])) {
                    $paramsAll = $rr->pb_id
                        ? ['type' => 'pb',  'id' => $rr->pb_id, 'modul' => 'ALL']
                        : ['type' => 'reg', 'id' => $rr->id,    'modul' => 'Registrasi'];
                    $paramsMod = ['type' => 'reg', 'id' => $rr->id, 'modul' => 'Registrasi'];
                    if ($origin) { $paramsAll['origin'] = $origin; $paramsMod['origin'] = $origin; }

                    $events->push([
                        'tanggal'    => $p['tgl_terbit'],
                        'kode'       => $kode,
                        'bahan'      => $rr->bahan_nama,
                        'modul'      => 'Registrasi',
                        'peristiwa'  => 'NIE Terbit',
                        'status'     => $p['status_dokumen'] ?? null,
                        'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $email),
                        'pb_id'      => $rr->pb_id,
                        'link'       => route('riwayat.detail', $paramsAll),
                        'link_modul' => route('riwayat.detail', $paramsMod),
                    ]);
                }
            }

            if (!empty($rr->hasil)) {
                $paramsAll = $rr->pb_id
                    ? ['type' => 'pb',  'id' => $rr->pb_id, 'modul' => 'ALL']
                    : ['type' => 'reg', 'id' => $rr->id,    'modul' => 'Registrasi'];
                $paramsMod = ['type' => 'reg', 'id' => $rr->id, 'modul' => 'Registrasi'];
                if ($origin) { $paramsAll['origin'] = $origin; $paramsMod['origin'] = $origin; }

                $events->push([
                    'tanggal'    => $rr->updated_at,
                    'kode'       => $kode,
                    'bahan'      => $rr->bahan_nama,
                    'modul'      => 'Registrasi',
                    'peristiwa'  => 'Konfirmasi hasil',
                    'status'     => $rr->hasil,
                    'keterangan' => $this->withSubmitter($rr->keterangan ?? null, $email),
                    'pb_id'      => $rr->pb_id,
                    'link'       => route('riwayat.detail', $paramsAll),
                    'link_modul' => route('riwayat.detail', $paramsMod),
                ]);
            }
        }

        // Filter
        $q    = trim($r->input('q', ''));
        $mod  = trim($r->input('modul', ''));
        $from = $r->input('from');
        $to   = $r->input('to');

        $events = $events->filter(function ($e) use ($q, $mod, $from, $to) {
            if ($q !== '' &&
                stripos($e['kode'].' '.$e['bahan'].' '.$e['peristiwa'].' '.($e['status'] ?? '').' '.($e['keterangan'] ?? ''), $q) === false) {
                return false;
            }
            if ($mod !== '' && $e['modul'] !== $mod) return false;
            if ($from && substr((string)$e['tanggal'], 0, 10) < $from) return false;
            if ($to   && substr((string)$e['tanggal'], 0, 10) > $to)   return false;
            return true;
        })->sortByDesc('tanggal')->values();

        $modulList = ['Permintaan','Purchasing','Sampling PCH','Uji COA','Halal','Trial R&D','Registrasi'];

        return view('riwayat.index', [
            'events'    => $events,
            'modulList' => $modulList,
            'filter'    => compact('q', 'mod', 'from', 'to'),
        ]);
    }

    /* =================== EXPORT (long / pivot) =================== */
    public function export(Request $r)
    {
        $layout = strtolower($r->query('layout', 'pivot'));
        if (in_array($layout, ['pivot','wide'])) {
            return $this->exportPivot($r);
        }
        return $this->exportLong($r);
    }

    /** Export LONG (baris per proses) — seperti sebelumnya */
    private function exportLong(Request $r)
    {
        $scope = strtolower($r->query('scope', 'all')) === 'filtered' ? 'filtered' : 'all';
        $q    = trim($r->query('q', ''));
        $mod  = trim($r->query('modul', ''));
        $from = $r->query('from');
        $to   = $r->query('to');

        $accept = function (array $e) use ($scope, $q, $mod, $from, $to) {
            if ($scope !== 'filtered') return true;
            if ($q !== '' &&
                stripos($e['kode'].' '.$e['bahan'].' '.$e['peristiwa'].' '.($e['status'] ?? '').' '.($e['keterangan'] ?? ''), $q) === false) {
                return false;
            }
            if ($mod !== '' && $e['modul'] !== $mod) return false;
            if ($from && substr((string)$e['tanggal'], 0, 10) < $from) return false;
            if ($to   && substr((string)$e['tanggal'], 0, 10) > $to)   return false;
            return true;
        };

        $filename = 'riwayat_raw_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($accept) {
            @set_time_limit(0);
            @ini_set('memory_limit', '1024M');

            $out = fopen('php://output', 'w');
            echo "\xEF\xBB\xBF";
            fputcsv($out, ['Tanggal','ID','Nama Bahan','Modul','Peristiwa','Status','Keterangan']);

            DB::table($this->tblPB.' as pb')
                ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
                ->leftJoin('users  as u', 'u.id', '=', 'pb.user_id')
                ->select('pb.*', DB::raw('b.nama as bahan_nama'), DB::raw('u.email as pb_user_email'))
                ->orderBy('pb.id')
                ->chunk(500, function ($chunk) use ($out, $accept) {
                    foreach ($chunk as $row) {
                        foreach ($this->buildPbEvents($row, null, null) as $e) {
                            if (!$accept($e)) continue;
                            fputcsv($out, [
                                $this->d($e['tanggal']),
                                $e['kode'],
                                $this->csvSanitize($e['bahan']),
                                $e['modul'],
                                $this->csvSanitize($e['peristiwa'] ?? ''),
                                $this->csvSanitize($e['status'] ?? ''),
                                $this->csvSanitize($e['keterangan'] ?? ''),
                            ]);
                        }
                    }
                });

            // Registrasi dari tabel khusus
            $joinUserReg = Schema::hasColumn($this->tblReg, 'user_id');
            DB::table($this->tblReg.' as r')
                ->leftJoin('pb as dummy', 'dummy.id', '=', 'dummy.id') // no-op, keep structure identical if needed
                ->leftJoin($this->tblPB.' as pb', 'pb.id', '=', 'r.trial_id')
                ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
                ->leftJoin('users  as upb', 'upb.id', '=', 'pb.user_id')
                ->when($joinUserReg, fn($q) => $q->leftJoin('users as ur', 'ur.id', '=', 'r.user_id'))
                ->select(
                    'r.*','pb.id as pb_id','pb.bahan_id','pb.ulang_ke',
                    DB::raw('b.nama as bahan_nama'),
                    DB::raw('upb.email as pb_user_email'),
                    DB::raw($joinUserReg ? 'ur.email as reg_user_email' : 'NULL as reg_user_email')
                )
                ->orderBy('r.id')
                ->chunk(500, function ($chunk) use ($out, $accept) {
                    foreach ($chunk as $rr) {
                        $kode   = $this->pbCode($rr->bahan_id, $rr->ulang_ke);
                        $email  = $rr->reg_user_email ?: $rr->pb_user_email;
                        $proses = $this->jsonArr($rr->proses ?? null);

                        foreach ($proses as $p) {
                            if (!empty($p['tgl_submit'])) {
                                $e = [
                                    'tanggal'    => $p['tgl_submit'],
                                    'kode'       => $kode,
                                    'bahan'      => $rr->bahan_nama,
                                    'modul'      => 'Registrasi',
                                    'peristiwa'  => 'NIE Submit',
                                    'status'     => $p['status_dokumen'] ?? null,
                                    'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $email),
                                ];
                                if ($accept($e)) {
                                    fputcsv($out, [
                                        $this->d($e['tanggal']),
                                        $e['kode'],
                                        $this->csvSanitize($e['bahan']),
                                        $e['modul'],
                                        $this->csvSanitize($e['peristiwa'] ?? ''),
                                        $this->csvSanitize($e['status'] ?? ''),
                                        $this->csvSanitize($e['keterangan'] ?? ''),
                                    ]);
                                }
                            }
                            if (!empty($p['tgl_terbit'])) {
                                $e = [
                                    'tanggal'    => $p['tgl_terbit'],
                                    'kode'       => $kode,
                                    'bahan'      => $rr->bahan_nama,
                                    'modul'      => 'Registrasi',
                                    'peristiwa'  => 'NIE Terbit',
                                    'status'     => $p['status_dokumen'] ?? null,
                                    'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $email),
                                ];
                                if ($accept($e)) {
                                    fputcsv($out, [
                                        $this->d($e['tanggal']),
                                        $e['kode'],
                                        $this->csvSanitize($e['bahan']),
                                        $e['modul'],
                                        $this->csvSanitize($e['peristiwa'] ?? ''),
                                        $this->csvSanitize($e['status'] ?? ''),
                                        $this->csvSanitize($e['keterangan'] ?? ''),
                                    ]);
                                }
                            }
                        }

                        if (!empty($rr->hasil)) {
                            $e = [
                                'tanggal'    => $rr->updated_at,
                                'kode'       => $kode,
                                'bahan'      => $rr->bahan_nama,
                                'modul'      => 'Registrasi',
                                'peristiwa'  => 'Konfirmasi hasil',
                                'status'     => $rr->hasil,
                                'keterangan' => $this->withSubmitter($rr->keterangan ?? null, $email),
                            ];
                            if ($accept($e)) {
                                fputcsv($out, [
                                    $this->d($e['tanggal']),
                                    $e['kode'],
                                    $this->csvSanitize($e['bahan']),
                                    $e['modul'],
                                    $this->csvSanitize($e['peristiwa'] ?? ''),
                                    $this->csvSanitize($e['status'] ?? ''),
                                    $this->csvSanitize($e['keterangan'] ?? ''),
                                ]);
                            }
                        }
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ]);
    }

    /** Export PIVOT (nyamping: 1 baris = 1 bahan/PB) */
    private function exportPivot(Request $r)
    {
        $filename = 'riwayat_pivot_'.now()->format('Ymd_His').'.csv';

        // Prefetch REG proses untuk fallback NIE Submit/Terbit
        $regMap = DB::table($this->tblReg.' as r')
            ->select('r.trial_id','r.proses')
            ->get()
            ->keyBy('trial_id');

        // Definisi kolom pivot (urutan tampil)
        $cols = [
            'created_at'           => 'Permintaan dibuat (tgl)',
            'tanggal_kebutuhan'    => 'Tanggal kebutuhan (tgl)',

            'vendor_pilih_tgl'     => 'Vendor dipilih (tgl)', // virtual: updated_at jika vendor_id ada
            'distributor'          => 'Distributor',
            'tgl_po'               => 'PO dibuat (tgl)',
            'no_po'                => 'PO No',

            'tgl_sampling_permintaan' => 'Sampling: permintaan (tgl)',
            'est_sampling_diterima'   => 'Sampling: estimasi diterima (tgl)',
            'tgl_sampling_dikirim'    => 'Sampling: dikirim (tgl)',
            'tgl_sampling_diterima'   => 'Sampling: diterima (tgl)',

            'tgl_permintaan_coa'   => 'COA: permintaan (tgl)',
            'est_coa_diterima'     => 'COA: estimasi diterima (tgl)',
            'tgl_coa_diterima'     => 'COA: diterima (tgl)',
            'hasil_uji'            => 'COA: hasil',

            'tgl_pengajuan'        => 'Halal: pengajuan (tgl)',
            'tgl_verifikasi'       => 'Halal: verifikasi (tgl)',
            'hasil_halal'          => 'Halal: hasil',

            'tgl_trial'            => 'Trial: mulai (tgl)',
            'tgl_selesai_trial'    => 'Trial: selesai (tgl)',
            'hasil_trial'          => 'Trial: hasil',

            // NIE dari PB (kalau kosong, fallback dari tabel registrasi)
            'nie_verifikasi'       => 'NIE: verifikasi (tgl)',
            'nie_tgl_trial_selesai'=> 'NIE: trial selesai (tgl)',
            'nie_tgl_submit'       => 'NIE: submit (tgl)',
            'nie_tgl_terbit'       => 'NIE: terbit (tgl)',
            'nie_hasil'            => 'NIE: hasil',
        ];

        return response()->streamDownload(function () use ($cols, $regMap) {
            @set_time_limit(0);
            @ini_set('memory_limit', '1024M');

            $out = fopen('php://output', 'w');
            echo "\xEF\xBB\xBF";

            // Header
            $header = array_merge(['ID','Nama Bahan'], array_values($cols));
            fputcsv($out, $header);

            DB::table($this->tblPB.' as pb')
                ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
                ->leftJoin('users  as u', 'u.id', '=', 'pb.user_id')
                ->select('pb.*', DB::raw('b.nama as bahan_nama'), DB::raw('u.email as pb_user_email'))
                ->orderBy('pb.id')
                ->chunk(500, function ($chunk) use ($out, $cols, $regMap) {
                    foreach ($chunk as $row) {
                        $kode  = $this->pbCode($row->bahan_id, $row->ulang_ke);

                        // Fallback NIE submit/terbit dari tabel registrasi (JSON proses) bila kolom PB kosong
                        $nieSubmit = $this->col($row, 'nie_tgl_submit');
                        $nieTerbit = $this->col($row, 'nie_tgl_terbit');
                        if ((!$nieSubmit || !$nieTerbit) && isset($regMap[$row->id])) {
                            $proses = $this->jsonArr($regMap[$row->id]->proses ?? null);
                            foreach ($proses as $p) {
                                if (!$nieSubmit && !empty($p['tgl_submit'])) $nieSubmit = $p['tgl_submit'];
                                if (!$nieTerbit && !empty($p['tgl_terbit'])) $nieTerbit = $p['tgl_terbit'];
                            }
                        }

                        // Virtual kolom vendor_pilih_tgl
                        $vendorPilih = !empty($row->vendor_id) ? $row->updated_at : null;

                        // Susun row pivot
                        $data = [
                            'ID'         => $kode,
                            'Nama Bahan' => $this->csvSanitize($row->bahan_nama),
                        ];

                        foreach ($cols as $field => $label) {
                            switch ($field) {
                                case 'vendor_pilih_tgl':
                                    $val = $vendorPilih;
                                    break;
                                case 'nie_tgl_submit':
                                    $val = $nieSubmit;
                                    break;
                                case 'nie_tgl_terbit':
                                    $val = $nieTerbit;
                                    break;
                                // date-like → tampilkan Y-m-d
                                case 'created_at':
                                case 'tanggal_kebutuhan':
                                case 'tgl_po':
                                case 'tgl_sampling_permintaan':
                                case 'est_sampling_diterima':
                                case 'tgl_sampling_dikirim':
                                case 'tgl_sampling_diterima':
                                case 'tgl_permintaan_coa':
                                case 'est_coa_diterima':
                                case 'tgl_coa_diterima':
                                case 'tgl_pengajuan':
                                case 'tgl_verifikasi':
                                case 'tgl_trial':
                                case 'tgl_selesai_trial':
                                case 'nie_verifikasi':
                                case 'nie_tgl_trial_selesai':
                                    $val = $this->d($this->col($row, $field));
                                    break;
                                default:
                                    $val = $this->csvSanitize((string)$this->col($row, $field, ''));
                            }
                            $data[$label] = is_string($val) ? $val : $this->d($val);
                        }

                        fputcsv($out, array_values($data));
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ]);
    }

    /* =================== DETAIL (PDF/HTML) =================== */
    public function detail(Request $r, string $type, int $id)
    {
        $origin      = $r->query('origin');          // ex: metrik-registrasi, halal, uji-coa, riwayat
        $originTab   = strtolower($r->query('origin_tab', ''));
        $targetModul = $r->query('modul');           // ex: Halal, Uji COA, ALL
        $isAll       = !$targetModul || in_array(strtolower($targetModul), ['all','semua']);
        $filterModul = $isAll ? null : $targetModul;

        if ($type === 'pb') {
            $row = DB::table($this->tblPB.' as pb')
                ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
                ->leftJoin('users  as u', 'u.id', '=', 'pb.user_id')
                ->select('pb.*', DB::raw('b.nama as bahan_nama'), DB::raw('u.email as pb_user_email'))
                ->where('pb.id', $id)->first();
            abort_if(!$row, 404);

            $kode   = $this->pbCode($row->bahan_id, $row->ulang_ke);
            $events = collect($this->buildPbEvents($row, $filterModul, $origin));

            if ($filterModul === null || $filterModul === 'Registrasi') {
                $joinUserReg = Schema::hasColumn($this->tblReg, 'user_id');
                $regs = DB::table($this->tblReg.' as r')
                    ->when($joinUserReg, fn($q) => $q->leftJoin('users as ur', 'ur.id', '=', 'r.user_id'))
                    ->select('r.*', DB::raw($joinUserReg ? 'ur.email as reg_user_email' : 'NULL as reg_user_email'))
                    ->where('r.trial_id', $row->id)
                    ->get();

                $emailPB = $row->pb_user_email ?? null;
                foreach ($regs as $rr) {
                    $emailReg = $rr->reg_user_email ?: $emailPB;
                    $proses   = $this->jsonArr($rr->proses ?? null);

                    foreach ($proses as $p) {
                        if (!empty($p['tgl_submit']))
                            $events->push(['tanggal'=>$p['tgl_submit'],'kode'=>$kode,'bahan'=>$row->bahan_nama,'modul'=>'Registrasi','peristiwa'=>'NIE Submit','status'=>$p['status_dokumen'] ?? null,'keterangan'=>$this->withSubmitter($p['keterangan'] ?? null, $emailReg)]);
                        if (!empty($p['tgl_terbit']))
                            $events->push(['tanggal'=>$p['tgl_terbit'],'kode'=>$kode,'bahan'=>$row->bahan_nama,'modul'=>'Registrasi','peristiwa'=>'NIE Terbit','status'=>$p['status_dokumen'] ?? null,'keterangan'=>$this->withSubmitter($p['keterangan'] ?? null, $emailReg)]);
                    }
                    if (!empty($rr->hasil))
                        $events->push(['tanggal'=>$rr->updated_at,'kode'=>$kode,'bahan'=>$row->bahan_nama,'modul'=>'Registrasi','peristiwa'=>'Konfirmasi hasil','status'=>$rr->hasil,'keterangan'=>$this->withSubmitter($rr->keterangan ?? null, $emailReg)]);
                }
            }

            $judul = $filterModul ? "Riwayat {$filterModul}" : 'Riwayat Semua Modul';
            $viewData = [
                'judul'     => $judul,
                'kode'      => $kode,
                'bahan'     => $row->bahan_nama,
                'modul'     => $filterModul ?: 'Semua Modul',
                'events'    => $events->sortBy('tanggal')->values(),
                'generated' => now(),
            ];
        } else {
            $joinUserReg = Schema::hasColumn($this->tblReg, 'user_id');

            $rr = DB::table($this->tblReg.' as r')
                ->leftJoin($this->tblPB.' as pb', 'pb.id', '=', 'r.trial_id')
                ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
                ->leftJoin('users  as upb', 'upb.id', '=', 'pb.user_id')
                ->when($joinUserReg, fn($q) => $q->leftJoin('users as ur', 'ur.id', '=', 'r.user_id'))
                ->select(
                    'r.*', 'pb.id as pb_id', 'pb.bahan_id', 'pb.ulang_ke',
                    DB::raw('b.nama as bahan_nama'),
                    DB::raw('upb.email as pb_user_email'),
                    DB::raw($joinUserReg ? 'ur.email as reg_user_email' : 'NULL as reg_user_email')
                )
                ->where('r.id', $id)->first();
            abort_if(!$rr, 404);

            $kode   = $this->pbCode($rr->bahan_id, $rr->ulang_ke);
            $events = collect();

            if ($rr->pb_id && ($filterModul === null || $filterModul !== 'Registrasi')) {
                $pbRow = DB::table($this->tblPB.' as pb')
                    ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
                    ->leftJoin('users  as u', 'u.id', '=', 'pb.user_id')
                    ->select('pb.*', DB::raw('b.nama as bahan_nama'), DB::raw('u.email as pb_user_email'))
                    ->where('pb.id', $rr->pb_id)->first();
                if ($pbRow) foreach ($this->buildPbEvents($pbRow, $filterModul, $origin) as $ev) $events->push($ev);
            }

            if ($filterModul === null || $filterModul === 'Registrasi') {
                $email  = $rr->reg_user_email ?: $rr->pb_user_email;
                $proses = $this->jsonArr($rr->proses ?? null);

                foreach ($proses as $p) {
                    if (!empty($p['tgl_submit']))
                        $events->push(['tanggal'=>$p['tgl_submit'],'kode'=>$kode,'bahan'=>$rr->bahan_nama,'modul'=>'Registrasi','peristiwa'=>'NIE Submit','status'=>$p['status_dokumen'] ?? null,'keterangan'=>$this->withSubmitter($p['keterangan'] ?? null, $email)]);
                    if (!empty($p['tgl_terbit']))
                        $events->push(['tanggal'=>$p['tgl_terbit'],'kode'=>$kode,'bahan'=>$rr->bahan_nama,'modul'=>'Registrasi','peristiwa'=>'NIE Terbit','status'=>$p['status_dokumen'] ?? null,'keterangan'=>$this->withSubmitter($p['keterangan'] ?? null, $email)]);
                }
                if (!empty($rr->hasil))
                    $events->push(['tanggal'=>$rr->updated_at,'kode'=>$kode,'bahan'=>$rr->bahan_nama,'modul'=>'Registrasi','peristiwa'=>'Konfirmasi hasil','status'=>$rr->hasil,'keterangan'=>$this->withSubmitter($rr->keterangan ?? null, $email)]);
            }

            $judul = $filterModul ? "Riwayat {$filterModul}" : 'Riwayat Semua Modul';
            $viewData = [
                'judul'     => $judul,
                'kode'      => $kode,
                'bahan'     => $rr->bahan_nama,
                'modul'     => $filterModul ?: 'Semua Modul',
                'events'    => $events->sortBy('tanggal')->values(),
                'generated' => now(),
            ];
        }

        // Gunakan origin (jika ada) agar tombol kembali pulang ke menu modul asal
        $meta = $this->backMeta($targetModul, $type, $id, $origin, $originTab);
        $viewData['back_label'] = $meta['label'];
        $viewData['back_url']   = RouteFacade::has($meta['route'])
            ? route($meta['route'], $meta['query'] ?? [])
            : route('riwayat.index');

        $fileName = 'Riwayat_'.$viewData['kode'].'_'.str_replace(' ', '_', $viewData['modul']).'.pdf';
        try {
            if (app()->bound('dompdf.wrapper')) {
                /** @var \Barryvdh\DomPDF\PDF $pdf */
                $pdf = app('dompdf.wrapper');
                $pdf->loadView('riwayat.pdf', $viewData)->setPaper('a4', 'portrait');
                return $pdf->stream($fileName); // inline di TAB yang sama
            }
        } catch (\Throwable $e) {}

        // fallback HTML
        return view('riwayat.pdf', $viewData);
    }

    /* =================== TAMBAHAN: agar route/fitur baru aman =================== */

    /** Show by kode → redirect ke detail */
    public function show(Request $r, string $kode)
    {
        // Kode format: PB-02 atau PB-02.1
        if (preg_match('/^PB-(\d{2})(?:\.(\d+))?$/', $kode, $m)) {
            $bahanId = (int)$m[1];
            $ulangKe = isset($m[2]) ? (int)$m[2] : 0;

            $row = DB::table($this->tblPB.' as pb')
                ->select('pb.id')
                ->where('pb.bahan_id', $bahanId)
                ->whereRaw('COALESCE(pb.ulang_ke,0) = ?', [$ulangKe])
                ->orderByDesc('pb.id')
                ->first();

            if ($row) {
                $params = ['type' => 'pb', 'id' => $row->id, 'modul' => 'ALL'];
                if ($r->has('origin')) $params['origin'] = $r->query('origin');
                return redirect()->route('riwayat.detail', $params);
            }
        }
        abort(404);
    }

    /** Export DB mentah: table=permintaan_bahan|registrasi_nie (default permintaan_bahan) */
    public function exportDb(Request $r)
    {
        $table = $r->query('table', $this->tblPB);
        $table = in_array($table, [$this->tblPB, $this->tblReg], true) ? $table : $this->tblPB;

        $cols = Schema::getColumnListing($table);
        $filename = 'dump_'.$table.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($table, $cols) {
            @set_time_limit(0);
            @ini_set('memory_limit', '1024M');

            $out = fopen('php://output', 'w');
            echo "\xEF\xBB\xBF";
            fputcsv($out, $cols);

            DB::table($table)
                ->orderBy('id')
                ->chunk(500, function ($chunk) use ($out, $cols) {
                    foreach ($chunk as $row) {
                        $line = [];
                        foreach ($cols as $c) {
                            $val = $row->{$c} ?? null;
                            if (is_array($val) || is_object($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                            $line[] = $this->csvSanitize((string)$val);
                        }
                        fputcsv($out, $line);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ]);
    }
}
