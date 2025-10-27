<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            // Tambah kolom hanya jika belum ada
            if (!Schema::hasColumn('permintaan_bahan', 'ulang_ke')) {
                $col = $table->unsignedInteger('ulang_ke')->default(0);
                // taruh setelah 'status' jika kolomnya ada
                if (Schema::hasColumn('permintaan_bahan', 'status')) {
                    $col->after('status');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (Schema::hasColumn('permintaan_bahan', 'ulang_ke')) {
                $table->dropColumn('ulang_ke');
            }
        });
    }
};
