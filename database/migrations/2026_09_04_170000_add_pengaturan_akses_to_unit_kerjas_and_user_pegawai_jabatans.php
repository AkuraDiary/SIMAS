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
        Schema::table('unit_kerjas', function (Blueprint $table) {
            $table->json('pengaturan_akses')->nullable()->after('is_active');
        });

        Schema::table('user_pegawai_jabatans', function (Blueprint $table) {
            $table->string('akses_surat_masuk')->default('DEFAULT')->after('status_jabatan')
                ->comment('DEFAULT, SEMUA, HANYA_DISPOSISI');
            $table->boolean('can_disposisi')->default(false)->after('akses_surat_masuk')
                ->comment('Izin delegasi membuat disposisi untuk unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_pegawai_jabatans', function (Blueprint $table) {
            $table->dropColumn(['akses_surat_masuk', 'can_disposisi']);
        });

        Schema::table('unit_kerjas', function (Blueprint $table) {
            $table->dropColumn('pengaturan_akses');
        });
    }
};
