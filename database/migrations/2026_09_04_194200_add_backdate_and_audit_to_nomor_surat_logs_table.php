<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nomor_surat_logs', function (Blueprint $table) {
            $table->boolean('is_backdate')->default(false)->after('nomor_lengkap')
                ->comment('True jika tanggal_ditetapkan sebelum hari ini');
            $table->boolean('is_manual')->default(false)->after('is_backdate')
                ->comment('True jika staf menggunakan nomor kustom / sisipan');
            $table->text('alasan_backdate')->nullable()->after('is_manual')
                ->comment('Alasan backdate / kustomisasi nomor');
            $table->foreignId('user_id')->nullable()->after('alasan_backdate')
                ->constrained('users')->nullOnDelete()
                ->comment('User yang menetapkan / menyetujui nomor ini');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nomor_surat_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['is_backdate', 'is_manual', 'alasan_backdate']);
        });
    }
};
