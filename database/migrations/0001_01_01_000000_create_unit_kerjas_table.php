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
        Schema::create('unit_kerjas', function (Blueprint $table) {
            $table->id(); // Default 'id'
            $table->string('nama_unit');
            $table->enum('jenis_unit', ['fakultas', 'non-fakultas']);
            $table->enum('status_unit', ['aktif', 'nonaktif']);
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_kerjas');
    }
};
