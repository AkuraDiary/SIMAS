<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Safely alter the ENUM to include DIPERBARUI without dropping the column
        DB::statement("ALTER TABLE surat_riwayats MODIFY COLUMN status ENUM('MENUNGGU', 'DISETUJUI', 'DIKEMBALIKAN', 'DITOLAK', 'REVISI', 'DIPERBARUI') NOT NULL DEFAULT 'MENUNGGU'");
    }

    public function down(): void
    {
        // Safe rollback: delete the DIPERBARUI rows first so MySQL doesn't crash when we remove the ENUM
        DB::statement("DELETE FROM surat_riwayats WHERE status = 'DIPERBARUI'");
        DB::statement("ALTER TABLE surat_riwayats MODIFY COLUMN status ENUM('MENUNGGU', 'DISETUJUI', 'DIKEMBALIKAN', 'DITOLAK', 'REVISI') NOT NULL DEFAULT 'MENUNGGU'");
    }
};