<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bahans', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('satuan_default', 20)->default('gr');
            $table->string('kategori_default', 50)->default('Bahan Aktif');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('bahans');
    }
};
