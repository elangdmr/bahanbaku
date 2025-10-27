<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            // Tanggal permintaan sampling dibuat
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_sampling_permintaan')) {
                $table->date('tgl_sampling_permintaan')->nullable()->after('tgl_verifikasi');
            }

            // Estimasi kapan sampling diterima
            if (!Schema::hasColumn('permintaan_bahan', 'est_sampling_diterima')) {
                $table->date('est_sampling_diterima')->nullable()->after('tgl_sampling_permintaan');
            }

            // Tanggal sampling dikirim (ke QC/PCH)
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_sampling_dikirim')) {
                $table->date('tgl_sampling_dikirim')->nullable()->after('est_sampling_diterima');
            }

            // Realisasi tanggal sampling diterima (penentu pindah ke riwayat Sampling)
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_sampling_diterima')) {
                $table->date('tgl_sampling_diterima')->nullable()->after('tgl_sampling_dikirim');
            }
        });

        // NOTE: tidak perlu index khusus. Kalau mau, bisa tambahkan:
        // Schema::table('permintaan_bahan', function (Blueprint $table) {
        //     $table->index('tgl_sampling_diterima', 'pb_sampling_diterima_idx');
        // });
    }

    public function down(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (Schema::hasColumn('permintaan_bahan', 'tgl_sampling_permintaan')) {
                $table->dropColumn('tgl_sampling_permintaan');
            }
            if (Schema::hasColumn('permintaan_bahan', 'est_sampling_diterima')) {
                $table->dropColumn('est_sampling_diterima');
            }
            if (Schema::hasColumn('permintaan_bahan', 'tgl_sampling_dikirim')) {
                $table->dropColumn('tgl_sampling_dikirim');
            }
            if (Schema::hasColumn('permintaan_bahan', 'tgl_sampling_diterima')) {
                $table->dropColumn('tgl_sampling_diterima');
            }
        });
    }
};
