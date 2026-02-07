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
        Schema::create('surat_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained();
            $table->foreignId('unit_kerja_id')->constrained();
            $table->enum('jenis_tujuan', ['utama', 'tembusan']);
            $table->dateTime('tanggal_terima')->nullable();
            $table->enum('status_baca', ['BELUM', 'SUDAH'])->default('BELUM');
            // $table->primary(['surat_id', 'unit_kerja_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_units');
    }
};
