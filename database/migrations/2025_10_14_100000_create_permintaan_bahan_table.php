<?php

// database/migrations/2025_10_14_000100_create_permintaan_bahan_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('permintaan_bahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_id')->nullable()->constrained('bahans')->nullOnDelete();
            $table->decimal('jumlah', 15, 3);
            $table->string('satuan', 20);
            $table->string('kategori', 50);
            $table->date('tanggal_kebutuhan');
            $table->text('alasan')->nullable();
            $table->enum('status', ['Pending','Approved','Rejected'])->default('Pending');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('permintaan_bahan');
    }
};

