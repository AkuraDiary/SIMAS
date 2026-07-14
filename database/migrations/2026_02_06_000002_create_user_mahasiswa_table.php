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
        Schema::create('user_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nim');
            $table->string('nama_lengkap');
            $table->date('tanggal_lahir')->nullable();
            $table->year('tahun_masuk')->nullable();
            $table->enum('status', ['AKTIF', 'CUTI', 'LULUS', 'KELUAR', 'MUTASI'])->default('AKTIF');
            $table->foreignId('prodi_id')->nullable()->constrained('unit_kerjas')->nullOnDelete();
            $table->foreignId('fakultas_id')->nullable()->constrained('unit_kerjas')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_mahasiswa');
    }
};
