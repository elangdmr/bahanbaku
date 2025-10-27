<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permintaan_bahan')) {
            return;
        }

        // Jika semua kolom vendor sudah ada, anggap selesai (no-op)
        $allVendorCols = [
            'pabrik_pembuat',
            'negara_asal',
            'distributor',
            'distributor_list',
            'tgl_permintaan_coa',
            'est_coa_diterima',
            'tgl_coa_diterima',
        ];
        $allExists = true;
        foreach ($allVendorCols as $c) {
            if (!Schema::hasColumn('permintaan_bahan', $c)) {
                $allExists = false; break;
            }
        }
        if ($allExists) {
            return;
        }

        // PASS 1 — pabrik_pembuat (opsional after 'alasan' jika ada)
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('permintaan_bahan', 'pabrik_pembuat')) {
                $col = $table->string('pabrik_pembuat')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'alasan')) {
                    $col->after('alasan');
                }
            }
        });

        // PASS 2 — negara_asal, distributor (opsional after jika kolom acuan ada)
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            // negara_asal
            if (!Schema::hasColumn('permintaan_bahan', 'negara_asal')) {
                $col = $table->string('negara_asal', 100)->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'pabrik_pembuat')) {
                    $col->after('pabrik_pembuat');
                }
            }
            // distributor
            if (!Schema::hasColumn('permintaan_bahan', 'distributor')) {
                $col = $table->string('distributor')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'negara_asal')) {
                    $col->after('negara_asal');
                }
            }
        });

        // PASS 3 — distributor_list (JSON)
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('permintaan_bahan', 'distributor_list')) {
                $col = $table->json('distributor_list')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'distributor')) {
                    $col->after('distributor');
                }
            }
        });

        // PASS 4 — tgl_permintaan_coa, est_coa_diterima, tgl_coa_diterima
        Schema::table('permintaan_bahan', function (Blueprint $table) {
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_permintaan_coa')) {
                $col = $table->date('tgl_permintaan_coa')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'distributor_list')) {
                    $col->after('distributor_list');
                }
            }
            if (!Schema::hasColumn('permintaan_bahan', 'est_coa_diterima')) {
                $col = $table->date('est_coa_diterima')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'tgl_permintaan_coa')) {
                    $col->after('tgl_permintaan_coa');
                }
            }
            if (!Schema::hasColumn('permintaan_bahan', 'tgl_coa_diterima')) {
                $col = $table->date('tgl_coa_diterima')->nullable();
                if (Schema::hasColumn('permintaan_bahan', 'est_coa_diterima')) {
                    $col->after('est_coa_diterima');
                }
            }
        });

        // Migrasi data: distributor (string) -> distributor_list (json)
        if (Schema::hasColumn('permintaan_bahan', 'distributor')
            && Schema::hasColumn('permintaan_bahan', 'distributor_list')) {

            DB::table('permintaan_bahan')
                ->whereNotNull('distributor')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $r) {
                        $arr = array_values(array_filter(
                            array_map('trim', explode(',', (string) $r->distributor))
                        ));
                        DB::table('permintaan_bahan')
                            ->where('id', $r->id)
                            ->update(['distributor_list' => json_encode($arr, JSON_UNESCAPED_UNICODE)]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permintaan_bahan')) {
            return;
        }

        Schema::table('permintaan_bahan', function (Blueprint $table) {
            foreach ([
                'tgl_coa_diterima',
                'est_coa_diterima',
                'tgl_permintaan_coa',
                'distributor_list',
                'distributor',
                'negara_asal',
                'pabrik_pembuat',
            ] as $col) {
                if (Schema::hasColumn('permintaan_bahan', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
