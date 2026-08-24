<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alter enum to include DITERUSKAN safely
        DB::statement("ALTER TABLE surat_riwayats MODIFY COLUMN status ENUM('MENUNGGU', 'DISETUJUI', 'DIKEMBALIKAN', 'DITOLAK', 'REVISI', 'DIPERBARUI', 'DITERUSKAN') DEFAULT 'MENUNGGU' COMMENT 'MENUNGGU, DISETUJUI, DIKEMBALIKAN (Step-back), DITOLAK, REVISI (Total Reset), DIPERBARUI, DITERUSKAN'");
    }

    public function down(): void
    {
        // Reverting enum in MySQL requires redefining the old allowed values.
        DB::statement("ALTER TABLE surat_riwayats MODIFY COLUMN status ENUM('MENUNGGU', 'DISETUJUI', 'DIKEMBALIKAN', 'DITOLAK', 'REVISI', 'DIPERBARUI') DEFAULT 'MENUNGGU'");
    }
};