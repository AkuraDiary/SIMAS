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
        Schema::create('surat_ttds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surats')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tipe', ['UTAMA', 'SEKUNDER'])->default('UTAMA')
                ->comment('UTAMA = penandatangan primer dokumen, SEKUNDER = penandatangan pendamping');
            $table->boolean('is_visible')->default(true)
                ->comment('false = TTD tidak dirender di dokumen final, tapi tetap terekam untuk audit trail');
            $table->string('jabatan_saat_ttd')->comment('snapshot jabatan saat dokumen ditandatangani');
            $table->string('unit_saat_ttd')->comment('snapshot unit kerja saat dokumen ditandatangani');
            $table->integer('halaman')->nullable();
            $table->float('posisi_x')->nullable();
            $table->float('posisi_y')->nullable();
            $table->dateTime('signed_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_ttds');
    }
};
