<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bahans', function (Blueprint $table) {
            if (!Schema::hasColumn('bahans', 'satuan_default')) {
                $table->string('satuan_default', 20)->default('gr')->after('nama');
            }
            if (!Schema::hasColumn('bahans', 'kategori_default')) {
                $table->string('kategori_default', 50)->default('Bahan Aktif')->after('satuan_default');
            }
        });
    }

    public function down(): void {
        Schema::table('bahans', function (Blueprint $table) {
            if (Schema::hasColumn('bahans', 'kategori_default')) $table->dropColumn('kategori_default');
            if (Schema::hasColumn('bahans', 'satuan_default'))   $table->dropColumn('satuan_default');
        });
    }
};
