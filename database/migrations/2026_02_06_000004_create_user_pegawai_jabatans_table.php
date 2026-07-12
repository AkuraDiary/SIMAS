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
        Schema::create('user_pegawai_jabatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_pegawai_id')->constrained('user_pegawai')->cascadeOnDelete();
            $table->foreignId('unit_kerja_id')->constrained('unit_kerjas')->cascadeOnDelete();
            $table->foreignId('jabatan_id')->constrained('jabatans')->cascadeOnDelete();
            $table->enum('status_jabatan', ['AKTIF', 'NONAKTIF'])->default('AKTIF');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pegawai_jabatans');
    }
};
