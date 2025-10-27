<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('permintaan_bahan', 'hasil_trial')) {
                $table->string('hasil_trial', 60)->nullable()->after('status');
            }
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_selesai_trial')) {
                $table->date('tgl_selesai_trial')->nullable()->after('hasil_trial');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (Schema::hasColumn('permintaan_bahan', 'tgl_selesai_trial')) {
                $table->dropColumn('tgl_selesai_trial');
            }
            if (Schema::hasColumn('permintaan_bahan', 'hasil_trial')) {
                $table->dropColumn('hasil_trial');
            }
        });
    }
};
