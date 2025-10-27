<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek dulu supaya tidak error kalau kolom sudah ada
        if (! Schema::hasColumn('bahans', 'deleted_at')) {
            Schema::table('bahans', function (Blueprint $table) {
                // tambahkan kolom deleted_at setelah updated_at
                $table->softDeletes()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        // Hapus hanya jika memang ada
        if (Schema::hasColumn('bahans', 'deleted_at')) {
            Schema::table('bahans', function (Blueprint $table) {
                // ini sama dengan $table->dropColumn('deleted_at');
                $table->dropSoftDeletes();
            });
        }
    }
};
