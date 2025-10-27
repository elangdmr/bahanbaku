<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use Illuminate\Http\Request;

class BahanController extends Controller
{
   public function index(Request $r)
{
    $q   = trim($r->get('q', ''));
    $per = (int) $r->get('per_page', 15);

    $rows = Bahan::query()
        ->when($q !== '', fn($qb) => $qb->where('nama','like',"%{$q}%")
            ->orWhere('satuan_default','like',"%{$q}%")
            ->orWhere('kategori_default','like',"%{$q}%"))
        ->orderBy('nama')
        ->paginate($per > 0 ? $per : 15);

    // ini efeknya sama dengan withQueryString()
    $rows->appends($r->query());

    return view('bahan.index', ['rows' => $rows, 'q' => $q]);
}


    public function create()
    {
        return view('bahan.form', [
            'bahan'           => new Bahan(),
            'satuanOptions'   => Bahan::SATUAN,
            'kategoriOptions' => Bahan::KATEGORI,
            'isEdit'          => false,
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nama'             => 'required|string|max:150|unique:bahans,nama',
            'satuan_default'   => 'required|string|max:20',
            'kategori_default' => 'required|string|max:50',
        ]);

        Bahan::create($data);

        return redirect()->route('bahan.index')->with('ok', 'Bahan ditambahkan.');
    }

    public function edit(Bahan $bahan)
    {
        return view('bahan.form', [
            'bahan'           => $bahan,
            'satuanOptions'   => Bahan::SATUAN,
            'kategoriOptions' => Bahan::KATEGORI,
            'isEdit'          => true,
        ]);
    }

    public function update(Request $r, Bahan $bahan)
    {
        $data = $r->validate([
            'nama'             => 'required|string|max:150|unique:bahans,nama,' . $bahan->id,
            'satuan_default'   => 'required|string|max:20',
            'kategori_default' => 'required|string|max:50',
        ]);

        $bahan->update($data);

        return redirect()->route('bahan.index')->with('ok', 'Bahan diperbarui.');
    }

    public function destroy(Bahan $bahan)
    {
        $bahan->delete(); // soft delete
        return redirect()->route('bahan.index')->with('ok', 'Bahan dihapus.');
    }
}
