<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            // === SAMPLING PCH ===
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_sampling_permintaan')) {
                $table->date('tgl_sampling_permintaan')->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'est_sampling_diterima')) {
                $table->date('est_sampling_diterima')->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_sampling_dikirim')) {
                $table->date('tgl_sampling_dikirim')->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_sampling_diterima')) {
                $table->date('tgl_sampling_diterima')->nullable()->index();
            }
            // keterangan umum (sudah ada di sistem Anda, tapi jaga-jaga)
            if (!Schema::hasColumn('permintaan_bahan', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }

            // === TRIAL R&D ===
            if (!Schema::hasColumn('permintaan_bahan', 'trial_bahan')) {
                $table->json('trial_bahan')->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'uji_formulasi')) {
                $table->json('uji_formulasi')->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'uji_stabilitas')) {
                $table->json('uji_stabilitas')->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'uji_be')) {
                $table->json('uji_be')->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'uji_be_active')) {
                $table->boolean('uji_be_active')->default(false);
            }
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_selesai_trial')) {
                $table->date('tgl_selesai_trial')->nullable();
            }
            if (!Schema::hasColumn('permintaan_bahan', 'hasil_trial')) {
                $table->string('hasil_trial', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            // SAMPLING
            if (Schema::hasColumn('permintaan_bahan', 'tgl_sampling_permintaan')) $table->dropColumn('tgl_sampling_permintaan');
            if (Schema::hasColumn('permintaan_bahan', 'est_sampling_diterima'))   $table->dropColumn('est_sampling_diterima');
            if (Schema::hasColumn('permintaan_bahan', 'tgl_sampling_dikirim'))    $table->dropColumn('tgl_sampling_dikirim');
            if (Schema::hasColumn('permintaan_bahan', 'tgl_sampling_diterima'))   $table->dropColumn('tgl_sampling_diterima');
            // TRIAL
            if (Schema::hasColumn('permintaan_bahan', 'trial_bahan'))      $table->dropColumn('trial_bahan');
            if (Schema::hasColumn('permintaan_bahan', 'uji_formulasi'))    $table->dropColumn('uji_formulasi');
            if (Schema::hasColumn('permintaan_bahan', 'uji_stabilitas'))   $table->dropColumn('uji_stabilitas');
            if (Schema::hasColumn('permintaan_bahan', 'uji_be'))           $table->dropColumn('uji_be');
            if (Schema::hasColumn('permintaan_bahan', 'uji_be_active'))    $table->dropColumn('uji_be_active');
            if (Schema::hasColumn('permintaan_bahan', 'tgl_selesai_trial'))$table->dropColumn('tgl_selesai_trial');
            if (Schema::hasColumn('permintaan_bahan', 'hasil_trial'))      $table->dropColumn('hasil_trial');
        });
    }
};
