<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BahanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Data contoh (boleh ubah sesuka hati)
        $items = [
            ['kode' => 'BAH-001', 'nama' => 'Aquadest',    'satuan' => 'L',  'kategori' => 'Bahan Penolong'],
            ['kode' => 'BAH-002', 'nama' => 'Vitamin C',   'satuan' => 'kg', 'kategori' => 'Bahan Aktif'],
            ['kode' => 'BAH-003', 'nama' => 'Lactose',     'satuan' => 'kg', 'kategori' => 'Bahan Penolong'],
            ['kode' => 'BAH-004', 'nama' => 'Paracetamol', 'satuan' => 'kg', 'kategori' => 'Bahan Aktif'],
            ['kode' => 'BAH-005', 'nama' => 'Citric Acid', 'satuan' => 'kg', 'kategori' => 'Bahan Penolong'],
        ];

        // Deteksi nama kolom yang ada di tabel bahans
        $hasSatuanDefault   = Schema::hasColumn('bahans', 'satuan_default');
        $hasDefaultSatuan   = Schema::hasColumn('bahans', 'default_satuan');
        $hasKategoriDefault = Schema::hasColumn('bahans', 'kategori_default');
        $hasKategori        = Schema::hasColumn('bahans', 'kategori');
        $hasKode            = Schema::hasColumn('bahans', 'kode');
        $hasStok            = Schema::hasColumn('bahans', 'stok');

        $rows = [];
        foreach ($items as $it) {
            $row = [
                'nama'       => $it['nama'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Map ke kolom yang tersedia
            if ($hasSatuanDefault)   $row['satuan_default']   = $it['satuan'];
            if ($hasDefaultSatuan)   $row['default_satuan']   = $it['satuan'];
            if ($hasKategoriDefault) $row['kategori_default'] = $it['kategori'];
            if ($hasKategori)        $row['kategori']         = $it['kategori'];
            if ($hasKode)            $row['kode']             = $it['kode'];
            if ($hasStok)            $row['stok']             = 0;

            $rows[] = $row;
        }

        DB::table('bahans')->insert($rows);
    }
}
