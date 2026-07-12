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
            $table->foreignId('unit_kerja_id')->constrained('unit_kerjas')->cascadeOnDelete();
            $table->string('nama_format');
            $table->string('format_penomoran')->comment('cth: {KODE_UNIT}/{NOMOR}/{BULAN-ROMAWI}/{TAHUN}');
            $table->integer('nomor_urut_terakhir')->default(0)->comment('di-increment hanya saat surat resmi dikirim');
            $table->integer('tahun');
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
