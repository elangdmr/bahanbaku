<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Tambah kolom-kolom dengan anchor "after" yang aman
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_coa_diterima')) {
                $col = $table->date('tgl_coa_diterima')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'est_coa_diterima')) {
                    $col->after('est_coa_diterima');
                }
            }

            if (!Schema::hasColumn('permintaan_bahan', 'hasil_uji')) {
                $col = $table->string('hasil_uji', 50)->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'tgl_coa_diterima')) {
                    $col->after('tgl_coa_diterima');
                }
            }

            if (!Schema::hasColumn('permintaan_bahan', 'keterangan')) {
                $col = $table->string('keterangan', 500)->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'hasil_uji')) {
                    $col->after('hasil_uji');
                }
            }

            if (!Schema::hasColumn('permintaan_bahan', 'detail_uji')) {
                $col = $table->json('detail_uji')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'keterangan')) {
                    $col->after('keterangan');
                }
            }

            // kolom status opsional
            if (!Schema::hasColumn('permintaan_bahan', 'status')) {
                $col = $table->string('status', 50)->default('Pending');
                if (Schema::hasColumn('permintaan_bahan', 'alasan')) {
                    $col->after('alasan');
                }
            }
        });

        // Buat index hanya jika belum ada
        if (!$this->indexExists('permintaan_bahan', 'permintaan_bahan_status_index')
            && Schema::hasColumn('permintaan_bahan', 'status')) {
            Schema::table('permintaan_bahan', function (Blueprint $table) {
                $table->index('status', 'permintaan_bahan_status_index');
            });
        }
    }

    public function down(): void
    {
        // Drop index kalau ada
        if ($this->indexExists('permintaan_bahan', 'permintaan_bahan_status_index')) {
            Schema::table('permintaan_bahan', function (Blueprint $table) {
                $table->dropIndex('permintaan_bahan_status_index');
            });
        }

        // Drop kolom-kolom baru (kolom 'status' sengaja TIDAK di-drop, mengikuti kode lama)
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            foreach (['detail_uji', 'keterangan', 'hasil_uji', 'tgl_coa_diterima'] as $col) {
                if (Schema::hasColumn('permintaan_bahan', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    // Helper cek index
    private function indexExists(string $table, string $index): bool
    {
        $sql = "SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND INDEX_NAME = ?
                LIMIT 1";
        return !empty(DB::select($sql, [$table, $index]));
    }
};
