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
            $table->foreignId('surat_id')->constrained();
            $table->foreignId('user_pembuat_id')->constrained('users');
            $table->foreignId('unit_tujuan_id')->constrained('unit_kerjas');
            // Self-referencing
            $table->foreignId('parent_disposisi_id')->nullable()->constrained('disposisis');
            $table->string('jenis_instruksi')->comment('cth: Untuk Ditindaklanjuti, Untuk Diketahui');
            $table->enum('sifat', ['RAHASIA', 'PENTING', 'BIASA', 'SEGERA', 'SANGAT_SEGERA'])->nullable();
            $table->text('catatan')->nullable();
            $table->enum('status_disposisi', ['BARU', 'DIPROSES', 'SELESAI'])->default('BARU');
            $table->dateTime('tanggal_disposisi');
            $table->softDeletes();
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
