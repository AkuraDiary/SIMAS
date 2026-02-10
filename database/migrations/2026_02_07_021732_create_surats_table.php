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
        Schema::create('surats', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_agenda');
            $table->string('nomor_surat');
            $table->string('perihal');
            $table->string('pengirim_eksternal')->nullable();
            $table->enum('tipe_surat', ['INTERNAL', 'EKSTERNAL',])->default('INTERNAL');
            $table->text('isi_surat');
            $table->dateTime('tanggal_buat');
            $table->dateTime('tanggal_kirim')->nullable();
            $table->enum('status_surat', ['DRAFT', 'TERKIRIM', 'DIPROSES', 'SELESAI']);
            $table->foreignId('unit_pengirim_id')->constrained('unit_kerjas');
            $table->foreignId('user_pembuat_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surats');
    }
};
