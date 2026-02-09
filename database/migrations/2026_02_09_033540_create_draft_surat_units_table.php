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
        Schema::create('draft_surat_units', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('surat_id')
                ->constrained()
                ->cascadeOnDelete();
        
            $table->foreignId('unit_kerja_id')
                ->constrained();
        
            $table->enum('jenis_tujuan', ['utama', 'tembusan']);
        
            $table->timestamps();
        
            $table->unique(['surat_id', 'unit_kerja_id']);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_surat_units');
    }
};
