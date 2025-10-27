<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            // Tambah field vendor/COA (nullable)
            if (!Schema::hasColumn('permintaan_bahan','pabrik_pembuat')) {
                $table->string('pabrik_pembuat')->nullable()->after('bahan_id');
            }
            if (!Schema::hasColumn('permintaan_bahan','negara_asal')) {
                $table->string('negara_asal')->nullable()->after('pabrik_pembuat');
            }
            if (!Schema::hasColumn('permintaan_bahan','distributor')) {
                $table->string('distributor')->nullable()->after('negara_asal');
            }
            if (!Schema::hasColumn('permintaan_bahan','tgl_permintaan_coa')) {
                $table->date('tgl_permintaan_coa')->nullable()->after('distributor');
            }
            if (!Schema::hasColumn('permintaan_bahan','est_coa_diterima')) {
                $table->date('est_coa_diterima')->nullable()->after('tgl_permintaan_coa');
            }
            if (!Schema::hasColumn('permintaan_bahan','tgl_coa_diterima')) {
                $table->date('tgl_coa_diterima')->nullable()->after('est_coa_diterima');
            }
        });

        // Ubah kolom status jadi VARCHAR biar bisa simpan 'Purchasing' & 'Proses Uji COA'
        // (tanpa perlu doctrine/dbal)
        DB::statement("ALTER TABLE permintaan_bahan MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            $cols = [
                'pabrik_pembuat','negara_asal','distributor',
                'tgl_permintaan_coa','est_coa_diterima','tgl_coa_diterima'
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('permintaan_bahan', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        // (opsional) kalau dulu kolomnya ENUM dan mau revert, lakukan manual sesuai enum awal.
        // DB::statement("ALTER TABLE permintaan_bahan MODIFY COLUMN status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending'");
    }
};
