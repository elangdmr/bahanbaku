<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uji_coa_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permintaan_id'); // relasi ke permintaan_bahan
            // 1 atau 2 kali pengujian; simpan label sederhana
            $table->enum('jenis_pengujian', ['Pengujian Pertama','Pengujian Kedua'])->default('Pengujian Pertama');
            $table->enum('hasil', ['Lulus','Tidak Lulus'])->nullable(); // hasil tiap pengujian
            $table->text('keterangan')->nullable();
            $table->date('mulai_pengujian')->nullable();
            $table->timestamps();

            $table->foreign('permintaan_id')
                ->references('id')->on('permintaan_bahan')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uji_coa_tests');
    }
};
