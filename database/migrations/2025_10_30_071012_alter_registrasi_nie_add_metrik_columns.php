<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Kalau tabel belum ada → buat lengkap
        if (!Schema::hasTable('registrasi_nie')) {
            Schema::create('registrasi_nie', function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->id();
                // Relasi ke permintaan_bahan (trial), nullable biar aman
                $table->unsignedBigInteger('trial_id')->nullable();

                // Kolom metrik
                $table->json('proses')->nullable(); // riwayat submit/terbit/status
                $table->string('negara', 100)->nullable()->index();
                $table->string('brand', 100)->nullable()->index();
                $table->string('perubahan_desain', 255)->nullable();
                $table->date('masa_berlaku_nie')->nullable()->index();
                $table->string('approve_vendor_lama', 150)->nullable();
                $table->string('source_tersedia', 150)->nullable();

                // Kolom yang dipakai controller lama
                $table->string('hasil', 100)->nullable();
                $table->text('keterangan')->nullable();

                $table->timestamps();
            });

            // Tambah FK hanya kalau tabel induk ada (biar anti-error urutan)
            if (Schema::hasTable('permintaan_bahan')) {
                Schema::table('registrasi_nie', function (Blueprint $table) {
                    $table->foreign('trial_id')
                        ->references('id')->on('permintaan_bahan')
                        ->nullOnDelete(); // kalau permintaan_bahan dihapus, set null
                });
            }

            return; // selesai (karena create baru)
        }

        // Kalau tabel sudah ada → tambah kolom yang belum ada
        Schema::table('registrasi_nie', function (Blueprint $table) {
            if (!Schema::hasColumn('registrasi_nie', 'trial_id')) {
                $table->unsignedBigInteger('trial_id')->nullable();
            }
            if (!Schema::hasColumn('registrasi_nie', 'proses')) {
                $table->json('proses')->nullable();
            }
            if (!Schema::hasColumn('registrasi_nie', 'negara')) {
                $table->string('negara', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('registrasi_nie', 'brand')) {
                $table->string('brand', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('registrasi_nie', 'perubahan_desain')) {
                $table->string('perubahan_desain', 255)->nullable();
            }
            if (!Schema::hasColumn('registrasi_nie', 'masa_berlaku_nie')) {
                $table->date('masa_berlaku_nie')->nullable()->index();
            }
            if (!Schema::hasColumn('registrasi_nie', 'approve_vendor_lama')) {
                $table->string('approve_vendor_lama', 150)->nullable();
            }
            if (!Schema::hasColumn('registrasi_nie', 'source_tersedia')) {
                $table->string('source_tersedia', 150)->nullable();
            }
            if (!Schema::hasColumn('registrasi_nie', 'hasil')) {
                $table->string('hasil', 100)->nullable();
            }
            if (!Schema::hasColumn('registrasi_nie', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
        });

        // (Opsional) coba tambahkan FK trial_id kalau tabel induk ada dan FK belum ada.
        // Untuk simple & aman, cukup biarkan tanpa FK bila sudah terlanjur ada datanya.
        if (Schema::hasTable('permintaan_bahan') && Schema::hasColumn('registrasi_nie', 'trial_id')) {
            // Banyak environment gak butuh FK di sini. Lewati jika ragu.
            // Kalau mau paksakan FK, pastikan tidak ada data yatim dulu.
        }
    }

    public function down(): void
    {
        // Jangan drop table (bisa sudah lama dipakai). Hanya drop kolom tambahan bila ada.
        if (Schema::hasTable('registrasi_nie')) {
            Schema::table('registrasi_nie', function (Blueprint $table) {
                // Lepas FK kalau ada
                if (Schema::hasColumn('registrasi_nie', 'trial_id')) {
                    // Nama constraint bisa berbeda-beda; aman: coba drop by column.
                    try { $table->dropForeign(['trial_id']); } catch (\Throwable $e) {}
                }

                foreach ([
                    'source_tersedia',
                    'approve_vendor_lama',
                    'masa_berlaku_nie',
                    'perubahan_desain',
                    'brand',
                    'negara',
                    'proses',
                    'hasil',
                    'keterangan',
                    'trial_id',
                ] as $col) {
                    if (Schema::hasColumn('registrasi_nie', $col)) {
                        try { $table->dropColumn($col); } catch (\Throwable $e) {}
                    }
                }
            });
        }
    }
};
