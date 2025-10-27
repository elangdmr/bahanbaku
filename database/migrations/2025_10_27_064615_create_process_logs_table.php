<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_logs', function (Blueprint $t) {
            $t->bigIncrements('id');

            // Identitas permintaan/bahan
            $t->string('kode', 50)->index();           // contoh: PB-01
            $t->string('bahan_nama')->nullable();

            // Sumber modul & referensi
            $t->string('modul', 50)->index();          // permintaan, purchasing_vendor, uji_coa, dll
            $t->unsignedBigInteger('modul_id')->nullable()->index();

            // Detail event
            $t->dateTime('waktu')->index();            // waktu event
            $t->string('peristiwa')->nullable();       // "Permintaan dibuat", "COA diterima", dst
            $t->string('status', 50)->nullable()->index();
            $t->string('status_label', 100)->nullable();
            $t->string('status_badge', 50)->nullable();
            $t->text('keterangan')->nullable();        // catatan: "by admin@mail.io", dll

            // Aktor
            $t->unsignedBigInteger('aktor_id')->nullable();
            $t->string('aktor_nama', 100)->nullable();
            $t->string('aktor_email', 191)->nullable(); // 191 aman untuk utf8mb4

            $t->timestamps();

            // Index tambahan yang sering dipakai untuk listing/detail
            $t->index(['kode', 'modul', 'waktu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_logs');
    }
};
