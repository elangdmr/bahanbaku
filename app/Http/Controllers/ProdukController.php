<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    /** Dapatkan kode berikutnya: PRD-001, PRD-002, ... */
    private function nextKode(): string
    {
        $last = DB::table('produks')
            ->where('kode', 'like', 'PRD-%')
            ->orderByDesc('id')
            ->value('kode');

        $num = 0;
        if ($last && preg_match('/PRD-(\d+)/', $last, $m)) {
            $num = (int)$m[1];
        }
        return 'PRD-' . str_pad((string)($num + 1), 3, '0', STR_PAD_LEFT);
    }

    /** List master produk */
    public function index()
    {
        $rows = DB::table('produks')
            ->select('id','kode','nama','brand','created_at')
            ->orderBy('kode')
            ->paginate(20);

        return view('produk.index', [
            'rows' => $rows,
        ]);
    }

    /** Form create */
    public function create()
    {
        return view('produk.form', [
            'mode' => 'create',
            'row'  => (object)[
                'id'   => null,
                'kode' => $this->nextKode(),
                'nama' => '',
                'brand'=> '',
            ],
        ]);
    }

    /** Simpan baru (dipakai juga oleh quick-add modal) */
    public function store(Request $r)
    {
        $r->validate([
            'kode'  => 'nullable|string|max:20',
            'nama'  => 'required|string|max:200',
            'brand' => 'nullable|string|max:100',
        ]);

        $kode = trim((string)$r->input('kode'));
        if ($kode === '') $kode = $this->nextKode();

        // pastikan unik
        if (DB::table('produks')->where('kode', $kode)->exists()) {
            return back()->withErrors(['kode' => 'Kode sudah dipakai.'])->withInput();
        }

        DB::table('produks')->insert([
            'kode'       => $kode,
            'nama'       => $r->input('nama'),
            'brand'      => $r->input('brand'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Jika datang dari halaman metrik, balik ke sana.
        if ($r->has('redirect_to_metrik')) {
            return redirect()->route('registrasi.metrik')
                ->with('success', "Produk {$kode} berhasil ditambahkan.");
        }

        return redirect()->route('produk.index')
            ->with('success', "Produk {$kode} berhasil ditambahkan.");
    }

    /** Form edit */
    public function edit(int $id)
    {
        $row = DB::table('produks')->where('id', $id)->first();
        abort_if(!$row, 404);

        return view('produk.form', [
            'mode' => 'edit',
            'row'  => $row,
        ]);
    }

    /** Update */
    public function update(Request $r, int $id)
    {
        $r->validate([
            'kode'  => 'required|string|max:20',
            'nama'  => 'required|string|max:200',
            'brand' => 'nullable|string|max:100',
        ]);

        // unik kecuali dirinya sendiri
        $exists = DB::table('produks')
            ->where('kode', $r->input('kode'))
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['kode' => 'Kode sudah dipakai.'])->withInput();
        }

        DB::table('produks')->where('id',$id)->update([
            'kode'       => $r->input('kode'),
            'nama'       => $r->input('nama'),
            'brand'      => $r->input('brand'),
            'updated_at' => now(),
        ]);

        return redirect()->route('produk.index')->with('success','Produk diperbarui.');
    }

    /** Hapus */
    public function destroy(int $id)
    {
        DB::table('produks')->where('id',$id)->delete();
        return back()->with('success','Produk dihapus.');
    }
}
