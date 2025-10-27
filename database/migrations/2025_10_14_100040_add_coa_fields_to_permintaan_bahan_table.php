<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            // Tambahkan kolom kalau belum ada
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_coa_diterima')) {
                $table->date('tgl_coa_diterima')->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'hasil_uji')) {
                $table->string('hasil_uji', 50)->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'keterangan')) {
                $table->string('keterangan', 500)->nullable();
            }

            // JSON kalau tersedia, fallback ke longText
            if (!Schema::hasColumn('permintaan_bahan', 'detail_uji')) {
                try {
                    $table->json('detail_uji')->nullable();
                } catch (\Throwable $e) {
                    $table->longText('detail_uji')->nullable();
                }
            }

            // index status untuk mempercepat whereIn/where
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn($i) => $i->getName(), $sm->listTableIndexes('permintaan_bahan'));
            if (!in_array('permintaan_bahan_status_index', $indexes)) {
                $table->index('status', 'permintaan_bahan_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (Schema::hasColumn('permintaan_bahan', 'detail_uji')) {
                $table->dropColumn('detail_uji');
            }
            if (Schema::hasColumn('permintaan_bahan', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
            if (Schema::hasColumn('permintaan_bahan', 'hasil_uji')) {
                $table->dropColumn('hasil_uji');
            }
            if (Schema::hasColumn('permintaan_bahan', 'tgl_coa_diterima')) {
                $table->dropColumn('tgl_coa_diterima');
            }
            // drop index bila ada
            try { $table->dropIndex('permintaan_bahan_status_index'); } catch (\Throwable $e) {}
        });
    }
};
