<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Jika tabel belum ada, biarkan saja (no-op)
        if (!Schema::hasTable('permintaan_bahan')) {
            return;
        }

        // PASS 1 — pabrik_pembuat (letakkan setelah 'alasan' jika ada)
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('permintaan_bahan', 'pabrik_pembuat')) {
                $col = $table->string('pabrik_pembuat')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'alasan')) {
                    $col->after('alasan');
                }
            }
        });

        // PASS 2 — negara_asal & distributor (setelah kolom sebelumnya jika ada)
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('permintaan_bahan', 'negara_asal')) {
                $col = $table->string('negara_asal', 100)->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'pabrik_pembuat')) {
                    $col->after('pabrik_pembuat');
                }
            }
            if (!Schema::hasColumn('permintaan_bahan', 'distributor')) {
                $col = $table->string('distributor')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'negara_asal')) {
                    $col->after('negara_asal');
                }
            }
        });

        // PASS 3 — tgl_permintaan_coa, est_coa_diterima, tgl_coa_diterima
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_permintaan_coa')) {
                $col = $table->date('tgl_permintaan_coa')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'distributor')) {
                    $col->after('distributor');
                }
            }
            if (!Schema::hasColumn('permintaan_bahan', 'est_coa_diterima')) {
                $col = $table->date('est_coa_diterima')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'tgl_permintaan_coa')) {
                    $col->after('tgl_permintaan_coa');
                }
            }
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_coa_diterima')) {
                $col = $table->date('tgl_coa_diterima')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'est_coa_diterima')) {
                    $col->after('est_coa_diterima');
                }
            }
        });

        // CATATAN: Jangan buat index di sini untuk menghindari duplicate index.
        // Kalau butuh index, buat migration terpisah dan cek dulu keberadaannya.
    }

    public function down(): void
    {
        if (!Schema::hasTable('permintaan_bahan')) {
            return;
        }

        Schema::table('permintaan_bahan', function (Blueprint $table) {
            foreach ([
                'tgl_coa_diterima',
                'est_coa_diterima',
                'tgl_permintaan_coa',
                'distributor',
                'negara_asal',
                'pabrik_pembuat',
            ] as $col) {
                if (Schema::hasColumn('permintaan_bahan', $col)) {
                    $table->dropColumn($col);
                }
            }

            // Kalau sebelumnya kamu sempat membuat index,
            // dropIndex di sini hanya jika memang yakin nama index-nya:
            // try { $table->dropIndex('permintaan_bahan_status_created_at_index'); } catch (\Throwable $e) {}
            // try { $table->dropIndex('permintaan_bahan_status_index'); } catch (\Throwable $e) {}
        });
    }
};
