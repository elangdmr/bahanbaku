<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_pengajuan')) {
                $table->date('tgl_pengajuan')->nullable()->after('tgl_coa_diterima');
            }
            if (!Schema::hasColumn('permintaan_bahan', 'proses')) {
                // pilih salah satu:
                $table->json('proses')->nullable()->after('tgl_pengajuan');
                // atau: $table->longText('proses')->nullable()->after('tgl_pengajuan');
            }
            if (!Schema::hasColumn('permintaan_bahan', 'hasil_halal')) {
                $table->string('hasil_halal', 30)->nullable()->after('proses');
            }
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_verifikasi')) {
                $table->date('tgl_verifikasi')->nullable()->after('hasil_halal');
            }
            if (!Schema::hasColumn('permintaan_bahan', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }

            // perbesar status bila perlu
            if (Schema::hasColumn('permintaan_bahan', 'status')) {
                $table->string('status', 50)->nullable(false)->change();
            }
        });
    }

    public function down(): void {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (Schema::hasColumn('permintaan_bahan', 'tgl_verifikasi')) $table->dropColumn('tgl_verifikasi');
            if (Schema::hasColumn('permintaan_bahan', 'hasil_halal'))   $table->dropColumn('hasil_halal');
            if (Schema::hasColumn('permintaan_bahan', 'proses'))        $table->dropColumn('proses');
            if (Schema::hasColumn('permintaan_bahan', 'tgl_pengajuan')) $table->dropColumn('tgl_pengajuan');
            // biasanya jangan drop 'keterangan' kalau sudah dipakai modul lain
            // if (Schema::hasColumn('permintaan_bahan', 'keterangan')) $table->dropColumn('keterangan');
        });
    }
};

