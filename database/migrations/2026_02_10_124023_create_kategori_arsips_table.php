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
        Schema::create('kategori_arsips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_kerja_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();
        
            $table->unique(['unit_kerja_id', 'nama']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_arsips');
    }
};
