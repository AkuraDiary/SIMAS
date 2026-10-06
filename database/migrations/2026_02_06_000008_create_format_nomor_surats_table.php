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
        Schema::create('format_nomor_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_kerja_id')->nullable()->constrained('unit_kerjas')->nullOnDelete();
            $table->string('tipe_surat')->default('ALL')->comment('ALL, INTERNAL, PENGAJUAN, TERBITAN, EKSTERNAL');
            $table->string('nama_format');
            $table->string('format_penomoran')->comment('cth: {KODE_UNIT}/{NOMOR}/{BULAN-ROMAWI}/{TAHUN}');
            $table->integer('padding_digit')->default(3)->comment('Jumlah digit padding nomor urut, cth 3 => 001');
            $table->integer('nomor_urut_terakhir')->default(0)->comment('di-increment hanya saat surat resmi dikirim');
            $table->integer('tahun');
            $table->index(['unit_kerja_id', 'tipe_surat', 'tahun', 'is_active'], 'fn_surats_lookup_idx');
            $table->boolean('is_active')->default(false)->comment('hanya satu aktif per unit_kerja_id per tahun');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('format_nomor_surats');
    }
};
