<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BahanBakuController extends Controller
{
    /* =================== Konstanta / Konfigurasi =================== */
    protected string  $permintaanTable = 'permintaan_bahan';
    protected ?string $bahanTable      = 'bahans';

    protected array $satuans      = ['gr','kg','ml','L','pcs'];
    protected array $kategoriOpts = ['Bahan Aktif','Bahan Pembantu','Lainnya'];

    // Status lintas modul (SAMAKAN dengan controller lain)
    private const STATUS_PENDING = 'Pending';
    private const STATUS_PURCH   = 'Purchasing Vendor';
    private const STATUS_COA     = 'Proses Uji COA';
    private const STATUS_APPROVE = 'Approved';
    private const STATUS_REJECT  = 'Rejected';

    /* =================== LIST (Pending) =================== */
    /** Tampilkan daftar permintaan yang masih Pending */
    public function index()
    {
        $q = DB::table($this->permintaanTable)
            ->orderByDesc("{$this->permintaanTable}.created_at")
            // pakai TRIM supaya kebal spasi tak sengaja
            ->whereRaw("TRIM({$this->permintaanTable}.status) = ?", [self::STATUS_PENDING]);

        if (!empty($this->bahanTable)) {
            $q->leftJoin($this->bahanTable, "{$this->bahanTable}.id", '=', "{$this->permintaanTable}.bahan_id")
              ->addSelect("{$this->permintaanTable}.*", "{$this->bahanTable}.nama as bahan_nama");
        } else {
            $q->select("{$this->permintaanTable}.*");
        }

        $requests = $q->paginate(10);

        $bahans = !empty($this->bahanTable)
            ? DB::table($this->bahanTable)->orderBy('nama')->get()
            : collect();

        return view('permintaan_bahan.show_permintaan', [
            'requests'     => $requests,
            'bahans'       => $bahans,
            'satuans'      => $this->satuans,
            'kategoriOpts' => $this->kategoriOpts,
        ]);
    }

    /* =================== CREATE (Form) =================== */
    /** Halaman tambah permintaan → GET /permintaan-bahan-baku/create */
    public function create()
    {
        $bahans = !empty($this->bahanTable)
            ? DB::table($this->bahanTable)->orderBy('nama')->get()
            : collect();

        return view('permintaan_bahan.create_permintaan', [
            'bahans'       => $bahans,
            'satuans'      => $this->satuans,
            'kategoriOpts' => $this->kategoriOpts,
        ]);
    }

    /* =================== STORE =================== */
    /** Simpan permintaan baru */
    public function store(Request $request)
    {
        $request->validate([
            'bahan_id'          => ['nullable','integer'],
            'jumlah'            => ['required','numeric','min:0'],
            'satuan'            => ['required','string','max:20'],
            'kategori'          => ['required','string','max:50'],
            'tanggal_kebutuhan' => ['required','date'],
            'alasan'            => ['nullable','string','max:1000'],
            'status'            => ['nullable','in:Pending,Purchasing,Purchasing Vendor,Approved,Rejected'],
        ]);

        $payload = [
            'bahan_id'          => $request->bahan_id,
            'jumlah'            => $request->jumlah,
            'satuan'            => $request->satuan,
            'kategori'          => $request->kategori,
            'tanggal_kebutuhan' => $request->tanggal_kebutuhan,
            'alasan'            => $request->alasan,
            'status'            => $request->input('status', self::STATUS_PENDING),
            'user_id'           => Auth::id(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ];

        // Inisialisasi ulang_ke = 0 bila kolomnya ada
        if (Schema::hasColumn($this->permintaanTable, 'ulang_ke') && !isset($payload['ulang_ke'])) {
            $payload['ulang_ke'] = 0;
        }

        DB::table($this->permintaanTable)->insert($payload);

        return redirect()->route('show-permintaan')->with('success', 'Permintaan berhasil diajukan.');
    }

    /* =================== EDIT (Form) =================== */
    public function edit($id)
    {
        $q = DB::table($this->permintaanTable)
            ->where("{$this->permintaanTable}.id", $id);

        if (!empty($this->bahanTable)) {
            $q->leftJoin($this->bahanTable, "{$this->bahanTable}.id", '=', "{$this->permintaanTable}.bahan_id")
              ->addSelect("{$this->permintaanTable}.*", "{$this->bahanTable}.nama as bahan_nama");
        } else {
            $q->select("{$this->permintaanTable}.*");
        }

    $permintaan = $q->first();
    abort_if(is_null($permintaan), 404);

        $bahans = !empty($this->bahanTable)
            ? DB::table($this->bahanTable)->orderBy('nama')->get()
            : collect();

        return view('permintaan_bahan.edit_permintaan', [
            'permintaan'   => $permintaan,
            'bahans'       => $bahans,
            'satuans'      => $this->satuans,
            'kategoriOpts' => $this->kategoriOpts,
        ]);
    }

    /* =================== UPDATE =================== */
    public function update(Request $request, $id)
    {
        $request->validate([
            'bahan_id'          => ['nullable','integer'],
            'jumlah'            => ['required','numeric','min:0'],
            'satuan'            => ['required','string','max:20'],
            'kategori'          => ['required','string','max:50'],
            'tanggal_kebutuhan' => ['required','date'],
            'alasan'            => ['nullable','string','max:1000'],
            'status'            => ['required','in:Pending,Purchasing,Purchasing Vendor,Approved,Rejected'],
        ]);

        DB::table($this->permintaanTable)->where('id', $id)->update([
            'bahan_id'          => $request->bahan_id,
            'jumlah'            => $request->jumlah,
            'satuan'            => $request->satuan,
            'kategori'          => $request->kategori,
            'tanggal_kebutuhan' => $request->tanggal_kebutuhan,
            'alasan'            => $request->alasan,
            'status'            => $request->status,
            'updated_at'        => now(),
        ]);

        return redirect()->route('show-permintaan')->with('success', 'Permintaan diperbarui.');
    }

    /* =================== ACCEPT =================== */
    /** Forward ke Purchasing (ubah status ke “Purchasing Vendor”) */
    public function accept($id)
    {
        // Siapkan payload update
        $update = [
            'status'     => self::STATUS_PURCH,
            'updated_at' => now(),
        ];
        // Jika kolom ulang_ke ada, pastikan tidak NULL (set 0 ketika NULL)
        if (Schema::hasColumn($this->permintaanTable, 'ulang_ke')) {
            $update['ulang_ke'] = DB::raw('COALESCE(ulang_ke, 0)');
        }

        // Update hanya jika masih Pending → cegah double proses
        $affected = DB::table($this->permintaanTable)
            ->where('id', $id)
            ->whereRaw("TRIM(status) = ?", [self::STATUS_PENDING])
            ->update($update);

        if (!$affected) {
            return back()->with('error', 'Permintaan tidak ditemukan atau sudah diproses.');
        }

        return redirect()
            ->route('purch-vendor.index', ['tab' => 'pending'])
            ->with('success', 'Permintaan diteruskan ke Purchasing.');
    }

    /* ===== Alias kompatibilitas (opsional) ===== */
    public function showJadwal()                 { return $this->index(); }
    public function editJadwal($id)              { return $this->edit($id); }
    public function updateJadwal(Request $r,$id) { return $this->update($r,$id); }
}
