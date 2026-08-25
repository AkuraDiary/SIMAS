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
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('user_pegawai_jabatan_id')->nullable()->constrained('user_pegawai_jabatans')->nullOnDelete()
                ->comment('NULL jika diajukan Mahasiswa/Guest');
            $table->foreignId('reply_to_surat_id')->nullable()->constrained('surats')->nullOnDelete()
                ->comment('thread balasan NDE, reply = surat baru independen');
            $table->foreignId('terbitan_for_surat_id')->nullable()->constrained('surats')->nullOnDelete()
                ->comment('TERBITAN sebagai output dari PENGAJUAN');
            $table->foreignId('unit_pengirim_id')->nullable()->constrained('unit_kerjas')->nullOnDelete();
            // $table->foreignId('unit_pengirim_id')->constrained('unit_kerjas');
            $table->foreignId('user_pembuat_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('NULL jika Guest tanpa login');
            $table->string('pengirim_nim')->nullable()->comment('diisi jika Mahasiswa via jalur publik tanpa login');
            $table->string('pengirim_nama')->nullable()->comment('dapat diisi Guest atau mahasiswa');
            $table->string('pengirim_email')->nullable()->comment('dapat diisi Guest atau mahasiswa');
            $table->json('pengirim_metadata')->nullable()->comment('diisi metadata pengirim eksternal baik guest maupun mahasiswa');
            $table->string('perihal');

            $table->timestamp('tanggal_kirim')->nullable();
            $table->enum('tipe_surat', ['INTERNAL', 'PENGAJUAN', 'TERBITAN', 'EKSTERNAL'])->default('INTERNAL');
            $table->enum('status_surat', ['DRAFT', 'DIPROSES', 'REVISI', 'TERKIRIM', 'SELESAI', 'DITOLAK', 'DIBATALKAN']);
            $table->json('content')->nullable()->comment('hasil isian form field_variables, di-merge ke .docx via PHPWord');
            $table->string('tracking_code')->nullable()->unique()->comment('pelacakan untuk Guest & Mahasiswa tanpa login');
            $table->text('qr_code_payload')->nullable();
            $table->softDeletes();
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
