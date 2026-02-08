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
        Schema::create('disposisis', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_instruksi');
            $table->enum('sifat', ['rahasia', 'penting', 'biasa', 'segera', 'sangat segera']);
            $table->text('catatan')->nullable();
            $table->dateTime('tanggal_disposisi');
            $table->dateTime('tanggal_update')->nullable();
            $table->enum('status_disposisi', ['BARU', 'DIPROSES', 'SELESAI']);
            $table->foreignId('surat_id')->constrained();
            $table->foreignId('unit_tujuan_id')->constrained('unit_kerjas');
            $table->foreignId('user_pembuat_id')->constrained('users');
            $table->unique(['surat_id', 'unit_tujuan_id']); // spam prevention
            // Self-referencing
            $table->foreignId('parent_disposisi_id')->nullable()->constrained('disposisis');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disposisis');
    }
};
