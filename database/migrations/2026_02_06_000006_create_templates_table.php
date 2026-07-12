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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('template_kategoris');
            $table->foreignId('entry_point_unit_id')->nullable()->constrained('unit_kerjas')->nullOnDelete()
                ->comment('unit penerima otomatis untuk surat publik/mahasiswa');
            $table->string('nama_template');
            $table->enum('tipe_surat', ['INTERNAL', 'PENGAJUAN', 'TERBITAN', 'EKSTERNAL'])
                ->comment('menentukan flow default surat yang dibuat dari template ini');
            $table->enum('aksesibilitas', ['PUBLIK', 'MAHASISWA', 'INTERNAL'])->default('INTERNAL');
            $table->json('field_variables')->nullable()->comment('definisi field form dinamis, di-render Filament');
            $table->string('template_file_path')->nullable()->comment('path .docx dengan placeholder, dikelola Spatie');
            // Path 2 (in-app TipTap editor) — see notes_template_system.md
            $table->longText('content_html')->nullable()->comment('body HTML untuk template yang dibuat in-app');
            $table->enum('render_engine', ['DOCX', 'HTML'])->default('HTML');
            $table->boolean('is_ttd_enabled')->default(true)->comment('konfigurasi default apakah template ini menggunakan TTD');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
