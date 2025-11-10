<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kolom-kolom NIE yang akan ditambahkan ke permintaan_bahan */
    private array $cols = [
        'nie_kode'              => ['type' => 'string', 'len' => 50],
        'nie_registrasi'        => ['type' => 'string', 'len' => 100],
        'nie_status_dokumen'    => ['type' => 'string', 'len' => 50],
        'nie_tgl_submit'        => ['type' => 'date'],
        'nie_tgl_terbit'        => ['type' => 'date'],
        'nie_tgl_trial_selesai' => ['type' => 'date'],
        'nie_tgl_verifikasi'    => ['type' => 'date'],
        'nie_hasil'             => ['type' => 'string', 'len' => 50],
        'nie_keterangan'        => ['type' => 'text'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permintaan_bahan')) {
            return;
        }

        // 1) Tambah kolom-kolom NIE (semua NULLable)
        foreach ($this->cols as $name => $meta) {
            if (!Schema::hasColumn('permintaan_bahan', $name)) {
                Schema::table('permintaan_bahan', function (Blueprint $table) use ($name, $meta) {
                    // taruh setelah produk_id biar ngumpul; aman walau diulang
                    $after = 'produk_id';
                    switch ($meta['type']) {
                        case 'string':
                            $table->string($name, $meta['len'] ?? 191)->nullable()->after($after);
                            break;
                        case 'date':
                            $table->date($name)->nullable()->after($after);
                            break;
                        case 'text':
                            $table->text($name)->nullable()->after($after);
                            break;
                    }
                });
            }
        }

        // 2) Backfill dari registrasi_nie (pilih mapping yang tersedia)
        try {
            if (!Schema::hasTable('registrasi_nie')) {
                return;
            }

            // A) via FK registrasi_nie_id
            if (
                Schema::hasColumn('permintaan_bahan', 'registrasi_nie_id') &&
                $this->columnsNieAdaDiRegistrasi()
            ) {
                DB::statement($this->sqlUpdate('rn.id = pb.registrasi_nie_id', 'pb.registrasi_nie_id IS NOT NULL'));
                return;
            }

            // B) via produk_id
            if (
                Schema::hasColumn('permintaan_bahan', 'produk_id') &&
                Schema::hasColumn('registrasi_nie', 'produk_id') &&
                $this->columnsNieAdaDiRegistrasi()
            ) {
                DB::statement($this->sqlUpdate('rn.produk_id = pb.produk_id'));
                return;
            }

            // C) via kode
            if (
                Schema::hasColumn('permintaan_bahan', 'kode') &&
                Schema::hasColumn('registrasi_nie', 'kode') &&
                $this->columnsNieAdaDiRegistrasi()
            ) {
                DB::statement($this->sqlUpdate('rn.kode = pb.kode'));
                return;
            }
        } catch (\Throwable $e) {
            // Jangan gagalkan migrasi bila backfill gagal
            logger()->warning('Backfill kolom NIE gagal: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permintaan_bahan')) {
            return;
        }

        // Drop kolom satu-satu (lebih aman untuk MySQL/MariaDB lama)
        foreach (array_keys($this->cols) as $name) {
            if (Schema::hasColumn('permintaan_bahan', $name)) {
                Schema::table('permintaan_bahan', function (Blueprint $table) use ($name) {
                    $table->dropColumn($name);
                });
            }
        }
    }

    /** Pastikan kolom sumber tersedia di registrasi_nie */
    private function columnsNieAdaDiRegistrasi(): bool
    {
        $need = [
            'kode','registrasi_nie','status_dokumen',
            'tgl_nie_submit','tgl_nie_terbit','tgl_trial_selesai',
            'tgl_verifikasi','hasil','keterangan'
        ];
        foreach ($need as $c) {
            if (!Schema::hasColumn('registrasi_nie', $c)) return false;
        }
        return true;
    }

    /** Template SQL update untuk berbagai mapping */
    private function sqlUpdate(string $joinOn, string $extraWhere = '1=1'): string
    {
        // Gunakan COALESCE agar tidak overwrite nilai yang sudah diisi manual
        return "
            UPDATE permintaan_bahan pb
            JOIN registrasi_nie rn ON {$joinOn}
            SET 
              pb.nie_kode              = COALESCE(pb.nie_kode, rn.kode),
              pb.nie_registrasi        = COALESCE(pb.nie_registrasi, rn.registrasi_nie),
              pb.nie_status_dokumen    = COALESCE(pb.nie_status_dokumen, rn.status_dokumen),
              pb.nie_tgl_submit        = COALESCE(pb.nie_tgl_submit, rn.tgl_nie_submit),
              pb.nie_tgl_terbit        = COALESCE(pb.nie_tgl_terbit, rn.tgl_nie_terbit),
              pb.nie_tgl_trial_selesai = COALESCE(pb.nie_tgl_trial_selesai, rn.tgl_trial_selesai),
              pb.nie_tgl_verifikasi    = COALESCE(pb.nie_tgl_verifikasi, rn.tgl_verifikasi),
              pb.nie_hasil             = COALESCE(pb.nie_hasil, rn.hasil),
              pb.nie_keterangan        = COALESCE(pb.nie_keterangan, rn.keterangan)
            WHERE {$extraWhere}
        ";
    }
};
