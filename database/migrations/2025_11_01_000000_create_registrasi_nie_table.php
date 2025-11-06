<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Jangan buat lagi kalau sudah ada
        if (Schema::hasTable('registrasi_nie')) {
            return;
        }

        Schema::create('registrasi_nie', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();

            // FK → permintaan_bahan.id (nullable agar aman jika parent dihapus → SET NULL)
            // JANGAN kasih unique() dulu, biar tidak bentrok nama index ketika tambah FK
            $table->unsignedBigInteger('trial_id')->nullable()->index('ix_regnie_trial_id');

            // Info ringkas
            $table->string('kode', 20)->nullable()->index('ix_regnie_kode');
            $table->string('bahan_nama', 150)->nullable();

            // Dari hasil konfirmasi trial
            $table->date('tgl_trial_selesai')->nullable();

            // Proses registrasi (versi field legacy yang sudah ada di env lain)
            $table->date('tgl_nie_submit')->nullable();
            $table->date('tgl_nie_terbit')->nullable();
            $table->string('status_dokumen', 50)->nullable();
            $table->string('registrasi_nie', 100)->nullable();
            $table->date('tgl_verifikasi')->nullable();
            // Pakai string agar fleksibel lintas env (daripada enum)
            $table->string('hasil', 100)->nullable();

            $table->string('keterangan', 500)->nullable();

            // Kolom metrik tambahan
            $table->json('proses')->nullable();
            $table->string('negara', 100)->nullable()->index('ix_regnie_negara');
            $table->string('brand', 100)->nullable()->index('ix_regnie_brand');
            $table->string('perubahan_desain', 255)->nullable();
            $table->date('masa_berlaku_nie')->nullable()->index('ix_regnie_masa');
            $table->string('approve_vendor_lama', 150)->nullable();
            $table->string('source_tersedia', 150)->nullable();

            $table->timestamps();
        });

        // Tambahkan FK di langkah terpisah agar:
        // 1) Bisa diberi nama khusus (hindari bentrok)
        // 2) Hanya dipasang jika tabel induk sudah ada
        if (Schema::hasTable('permintaan_bahan')) {
            Schema::table('registrasi_nie', function (Blueprint $table) {
                try {
                    $table->foreign('trial_id', 'fk_regnie_trial_id')
                          ->references('id')->on('permintaan_bahan')
                          ->nullOnDelete()     // kolom trial_id sudah nullable
                          ->cascadeOnUpdate();
                } catch (\Throwable $e) {
                    // biarkan lewat jika sudah pernah ada / bentrok name
                }
            });
        }

        // Opsional (kalau kamu memang ingin 1–1 dengan permintaan_bahan):
        // bikin UNIQUE SETELAH FK agar tidak bentrok pembuatan index otomatis.
        // Schema::table('registrasi_nie', function (Blueprint $table) {
        //     try { $table->unique('trial_id', 'ux_regnie_trial_id'); } catch (\Throwable $e) {}
        // });
    }

    public function down(): void
    {
        if (!Schema::hasTable('registrasi_nie')) return;

        // Lepas FK (nama harus sesuai dengan yang kita set)
        try {
            Schema::table('registrasi_nie', function (Blueprint $table) {
                $table->dropForeign('fk_regnie_trial_id');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::dropIfExists('registrasi_nie');
    }
};
