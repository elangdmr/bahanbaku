<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // --- siapkan minimal 8 bahan (nama saja sudah cukup) ---
            $defaultBahans = [
                'Lactose Monohydrate','Microcrystalline Cellulose','Povidone K30',
                'Magnesium Stearate','Starch 1500','HPMC','Talc','Silicon Dioxide'
            ];

            foreach ($defaultBahans as $nama) {
                $exists = DB::table('bahans')->where('nama', $nama)->exists();
                if (!$exists) {
                    // kolom wajib yang pasti ada di project kamu: nama
                    DB::table('bahans')->insert(['nama' => $nama]);
                }
            }

            $bahanAll = DB::table('bahans')->orderBy('id')->get();

            // --- 5 produk sample ---
            $produkData = [
                ['kode'=>'PRD-001','nama'=>'Paracetamol 500 mg Tablet','brand'=>'SAMCO','deskripsi'=>'Analgesik-antipiretik'],
                ['kode'=>'PRD-002','nama'=>'Vitamin C 500 mg Tablet','brand'=>'SAMCO','deskripsi'=>'Suplemen vitamin C'],
                ['kode'=>'PRD-003','nama'=>'Amoxicillin 500 mg Capsule','brand'=>'SAMCO','deskripsi'=>'Antibiotik'],
                ['kode'=>'PRD-004','nama'=>'Cough Syrup 60 mL','brand'=>'SAMCO','deskripsi'=>'Syrup batuk'],
                ['kode'=>'PRD-005','nama'=>'Omeprazole 20 mg Capsule','brand'=>'SAMCO','deskripsi'=>'Antasida'],
            ];

            foreach ($produkData as $pd) {
                $produk = DB::table('produks')->where('kode', $pd['kode'])->first();
                if (!$produk) {
                    $id = DB::table('produks')->insertGetId([
                        'kode'=>$pd['kode'], 'nama'=>$pd['nama'],
                        'brand'=>$pd['brand'] ?? null, 'deskripsi'=>$pd['deskripsi'] ?? null,
                        'created_at'=>now(), 'updated_at'=>now(),
                    ]);
                    $produk = (object)['id'=>$id];
                }

                // ambil 5 bahan bergilir, isi pivot produk_bahan (qty/satuan opsional)
                for ($k=0; $k<5; $k++) {
                    $bahan = $bahanAll[($produk->id + $k) % $bahanAll->count()];
                    DB::table('produk_bahan')->updateOrInsert(
                        ['produk_id'=>$produk->id, 'bahan_id'=>$bahan->id],
                        [
                            'qty'=>100 + ($k*10), 'satuan'=>'kg', 'peran'=>$k===0?'API':'Eksipien',
                            'urutan'=>$k+1, 'updated_at'=>now(), 'created_at'=>now(),
                        ]
                    );
                }
            }
        });
    }
}
