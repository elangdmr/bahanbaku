<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('produks', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id(); // BIGINT UNSIGNED
            $table->string('kode', 20)->unique();
            $table->string('nama', 150);
            $table->string('brand', 100)->nullable();
            $table->string('kategori', 100)->nullable();
            $table->text('deskripsi')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('produks');
    }
};
