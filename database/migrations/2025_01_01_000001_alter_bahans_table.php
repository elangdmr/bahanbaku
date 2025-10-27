<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Jika tabel belum ada (kasus fresh install), buat dengan skema final
        if (!Schema::hasTable('bahans')) {
            Schema::create('bahans', function (Blueprint $table) {
                $table->id();
                $table->string('kode')->nullable()->unique();
                $table->string('nama'); // bebas mau unique atau tidak
                $table->string('default_satuan', 20)->nullable();
                $table->string('kategori', 50)->nullable();
                $table->decimal('stok', 15, 3)->nullable();
                $table->timestamps();
                $table->softDeletes(); // tetap ada agar konsisten dengan lingkunganmu sekarang
            });
            return;
        }

        // Jika tabel SUDAH ada (kasusmu sekarang), lakukan ALTER bertahap
        Schema::table('bahans', function (Blueprint $table) {
            // tambah kolom baru bila belum ada
            if (!Schema::hasColumn('bahans', 'kode')) {
                $table->string('kode')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('bahans', 'stok')) {
                $table->decimal('stok', 15, 3)->nullable()->after('kategori_default');
            }

            // siapkan kolom target (default_satuan & kategori) jika belum ada
            if (!Schema::hasColumn('bahans', 'default_satuan')) {
                $table->string('default_satuan', 20)->nullable()->after('nama');
            }
            if (!Schema::hasColumn('bahans', 'kategori')) {
                $table->string('kategori', 50)->nullable()->after('default_satuan');
            }
        });

        // copy data lama -> kolom baru (jika ada kolom lama)
        if (Schema::hasColumn('bahans', 'satuan_default')) {
            DB::statement('UPDATE bahans SET default_satuan = satuan_default WHERE default_satuan IS NULL');
        }
        if (Schema::hasColumn('bahans', 'kategori_default')) {
            DB::statement('UPDATE bahans SET kategori = kategori_default WHERE kategori IS NULL');
        }

        // bersihkan kolom lama (opsional)
        Schema::table('bahans', function (Blueprint $table) {
            if (Schema::hasColumn('bahans', 'satuan_default')) {
                $table->dropColumn('satuan_default');
            }
            if (Schema::hasColumn('bahans', 'kategori_default')) {
                $table->dropColumn('kategori_default');
            }
        });

        // jika ingin nama unik pada 'nama' tidak lagi unique:
        // pastikan index unik memang ada sebelum drop
        // try-catch karena nama index bisa berbeda antar mesin
        try { DB::statement('ALTER TABLE bahans DROP INDEX bahans_nama_unique'); } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        // rollback: kembalikan ke bentuk awal (versi 2025_01_01)
        Schema::table('bahans', function (Blueprint $table) {
            if (!Schema::hasColumn('bahans', 'satuan_default')) {
                $table->string('satuan_default', 20)->nullable();
            }
            if (!Schema::hasColumn('bahans', 'kategori_default')) {
                $table->string('kategori_default', 50)->nullable();
            }
        });

        DB::statement('UPDATE bahans SET satuan_default = default_satuan WHERE satuan_default IS NULL');
        DB::statement('UPDATE bahans SET kategori_default = kategori WHERE kategori_default IS NULL');

        Schema::table('bahans', function (Blueprint $table) {
            if (Schema::hasColumn('bahans', 'kode')) {
                // buang unique index kalau ada, lalu drop kolom
                try { DB::statement('ALTER TABLE bahans DROP INDEX bahans_kode_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('kode');
            }
            if (Schema::hasColumn('bahans', 'stok'))   $table->dropColumn('stok');
            if (Schema::hasColumn('bahans', 'default_satuan')) $table->dropColumn('default_satuan');
            if (Schema::hasColumn('bahans', 'kategori'))       $table->dropColumn('kategori');
        });
    }
};
