<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('produk_bahan', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            // kolom FK (samakan tipe dengan tabel induk: BIGINT UNSIGNED)
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('bahan_id');

            $table->decimal('qty', 12, 3)->nullable();
            $table->string('satuan', 20)->nullable();   // kg, g, L, pcs
            $table->string('peran', 50)->nullable();    // API, eksipien, coating, dll
            $table->unsignedSmallInteger('urutan')->nullable(); // buat sorting komposisi

            $table->timestamps();

            $table->unique(['produk_id', 'bahan_id']);
            $table->index(['produk_id', 'urutan']);
        });

        // Tambahkan FK setelah tabel jadi (lebih kompatibel)
        Schema::table('produk_bahan', function (Blueprint $table) {
            $table->foreign('produk_id')
                  ->references('id')->on('produks')
                  ->onDelete('cascade');

            $table->foreign('bahan_id')
                  ->references('id')->on('bahans')
                  ->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::table('produk_bahan', function (Blueprint $table) {
            // drop FK lebih aman sebelum drop table
            $table->dropForeign(['produk_id']);
            $table->dropForeign(['bahan_id']);
        });
        Schema::dropIfExists('produk_bahan');
    }
};
