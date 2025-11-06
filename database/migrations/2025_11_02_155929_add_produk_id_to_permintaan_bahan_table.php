<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('permintaan_bahan') && !Schema::hasColumn('permintaan_bahan','produk_id')) {
            Schema::table('permintaan_bahan', function (Blueprint $table) {
                // nullable supaya data lama aman
                $table->unsignedBigInteger('produk_id')->nullable()->after('bahan_id');

                // FK → produks.id (hapus set null saat produk dihapus)
                $table->foreign('produk_id')
                      ->references('id')->on('produks')
                      ->nullOnDelete();

                $table->index('produk_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permintaan_bahan') && Schema::hasColumn('permintaan_bahan','produk_id')) {
            Schema::table('permintaan_bahan', function (Blueprint $table) {
                // lepas FK kalau ada, lalu drop kolom
                try { $table->dropForeign(['produk_id']); } catch (\Throwable $e) {}
                $table->dropColumn('produk_id');
            });
        }
    }
};
