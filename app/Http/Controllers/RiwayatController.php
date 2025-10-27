<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Bangun event dari satu baris PB.
     * Jika $filterModul null => semua modul PB.
     * Event yang dikembalikan SELALU menyertakan:
     *  - pb_id
     *  - link       -> riwayat ALL (recommended)
     *  - link_modul -> riwayat modul spesifik (opsional)
     */
    private function buildPbEvents(object $row, ?string $filterModul = null): array
    {
        $kode  = $this->pbCode($row->bahan_id, $row->ulang_ke);
        $email = $row->pb_user_email ?? null;
        $out   = [];

        $want = fn(string $m) =>
            $filterModul === null || $filterModul === '' || $filterModul === $m;

        $push = function (string $modul, ?string $tanggal, string $peristiwa, ?string $status, ?string $ket)
            use (&$out, $row, $kode, $email)
        {
            if (!$tanggal) return;
            $out[] = [
                'tanggal'     => $tanggal,
                'kode'        => $kode,
                'bahan'       => $row->bahan_nama,
                'modul'       => $modul,
                'peristiwa'   => $peristiwa,
                'status'      => $status,
                'keterangan'  => $this->withSubmitter($ket, $email),
                'pb_id'       => $row->id,
                // default ke riwayat lengkap:
                'link'        => route('riwayat.detail', ['type' => 'pb', 'id' => $row->id, 'modul' => 'ALL']),
                // opsional kalau mau per-modul:
                'link_modul'  => route('riwayat.detail', ['type' => 'pb', 'id' => $row->id, 'modul' => $modul]),
            ];
        };

        // Permintaan
        if ($want('Permintaan')) {
            $push('Permintaan', $row->created_at, 'Permintaan dibuat', $row->status ?? '-', $row->keterangan ?? null);
        }

        // Purchasing
        if ($want('Purchasing')) {
            if (!empty($row->vendor_id)) {
                $push('Purchasing', $row->updated_at, 'Vendor dipilih', null, $row->distributor ?? null);
            }
            if (!empty($row->tgl_po)) {
                $push('Purchasing', $row->tgl_po, 'PO dibuat', $row->no_po ? 'PO: '.$row->no_po : null, $row->distributor ?? null);
            }
        }

        // Sampling PCH
        if ($want('Sampling PCH')) {
            $push('Sampling PCH', $row->tgl_sampling_permintaan, 'Permintaan sampling', null, $row->keterangan ?? null);
            $push('Sampling PCH', $row->est_sampling_diterima,   'Estimasi sampling diterima', null, $row->keterangan ?? null);
            $push('Sampling PCH', $row->tgl_sampling_dikirim,    'Sampling dikirim', null, $row->keterangan ?? null);
            $push('Sampling PCH', $row->tgl_sampling_diterima,   'Sampling diterima', 'Lanjut Trial R&D', $row->keterangan ?? null);
        }

        // Uji COA
        if ($want('Uji COA')) {
            $push('Uji COA', $row->est_coa_diterima, 'Estimasi COA diterima', null, $row->detail_uji ?? null);
            $push('Uji COA', $row->tgl_coa_diterima, 'COA diterima', $row->hasil_uji ?? null, $row->detail_uji ?? null);
        }

        // Halal
        if ($want('Halal')) {
            $prosesHalal = $this->jsonArr($row->proses ?? null);
            foreach ($prosesHalal as $p) {
                if (!empty($p['tgl_pengajuan'])) $push('Halal', $p['tgl_pengajuan'], 'Pengajuan dokumen', $p['status_dokumen'] ?? null, $p['keterangan'] ?? null);
                if (!empty($p['tgl_terima']))    $push('Halal', $p['tgl_terima'],    'Dokumen diterima',   $p['status_dokumen'] ?? null, $p['keterangan'] ?? null);
            }
        }

        // Trial R&D
        if ($want('Trial R&D')) {
            foreach ([
                ['col' => 'trial_bahan',    'label' => 'Trial Bahan',        'start' => 'mulai', 'end' => 'selesai'],
                ['col' => 'uji_formulasi',  'label' => 'Uji Formulasi',      'start' => 'mulai', 'end' => 'selesai'],
                ['col' => 'uji_stabilitas', 'label' => 'Uji Stabilitas',     'start' => 'mulai', 'end' => 'selesai'],
                ['col' => 'uji_be',         'label' => 'Uji Bioequivalence', 'start' => 'awal',  'end' => 'selesai'],
            ] as $cfg) {
                $rows = $this->jsonArr($row->{$cfg['col']} ?? null);
                foreach ($rows as $it) {
                    if (!empty($it[$cfg['start']])) {
                        $push('Trial R&D', $it[$cfg['start']], "{$cfg['label']} mulai", $it['status'] ?? null, $it['keterangan'] ?? null);
                    }
                    if (!empty($it[$cfg['end']])) {
                        $push('Trial R&D', $it[$cfg['end']], "{$cfg['label']} selesai", $it['status'] ?? null, $it['keterangan'] ?? null);
                    }
                }
            }
        }

        return $out;
    }

    /** URL & label tombol Kembali */
    private function backMeta(?string $modul, string $type, int $id): array
    {
        $m = strtolower(trim((string)$modul));
        return match ($m) {
            'purchasing'   => ['route' => 'purch-vendor.index',    'label' => 'Purchasing Vendor', 'query' => ['tab' => 'pending', 'focus' => $id]],
            'permintaan'   => ['route' => 'permintaan-bahan.index','label' => 'Permintaan Bahan',  'query' => ['focus' => $id]],
            'uji coa'      => ['route' => 'uji-coa.index',         'label' => 'Hasil Uji COA',     'query' => ['focus' => $id]],
            'sampling pch' => ['route' => 'sampling-pch.index',    'label' => 'Sampling PCH',      'query' => ['focus' => $id]],
            'halal'        => ['route' => 'halal.index',           'label' => 'Halal PPIC',        'query' => ['focus' => $id]], // ← perbaikan
            'trial r&d'    => ['route' => 'trial-rd.index',        'label' => 'Trial R&D',         'query' => ['focus' => $id]],
            'registrasi'   => ['route' => 'registrasi.index',      'label' => 'Registrasi',        'query' => ['focus' => $id]],
            default        => ['route' => 'riwayat.index',         'label' => 'Riwayat Proses',    'query' => []],
        };
    }

    /* =================== INDEX (list gabungan + filter) =================== */
    public function index(Request $r)
    {
        $events = collect();

        // PB
        $pbs = DB::table($this->tblPB.' as pb')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->leftJoin('users  as u', 'u.id', '=', 'pb.user_id')
            ->select('pb.*', DB::raw('b.nama as bahan_nama'), DB::raw('u.email as pb_user_email'))
            ->get();

        foreach ($pbs as $row) {
            foreach ($this->buildPbEvents($row) as $ev) {
                // pastikan link default ke ALL
                $ev['link'] = route('riwayat.detail', ['type'=>'pb','id'=>$ev['pb_id'],'modul'=>'ALL']);
                $events->push($ev);
            }
        }

        // Registrasi
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
                    $events->push([
                        'tanggal'    => $p['tgl_submit'],
                        'kode'       => $kode,
                        'bahan'      => $rr->bahan_nama,
                        'modul'      => 'Registrasi',
                        'peristiwa'  => 'NIE Submit',
                        'status'     => $p['status_dokumen'] ?? null,
                        'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $email),
                        'pb_id'      => $rr->pb_id,
                        'link'       => $rr->pb_id ? route('riwayat.detail',['type'=>'pb','id'=>$rr->pb_id,'modul'=>'ALL']) : route('riwayat.detail',['type'=>'reg','id'=>$rr->id,'modul'=>'Registrasi']),
                        'link_modul' => route('riwayat.detail', ['type'=>'reg','id'=>$rr->id,'modul'=>'Registrasi']),
                    ]);
                }
                if (!empty($p['tgl_terbit'])) {
                    $events->push([
                        'tanggal'    => $p['tgl_terbit'],
                        'kode'       => $kode,
                        'bahan'      => $rr->bahan_nama,
                        'modul'      => 'Registrasi',
                        'peristiwa'  => 'NIE Terbit',
                        'status'     => $p['status_dokumen'] ?? null,
                        'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $email),
                        'pb_id'      => $rr->pb_id,
                        'link'       => $rr->pb_id ? route('riwayat.detail',['type'=>'pb','id'=>$rr->pb_id,'modul'=>'ALL']) : route('riwayat.detail',['type'=>'reg','id'=>$rr->id,'modul'=>'Registrasi']),
                        'link_modul' => route('riwayat.detail', ['type'=>'reg','id'=>$rr->id,'modul'=>'Registrasi']),
                    ]);
                }
            }

            if (!empty($rr->hasil)) {
                $events->push([
                    'tanggal'    => $rr->updated_at,
                    'kode'       => $kode,
                    'bahan'      => $rr->bahan_nama,
                    'modul'      => 'Registrasi',
                    'peristiwa'  => 'Konfirmasi hasil',
                    'status'     => $rr->hasil,
                    'keterangan' => $this->withSubmitter($rr->keterangan ?? null, $email),
                    'pb_id'      => $rr->pb_id,
                    'link'       => $rr->pb_id ? route('riwayat.detail',['type'=>'pb','id'=>$rr->pb_id,'modul'=>'ALL']) : route('riwayat.detail',['type'=>'reg','id'=>$rr->id,'modul'=>'Registrasi']),
                    'link_modul' => route('riwayat.detail', ['type'=>'reg','id'=>$rr->id,'modul'=>'Registrasi']),
                ]);
            }
        }

        // Filter UI
       // Filter UI
$q    = trim($r->input('q', ''));
$mod  = trim($r->input('modul', ''));
$from = $r->input('from');
$to   = $r->input('to');

$events = $events->filter(function($e) use ($q, $mod, $from, $to) {
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
            'filter'    => compact('q','mod','from','to'),
        ]);
    }

    /* =================== DETAIL (PDF/HTML) =================== */
    public function detail(Request $r, string $type, int $id)
    {
        $origin      = $r->query('origin');
        $targetModul = $r->query('modul');         // 'ALL' / 'Purchasing' / dst.
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
            $events = collect($this->buildPbEvents($row, $filterModul));

            // Sertakan Registrasi untuk PB ini bila ALL / filter 'Registrasi'
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
                        if (!empty($p['tgl_submit'])) {
                            $events->push([
                                'tanggal'    => $p['tgl_submit'],
                                'kode'       => $kode,
                                'bahan'      => $row->bahan_nama,
                                'modul'      => 'Registrasi',
                                'peristiwa'  => 'NIE Submit',
                                'status'     => $p['status_dokumen'] ?? null,
                                'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $emailReg),
                            ]);
                        }
                        if (!empty($p['tgl_terbit'])) {
                            $events->push([
                                'tanggal'    => $p['tgl_terbit'],
                                'kode'       => $kode,
                                'bahan'      => $row->bahan_nama,
                                'modul'      => 'Registrasi',
                                'peristiwa'  => 'NIE Terbit',
                                'status'     => $p['status_dokumen'] ?? null,
                                'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $emailReg),
                            ]);
                        }
                    }
                    if (!empty($rr->hasil)) {
                        $events->push([
                            'tanggal'    => $rr->updated_at,
                            'kode'       => $kode,
                            'bahan'      => $row->bahan_nama,
                            'modul'      => 'Registrasi',
                            'peristiwa'  => 'Konfirmasi hasil',
                            'status'     => $rr->hasil,
                            'keterangan' => $this->withSubmitter($rr->keterangan ?? null, $emailReg),
                        ]);
                    }
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

            // Event PB bila ALL atau filter bukan Registrasi
            if ($rr->pb_id && ($filterModul === null || $filterModul !== 'Registrasi')) {
                $pbRow = DB::table($this->tblPB.' as pb')
                    ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
                    ->leftJoin('users  as u', 'u.id', '=', 'pb.user_id')
                    ->select('pb.*', DB::raw('b.nama as bahan_nama'), DB::raw('u.email as pb_user_email'))
                    ->where('pb.id', $rr->pb_id)->first();
                if ($pbRow) {
                    foreach ($this->buildPbEvents($pbRow, $filterModul) as $ev) $events->push($ev);
                }
            }

            // Event Registrasi
            if ($filterModul === null || $filterModul === 'Registrasi') {
                $email  = $rr->reg_user_email ?: $rr->pb_user_email;
                $proses = $this->jsonArr($rr->proses ?? null);

                foreach ($proses as $p) {
                    if (!empty($p['tgl_submit'])) {
                        $events->push([
                            'tanggal'    => $p['tgl_submit'],
                            'kode'       => $kode,
                            'bahan'      => $rr->bahan_nama,
                            'modul'      => 'Registrasi',
                            'peristiwa'  => 'NIE Submit',
                            'status'     => $p['status_dokumen'] ?? null,
                            'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $email),
                        ]);
                    }
                    if (!empty($p['tgl_terbit'])) {
                        $events->push([
                            'tanggal'    => $p['tgl_terbit'],
                            'kode'       => $kode,
                            'bahan'      => $rr->bahan_nama,
                            'modul'      => 'Registrasi',
                            'peristiwa'  => 'NIE Terbit',
                            'status'     => $p['status_dokumen'] ?? null,
                            'keterangan' => $this->withSubmitter($p['keterangan'] ?? null, $email),
                        ]);
                    }
                }
                if (!empty($rr->hasil)) {
                    $events->push([
                        'tanggal'    => $rr->updated_at,
                        'kode'       => $kode,
                        'bahan'      => $rr->bahan_nama,
                        'modul'      => 'Registrasi',
                        'peristiwa'  => 'Konfirmasi hasil',
                        'status'     => $rr->hasil,
                        'keterangan' => $this->withSubmitter($rr->keterangan ?? null, $email),
                    ]);
                }
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

        // Tombol Kembali
        $meta = $this->backMeta($origin ?: $targetModul, $type, $id);
        $viewData['back_label'] = $meta['label'];
        $viewData['back_url']   = route($meta['route'], $meta['query'] ?? []);

        // Render
        $fileName = 'Riwayat_'.$viewData['kode'].'_'.str_replace(' ', '_', $viewData['modul']).'.pdf';
        try {
            if (app()->bound('dompdf.wrapper')) {
                /** @var \Barryvdh\DomPDF\PDF $pdf */
                $pdf = app('dompdf.wrapper');
                $pdf->loadView('riwayat.pdf', $viewData)->setPaper('a4', 'portrait');
                return $pdf->stream($fileName);
            }
        } catch (\Throwable $e) {}

        return view('riwayat.pdf', $viewData);
    }
}
