<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Jadikan VARCHAR(20) supaya fleksibel (termasuk 'Purchasing')
        DB::statement("ALTER TABLE `permintaan_bahan`
                       MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        // Kembalikan ke enum lama jika perlu (sesuaikan kalau enum-mu berbeda)
        DB::statement("ALTER TABLE `permintaan_bahan`
                       MODIFY `status` ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending'");
    }
};
