<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registrasi_nie', function (Blueprint $table) {
            $table->bigIncrements('id');

            // relasi ke permintaan_bahan
            $table->unsignedBigInteger('trial_id')->unique()->comment('FK -> permintaan_bahan.id');
            $table->foreign('trial_id')->references('id')->on('permintaan_bahan')->cascadeOnDelete();

            // info ringkas untuk tampilan
            $table->string('kode', 20)->index();               // PB-xx(.n)
            $table->string('bahan_nama', 150)->nullable();

            // dari hasil konfirmasi trial
            $table->date('tgl_trial_selesai')->nullable();

            // proses registrasi
            $table->date('tgl_nie_submit')->nullable();
            $table->date('tgl_nie_terbit')->nullable();
            $table->string('status_dokumen', 50)->nullable();  // Registrasi / Dokumen Lengkap / Belum Lengkap / Tidak Lengkap

            // konfirmasi akhir registrasi
            $table->string('registrasi_nie', 100)->nullable();
            $table->date('tgl_verifikasi')->nullable();
            $table->enum('hasil', ['Disetujui','Perlu Revisi','Ditolak'])->nullable();

            $table->string('keterangan', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('registrasi_nie', function (Blueprint $table) {
            $table->dropForeign(['trial_id']);
        });
        Schema::dropIfExists('registrasi_nie');
    }
};
