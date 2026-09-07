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
        Schema::table('format_nomor_surats', function (Blueprint $table) {
            $table->string('tipe_surat')->default('ALL')->after('unit_kerja_id')
                ->comment('ALL, INTERNAL, PENGAJUAN, TERBITAN, EKSTERNAL');
            $table->integer('padding_digit')->default(3)->after('format_penomoran')
                ->comment('Jumlah digit padding nomor urut, cth 3 => 001');
            $table->index(['unit_kerja_id', 'tipe_surat', 'tahun', 'is_active'], 'fn_surats_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('format_nomor_surats', function (Blueprint $table) {
            $table->dropIndex('fn_surats_lookup_idx');
            $table->dropColumn(['tipe_surat', 'padding_digit']);
        });
    }
};
