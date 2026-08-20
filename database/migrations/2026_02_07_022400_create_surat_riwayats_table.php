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
        Schema::create('surat_riwayats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surats')->cascadeOnDelete()
                ->comment('Surat yang direferensikan oleh riwayat ini');
            $table->foreignId('parent_id')->nullable()->constrained('surat_riwayats')->nullOnDelete()
                ->comment('Self-reference ke langkah sebelumnya. NULL jika ini adalah langkah pertama (BUAT/KIRIM awal). Untuk backtrack, parent_id menunjuk langsung ke baris tujuan backtrack (bukan selalu baris sebelumnya secara urutan).');
            $table->foreignId('unit_asal_id')->constrained('unit_kerjas')
                ->comment('Unit yang mengambil aksi pada langkah ini.');
            $table->foreignId('unit_tujuan_id')->nullable()->constrained('unit_kerjas')->nullOnDelete()
                ->comment('Unit yang dituju. NULL jika aksi terminal (DITOLAK, SELESAI) karena tidak ada tujuan berikutnya.');
            $table->foreignId('user_aktor_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Pengguna yang mengambil aksi. NULL jika belum diproses (status masih MENUNGGU).');
            $table->enum('status', ['MENUNGGU', 'DISETUJUI', 'DIKEMBALIKAN', 'DITOLAK', 'REVISI'])
                ->default('MENUNGGU')
                ->comment('MENUNGGU — belum diproses. DISETUJUI — maju ke unit_tujuan. DIKEMBALIKAN — backtrack via parent_id. DITOLAK — ditolak permanen. REVISI — bola ada di staf untuk diperbaiki sebelum dikirim ulang.');
            $table->text('catatan')->nullable()->comment('Instruksi, alasan pengembalian, atau catatan revisi dari aktor.');
            $table->dateTime('expired_at')->nullable()->comment('Batas berlakunya surat/persetujuan (jika ada)');
            $table->dateTime('actioned_at')->nullable()->comment('Waktu aksi diambil. NULL selama status masih MENUNGGU.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_riwayats');
    }
};
