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
        Schema::create('nomor_surat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surats')->cascadeOnDelete();
            $table->foreignId('format_nomor_id')->constrained('format_nomor_surats');
            $table->integer('nomor_urut');
            $table->string('nomor_lengkap')->comment('hasil render final, bisa berbeda jika user edit di form');
            $table->date('tanggal_ditetapkan')->comment('mendukung backdate');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomor_surat_logs');
    }
};
