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
        Schema::create('arsip_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_kerja_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kategori_arsip_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('tanggal_arsip')->useCurrent();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['surat_id', 'unit_kerja_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arsip_surats');
    }
};
