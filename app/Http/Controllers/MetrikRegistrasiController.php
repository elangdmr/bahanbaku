<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MetrikRegistrasiController extends Controller
{
    protected string $tblPB  = 'permintaan_bahan';
    protected string $tblReg = 'registrasi_nie';

    /* ================= Helpers ================= */

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

    private function firstVal(...$vals): ?string
    {
        foreach ($vals as $v) {
            $t = trim((string)($v ?? ''));
            if ($t !== '') return $t;
        }
        return null;
    }

    private function negaraFromHalalJson(?string $jsonProses): ?string
    {
        $proses = $this->jsonArr($jsonProses);
        foreach ($proses as $p) {
            if (!empty($p['negara']))  return (string)$p['negara'];
            if (!empty($p['country'])) return (string)$p['country'];
        }
        return null;
    }

    private function firstExistingCol(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }

    /**
     * COALESCE lintas tabel: ambil dari registrasi_nie (r.*) dulu, lalu fallback ke permintaan_bahan (pb.*).
     * Menghasilkan DB::raw("COALESCE(...) AS `alias`") atau "NULL AS `alias`" bila tak ada satupun kolomnya.
     */
    private function coalesceRegPb(string $alias, array $regCols, array $pbCols)
    {
        $parts = [];
        foreach ($regCols as $c) if (Schema::hasColumn($this->tblReg, $c)) $parts[] = 'r.`'.$c.'`';
        foreach ($pbCols  as $c) if (Schema::hasColumn($this->tblPB,  $c)) $parts[] = 'pb.`'.$c.'`';
        $sql = count($parts) ? ('COALESCE('.implode(',', $parts).') AS `'.$alias.'`') : ('NULL AS `'.$alias.'`');
        return DB::raw($sql);
    }

    /* =============== Sinkron bahan TERBIT -> produk_bahan =============== */
    private function syncProdukKomposisiTerbit(): void
    {
        if (!Schema::hasTable('produk_bahan')) return;
        if (!Schema::hasTable($this->tblReg) || !Schema::hasTable($this->tblPB)) return;
        if (!Schema::hasColumn($this->tblPB, 'produk_id')) return;

        $hasTerbitCol = Schema::hasColumn($this->tblReg, 'tgl_nie_terbit');
        $hasHasilCol  = Schema::hasColumn($this->tblReg, 'hasil');
        $hasProsesCol = Schema::hasColumn($this->tblReg, 'proses');

        $rows = DB::table($this->tblReg.' as r')
            ->join($this->tblPB.' as pb', 'pb.id', '=', 'r.trial_id')
            ->select(
                'pb.produk_id','pb.bahan_id','pb.jumlah','pb.satuan',
                $hasTerbitCol ? 'r.tgl_nie_terbit' : DB::raw('NULL as tgl_nie_terbit'),
                $hasHasilCol  ? 'r.hasil'          : DB::raw('NULL as hasil'),
                $hasProsesCol ? 'r.proses'         : DB::raw('NULL as proses_json')
            )
            ->whereNotNull('pb.produk_id')
            ->get();

        $toUpsert = [];
        foreach ($rows as $r) {
            $terbit = false;
            if ($hasTerbitCol && !empty($r->tgl_nie_terbit)) $terbit = true;
            if (!$terbit && $hasHasilCol && trim((string)$r->hasil) === 'Disetujui') $terbit = true;
            if (!$terbit && $hasProsesCol) {
                foreach ($this->jsonArr($r->proses_json) as $p) {
                    if (!empty($p['tgl_terbit'])) { $terbit = true; break; }
                }
            }
            if (!$terbit) continue;

            $toUpsert[] = [
                'produk_id'  => (int)$r->produk_id,
                'bahan_id'   => (int)$r->bahan_id,
                'qty'        => $r->jumlah ?? null,
                'satuan'     => $r->satuan ?? null,
                'peran'      => null,
                'urutan'     => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($toUpsert) {
            DB::table('produk_bahan')->upsert($toUpsert, ['produk_id','bahan_id'], ['qty','satuan','updated_at']);

            // Set urutan default bila masih NULL
            $produkIds = DB::table('produk_bahan')->whereNull('urutan')->distinct()->pluck('produk_id');
            foreach ($produkIds as $pid) {
                $links = DB::table('produk_bahan')->where('produk_id',$pid)->orderBy('urutan')->orderBy('id')->get();
                $i = 1;
                foreach ($links as $L) {
                    if ($L->urutan === null) {
                        DB::table('produk_bahan')->where('id',$L->id)->update(['urutan'=>$i]);
                    }
                    $i++;
                }
            }
        }
    }

    /* ============================== INDEX ============================== */
    public function index(Request $r)
    {
        $this->syncProdukKomposisiTerbit();

        $u = auth()->user();
        $role = strtolower($u->role ?? '');
        $canEdit = in_array($role, ['admin','administrator','superadmin','r&d','rnd']);

        $regProsesCol      = $this->firstExistingCol($this->tblReg, ['proses','proses_json','proses_reg','proses_registrasi']);
        $hasRegHasil       = Schema::hasColumn($this->tblReg, 'hasil');
        $hasRegKet         = Schema::hasColumn($this->tblReg, 'keterangan');
        $hasRegNegara      = Schema::hasColumn($this->tblReg, 'negara');
        $hasRegPerubahan   = Schema::hasColumn($this->tblReg, 'perubahan_desain');
        $hasRegMasa        = Schema::hasColumn($this->tblReg, 'masa_berlaku_nie');
        $hasRegBrand       = Schema::hasColumn($this->tblReg, 'brand');
        $hasRegApproveLama = Schema::hasColumn($this->tblReg, 'approve_vendor_lama');
        $hasRegSource      = Schema::hasColumn($this->tblReg, 'source_tersedia');

        $hasPbProses     = Schema::hasColumn($this->tblPB, 'proses');
        $hasPbNegara     = Schema::hasColumn($this->tblPB, 'negara');
        $hasPbNegaraAsal = Schema::hasColumn($this->tblPB, 'negara_asal');
        $hasPbAsalNegara = Schema::hasColumn($this->tblPB, 'asal_negara');
        $hasBhnNegara    = Schema::hasColumn('bahans', 'negara');

        $rows = DB::table($this->tblReg.' as r')
            ->leftJoin($this->tblPB.' as pb', 'pb.id', '=', 'r.trial_id')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->select('r.id as reg_id','r.updated_at','pb.id as pb_id','pb.bahan_id','pb.ulang_ke', DB::raw('b.nama as bahan_nama'))
            ->selectRaw('pb.produk_id as pb_produk_id')
            ->selectRaw($regProsesCol ? "r.$regProsesCol as reg_proses" : "NULL as reg_proses")
            ->selectRaw($hasRegHasil ? 'r.hasil as hasil' : 'NULL as hasil')
            ->selectRaw($hasRegKet ? 'r.keterangan as keterangan' : 'NULL as keterangan')
            ->selectRaw($hasRegNegara ? 'r.negara as reg_negara' : 'NULL as reg_negara')
            ->selectRaw($hasRegPerubahan ? 'r.perubahan_desain as perubahan_desain' : 'NULL as perubahan_desain')
            ->selectRaw($hasRegMasa ? 'r.masa_berlaku_nie as masa_berlaku_nie' : 'NULL as masa_berlaku_nie')
            ->selectRaw($hasRegBrand ? 'r.brand as brand' : 'NULL as brand')
            ->selectRaw($hasRegApproveLama ? 'r.approve_vendor_lama as approve_vendor_lama' : 'NULL as approve_vendor_lama')
            ->selectRaw($hasRegSource ? 'r.source_tersedia as source_tersedia' : 'NULL as source_tersedia')
            ->selectRaw($hasPbProses ? 'pb.proses as pb_proses' : 'NULL as pb_proses')
            ->selectRaw($hasPbNegara ? 'pb.negara as pb_negara' : 'NULL as pb_negara')
            ->selectRaw($hasPbNegaraAsal ? 'pb.negara_asal as pb_negara_asal' : 'NULL as pb_negara_asal')
            ->selectRaw($hasPbAsalNegara ? 'pb.asal_negara as pb_asal_negara' : 'NULL as pb_asal_negara')
            ->selectRaw($hasBhnNegara ? 'b.negara as bahan_negara' : 'NULL as bahan_negara')
            ->get();

        $semua = collect();
        foreach ($rows as $row) {
            $kode = $this->pbCode($row->bahan_id, $row->ulang_ke);

            $proses = $this->jsonArr($row->reg_proses);
            $tglSubmit = null; $tglTerbit = null;
            foreach ($proses as $p) {
                if (!empty($p['tgl_submit'])) $tglSubmit = $p['tgl_submit'];
                if (!empty($p['tgl_terbit'])) $tglTerbit = $p['tgl_terbit'];
            }

            $negara = $this->firstVal(
                $row->reg_negara,
                $row->pb_negara,
                $row->pb_negara_asal,
                $row->pb_asal_negara,
                $row->bahan_negara,
                $this->negaraFromHalalJson($row->pb_proses)
            );

            $masaBerlaku = $row->masa_berlaku_nie ?: ($tglTerbit ? (Carbon::parse($tglTerbit)->addYears(3)->format('Y-m-d')) : null);
            $bpomLabel   = $tglTerbit ? 'Selesai' : ($tglSubmit ? 'On Process' : '');

            $semua->push([
                'id'                       => $row->reg_id,
                'pb_id'                    => $row->pb_id,
                'pb_produk_id'             => $row->pb_produk_id,
                'kode'                     => $kode,
                'bahan_nama'               => $row->bahan_nama,
                'negara_nama'              => $negara,
                'approve_vendor_lama'      => $row->approve_vendor_lama ?? null,
                'source_tersedia'          => $row->source_tersedia ?? null,
                'perubahan_desain_kemasan' => $row->perubahan_desain ?? null,
                'on_process_bpom'          => $bpomLabel,
                'masa_berlaku_nie'         => $masaBerlaku,
                'brand'                    => $row->brand ?? null,
                'keterangan'               => $row->keterangan ?? null,
                'updated_at'               => $row->updated_at,
            ]);
        }

        // ===== Filter =====
        $q     = trim((string)$r->get('q',''));
        $negQ  = trim((string)$r->get('negara',''));
        $bpomQ = trim((string)$r->get('bpom',''));

        $filtered = $semua->filter(function($x) use($q,$negQ,$bpomQ){
            if ($q !== '') {
                $hay = strtolower(implode(' ', [
                    $x['kode'] ?? '', $x['bahan_nama'] ?? '', $x['negara_nama'] ?? '',
                    $x['approve_vendor_lama'] ?? '', $x['source_tersedia'] ?? '',
                    $x['perubahan_desain_kemasan'] ?? '', $x['on_process_bpom'] ?? '',
                    $x['masa_berlaku_nie'] ?? '', $x['brand'] ?? '', $x['keterangan'] ?? ''
                ]));
                if (strpos($hay, strtolower($q)) === false) return false;
            }
            if ($negQ!=='' && strcasecmp((string)($x['negara_nama'] ?? ''), (string)$negQ)!==0) return false;
            if ($bpomQ!=='') {
                $key = ($x['on_process_bpom'] ?? '')==='' ? 'belum'
                     : (stripos($x['on_process_bpom'],'proses')!==false ? 'proses'
                     : (stripos($x['on_process_bpom'],'selesai')!==false ? 'selesai' : 'lain'));
                if ($key!==$bpomQ) return false;
            }
            return true;
        })->values();

        $toObj = fn($col) => $col->map(fn($x) => (object)$x)->values();

        // ===== Panel produk (paginate 5) + komposisi halaman aktif =====
        $produkList = DB::table('produks as p')
            ->leftJoin(
                DB::raw('(SELECT produk_id, COUNT(*) AS jml FROM produk_bahan GROUP BY produk_id) pb'),
                'pb.produk_id',
                '=',
                'p.id'
            )
            ->select('p.id','p.kode','p.nama','p.brand', DB::raw('COALESCE(pb.jml,0) as jml_bahan'))
            ->orderBy('p.kode')
            ->paginate(5, ['*'], 'page_prod');

        $prodIds = collect($produkList->items())->pluck('id')->all();
        if (empty($prodIds)) $prodIds = [0];

        $komposisi = DB::table('produk_bahan as x')
            ->join('bahans as b','b.id','=','x.bahan_id')
            ->whereIn('x.produk_id', $prodIds)
            ->select('x.id as link_id','x.produk_id','b.nama as bahan_nama','x.qty','x.satuan','x.peran','x.urutan')
            ->orderBy('x.produk_id')->orderBy('x.urutan')
            ->get()
            ->groupBy('produk_id');

        // Dropdown negara
        $negMap = [];
        foreach ($semua->pluck('negara_nama')->filter()->unique()->sort() as $n) {
            $negMap[$n] = $n;
        }

        return view('registrasi.metrik.index', [
            'rows'       => $toObj($filtered),
            'semua'      => $toObj($filtered),
            'on_process' => $toObj($filtered->filter(fn($x)=> stripos(($x['on_process_bpom']??''),'proses')!==false)),
            'terbit'     => $toObj($filtered->filter(fn($x)=> stripos(($x['on_process_bpom']??''),'selesai')!==false)),
            'belum'      => $toObj($filtered->filter(fn($x)=> ($x['on_process_bpom']??'')==='')),
            'negaraList' => $negMap,
            'filter'     => ['q'=>$q,'negara'=>$negQ,'bpom'=>$bpomQ],

            // panel produk
            'produkList' => $produkList,
            'komposisi'  => $komposisi,
            'canEdit'    => $canEdit,
        ]);
    }

    /* ============================== EDIT ============================== */
    public function edit(int $id)
    {
        $tblR  = $this->tblReg;
        $tblPB = $this->tblPB;

        $has = function(string $col) use ($tblR) { return Schema::hasColumn($tblR, $col); };

        $regProsesCol = $this->firstExistingCol($tblR, ['proses','proses_json','proses_reg','proses_registrasi']);
        $perubahanCol = $this->firstExistingCol($tblR, ['perubahan_desain_kemasan','perubahan_desain']);

        $hasPbProses     = Schema::hasColumn($tblPB, 'proses');
        $hasPbNegara     = Schema::hasColumn($tblPB, 'negara');
        $hasPbNegaraAsal = Schema::hasColumn($tblPB, 'negara_asal');
        $hasPbAsalNegara = Schema::hasColumn($tblPB, 'asal_negara');
        $hasBhnNegara    = Schema::hasColumn('bahans', 'negara');

        $row = DB::table("$tblR as r")
            ->leftJoin("$tblPB as pb", 'pb.id', '=', 'r.trial_id')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->select('r.id','r.updated_at','pb.id as pb_id','pb.bahan_id','pb.ulang_ke', DB::raw('b.nama as bahan_nama'))
            ->selectRaw('pb.produk_id as pb_produk_id')
            ->selectRaw($regProsesCol ? "r.$regProsesCol as reg_proses" : 'NULL as reg_proses')
            ->selectRaw($perubahanCol ? "r.$perubahanCol as perubahan_desain_kemasan" : 'NULL as perubahan_desain_kemasan')
            ->selectRaw($has('approve_vendor_lama') ? 'r.approve_vendor_lama as approve_vendor_lama' : 'NULL as approve_vendor_lama')
            ->selectRaw($has('source_tersedia')     ? 'r.source_tersedia as source_tersedia'     : 'NULL as source_tersedia')
            ->selectRaw($has('keterangan')          ? 'r.keterangan as keterangan'                : 'NULL as keterangan')
            ->selectRaw($has('masa_berlaku_nie')    ? 'r.masa_berlaku_nie as masa_berlaku_nie'    : 'NULL as masa_berlaku_nie')
            ->selectRaw($has('brand')               ? 'r.brand as brand'                          : 'NULL as brand')
            ->selectRaw($has('negara')              ? 'r.negara as reg_negara'                    : 'NULL as reg_negara')
            ->selectRaw($has('hasil')               ? 'r.hasil as hasil'                          : 'NULL as hasil')
            ->selectRaw($has('tgl_nie_terbit')      ? 'r.tgl_nie_terbit as tgl_nie_terbit'        : 'NULL as tgl_nie_terbit')
            ->selectRaw($hasPbProses     ? 'pb.proses as pb_proses'           : 'NULL as pb_proses')
            ->selectRaw($hasPbNegara     ? 'pb.negara as pb_negara'           : 'NULL as pb_negara')
            ->selectRaw($hasPbNegaraAsal ? 'pb.negara_asal as pb_negara_asal' : 'NULL as pb_negara_asal')
            ->selectRaw($hasPbAsalNegara ? 'pb.asal_negara as pb_asal_negara' : 'NULL as pb_asal_negara')
            ->selectRaw($hasBhnNegara    ? 'b.negara as bahan_negara'         : 'NULL as bahan_negara')
            ->where('r.id', $id)
            ->first();

        abort_if(!$row, 404);

        $row->kode = $this->pbCode($row->bahan_id, $row->ulang_ke);

        $row->negara_nama = $this->firstVal(
            $row->reg_negara,
            $row->pb_negara,
            $row->pb_negara_asal,
            $row->pb_asal_negara,
            $row->bahan_negara,
            $this->negaraFromHalalJson($row->pb_proses)
        );

        $tglSubmit = null; $tglTerbit = null;
        foreach ($this->jsonArr($row->reg_proses) as $p) {
            if (!empty($p['tgl_submit'])) $tglSubmit = $p['tgl_submit'];
            if (!empty($p['tgl_terbit'])) $tglTerbit = $p['tgl_terbit'];
        }
        if (!$tglTerbit && !empty($row->tgl_nie_terbit)) $tglTerbit = $row->tgl_nie_terbit;
        $row->on_process_bpom = $tglTerbit ? 'Selesai' : ($tglSubmit ? 'On Process' : '');

        if (empty($row->masa_berlaku_nie) && $tglTerbit) {
            try { $row->masa_berlaku_nie = Carbon::parse($tglTerbit)->addYears(3)->format('Y-m-d'); } catch (\Throwable $e) {}
        }

        return view('registrasi.metrik.edit', compact('row'));
    }

    /* ============================== UPDATE ============================== */
    public function update(Request $r, int $id)
    {
        $upd = [];

        if (Schema::hasColumn($this->tblReg, 'approve_vendor_lama')) {
            $upd['approve_vendor_lama'] = $r->input('approve_vendor_lama');
        }
        if (Schema::hasColumn($this->tblReg, 'source_tersedia')) {
            $upd['source_tersedia'] = $r->input('source_tersedia');
        }
        $perubahanCol = $this->firstExistingCol($this->tblReg, ['perubahan_desain_kemasan','perubahan_desain']);
        if ($perubahanCol) {
            $upd[$perubahanCol] = $r->input('perubahan_desain_kemasan');
        }
        if (Schema::hasColumn($this->tblReg, 'masa_berlaku_nie')) {
            $val = $r->input('masa_berlaku_nie');
            if ($val) { try { $val = Carbon::parse($val)->format('Y-m-d'); } catch (\Throwable $e) {} }
            $upd['masa_berlaku_nie'] = $val ?: null;
        }
        if (Schema::hasColumn($this->tblReg, 'keterangan')) {
            $upd['keterangan'] = $r->input('keterangan');
        }

        if ($upd) {
            $upd['updated_at'] = now();
            DB::table($this->tblReg)->where('id',$id)->update($upd);
        }

        return redirect()->route('registrasi.metrik.edit', $id)->with('success','Metrik registrasi disimpan.');
    }

    /* ===================== KONFIRMASI TUJUAN PRODUK ===================== */
    public function confirmForm(int $id)
    {
        // Ambil NIE terbit/hasil dengan coalesce dinamis
        $exprTerbit = $this->coalesceRegPb('tgl_nie_terbit',
            ['tgl_nie_terbit','tgl_terbit_nie','tanggal_terbit_nie'],
            ['nie_tgl_terbit']
        );
        $exprHasil = $this->coalesceRegPb('hasil', ['hasil'], ['nie_hasil']);

        $row = DB::table($this->tblReg.' as r')
            ->leftJoin($this->tblPB.' as pb', 'pb.id', '=', 'r.trial_id')
            ->leftJoin('bahans as b', 'b.id', '=', 'pb.bahan_id')
            ->select(
                'r.id','r.proses','r.keterangan',
                'pb.id as pb_id','pb.bahan_id','pb.ulang_ke','pb.produk_id as pb_produk_id',
                DB::raw('b.nama as bahan_nama'),
                $exprTerbit,   // alias => tgl_nie_terbit
                $exprHasil     // alias => hasil
            )
            ->where('r.id', $id)->first();

        abort_if(!$row, 404);

        $kode  = $this->pbCode($row->bahan_id, $row->ulang_ke);
        $bahan = $row->bahan_nama;

        $produkList = DB::table('produks')
            ->orderBy('kode')->get()
            ->mapWithKeys(function ($p) {
                $label = ($p->kode ? $p->kode.' — ' : '').$p->nama.($p->brand ? ' ('.$p->brand.')' : '');
                return [$p->id => $label];
            });

        $komposisiBahan = DB::table('produk_bahan as x')
            ->join('produks as p','p.id','=','x.produk_id')
            ->select('x.id','x.produk_id','p.kode as produk_kode','p.nama as produk_nama','x.urutan')
            ->where('x.bahan_id',$row->bahan_id)
            ->orderBy('p.kode')->orderBy('x.urutan')->get();

        // boleh konfirmasi bila terbit / disetujui
        $boleh = false;
        if (!empty($row->tgl_nie_terbit) || trim((string)($row->hasil ?? ''))==='Disetujui') {
            $boleh = true;
        } else {
            foreach ($this->jsonArr($row->proses ?? null) as $p) {
                if (!empty($p['tgl_terbit'])) { $boleh = true; break; }
            }
        }

        // opsi peran (suggestion; tetap input bebas)
        $peranOptions = [
            'API','Eksipien','Pengikat','Pelicin','Pengisi','Disintegran',
            'Pelarut','Pewarna','Perisa','Pengawet'
        ];

        return view('registrasi.metrik.confirm', [
            'row'            => $row,
            'kode'           => $kode,
            'bahan'          => $bahan,
            'produkList'     => $produkList,
            'komposisiBahan' => $komposisiBahan,
            'boleh'          => $boleh,
            'peranOptions'   => $peranOptions,
        ]);
    }

    public function confirmUpdate(Request $r, int $id)
    {
        $r->validate([
            'produk_id' => 'required|integer|exists:produks,id',
            'peran'     => 'nullable|string|max:50',
        ]);

        $reg = DB::table($this->tblReg)->where('id',$id)->first();
        abort_if(!$reg, 404);

        $pb = DB::table($this->tblPB)->where('id', $reg->trial_id)->first();
        abort_if(!$pb, 404);

        $produkId = (int)$r->produk_id;
        $bahanId  = (int)$pb->bahan_id;
        $peran    = trim((string)$r->peran) ?: null;

        // tempel produk_id ke PB
        DB::table($this->tblPB)->where('id', $pb->id)->update([
            'produk_id'  => $produkId,
            'updated_at' => now(),
        ]);

        $exists = DB::table('produk_bahan')
            ->where('produk_id',$produkId)->where('bahan_id',$bahanId)->first();

        if ($exists) {
            if ($peran !== null) {
                DB::table('produk_bahan')
                    ->where('id', $exists->id)
                    ->update(['peran' => $peran, 'updated_at' => now()]);
            }
        } else {
            $nextOrder = (int)(DB::table('produk_bahan')->where('produk_id',$produkId)->max('urutan') ?? 0) + 1;
            DB::table('produk_bahan')->insert([
                'produk_id'  => $produkId,
                'bahan_id'   => $bahanId,
                'qty'        => null,
                'satuan'     => null,
                'peran'      => $peran,
                'urutan'     => $nextOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('registrasi.metrik.edit', $id)
            ->with('success', 'Bahan berhasil diarahkan ke produk. Peran sudah disimpan.');
    }

    /* ================= Komposisi (opsional, bila dipakai) ================ */
    public function komposisiAdd(Request $r, int $id)
    {
        $r->validate([
            'produk_id' => 'required|integer|exists:produks,id',
            'peran'     => 'nullable|string|max:50',
            'qty'       => 'nullable|numeric',
            'satuan'    => 'nullable|string|max:20',
            'urutan'    => 'nullable|integer|min:1',
        ]);

        $reg = DB::table($this->tblReg)->where('id',$id)->first();
        abort_if(!$reg, 404);
        $pb  = DB::table($this->tblPB)->where('id', $reg->trial_id)->first();
        abort_if(!$pb, 404);

        DB::table('produk_bahan')->upsert([[
            'produk_id'  => (int)$r->produk_id,
            'bahan_id'   => (int)$pb->bahan_id,
            'qty'        => $r->qty,
            'satuan'     => $r->satuan,
            'peran'      => $r->peran,
            'urutan'     => $r->urutan,
            'updated_at' => now(),
            'created_at' => now(),
        ]], ['produk_id','bahan_id'], ['qty','satuan','peran','urutan','updated_at']);

        return back()->with('success','Komposisi diperbarui.');
    }

    public function komposisiDelete(Request $r, int $id, int $linkId)
    {
        $reg = DB::table($this->tblReg)->where('id',$id)->first();
        abort_if(!$reg, 404);
        $pb  = DB::table($this->tblPB)->where('id', $reg->trial_id)->first();
        abort_if(!$pb, 404);

        DB::table('produk_bahan')->where('id',$linkId)->where('bahan_id',$pb->bahan_id)->delete();
        return back()->with('success','Komposisi dihapus.');
    }

    /* ====== Tambahan untuk panel Master Produk & Komposisi ====== */
    public function produkBahanDestroy(int $id)
    {
        DB::table('produk_bahan')->where('id',$id)->delete();
        return back()->with('success','Bahan dihapus dari komposisi.');
    }

    public function produkBahanConfirm(Request $r)
    {
        return back()->with('success','Perubahan komposisi dikonfirmasi.');
    }

    public function exportKomposisi(Request $r)
    {
        $rows = DB::table('produks as p')
            ->leftJoin('produk_bahan as x','x.produk_id','=','p.id')
            ->leftJoin('bahans as b','b.id','=','x.bahan_id')
            ->select(
                'p.kode as produk_kode','p.nama as produk_nama','p.brand',
                'x.urutan','b.nama as bahan','x.peran','x.qty','x.satuan'
            )
            ->orderBy('p.kode')->orderBy('x.urutan')
            ->get();

        $filename = 'komposisi_produk_'.now()->format('Ymd_His').'.csv';
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Produk Kode','Produk Nama','Brand','Urutan','Bahan','Peran','Qty','Satuan']);
        foreach ($rows as $r2) {
            fputcsv($out, [
                $r2->produk_kode, $r2->produk_nama, $r2->brand,
                $r2->urutan, $r2->bahan, $r2->peran, $r2->qty, $r2->satuan
            ]);
        }
        rewind($out); $csv = stream_get_contents($out); fclose($out);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /* ============== Quick Add Produk (dipakai modal) ============== */
    public function produkStore(Request $r)
    {
        $r->validate([
            'nama' => 'required|string|max:150',
            'brand'=> 'nullable|string|max:100',
            'kode' => 'nullable|string|max:20|unique:produks,kode',
        ]);

        // Auto-generate kode kalau kosong (PRD-xxx)
        $kode = trim((string)$r->kode);
        if ($kode === '') {
            $last = DB::table('produks')
                ->where('kode','like','PRD-%')
                ->orderBy('kode','desc')
                ->value('kode');
            $n = 0;
            if ($last && preg_match('/^PRD-(\d+)/', $last, $m)) $n = (int)$m[1];
            $kode = 'PRD-'.str_pad((string)($n+1), 3, '0', STR_PAD_LEFT);
        }

        DB::table('produks')->insert([
            'kode'       => $kode,
            'nama'       => $r->nama,
            'brand'      => $r->brand,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('registrasi.metrik')
            ->with('success', 'Produk ditambahkan.');
    }
}
