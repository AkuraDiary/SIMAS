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
        Schema::table('surats', function (Blueprint $table) {
            $table->string('nomor_surat')->nullable()->index()->after('perihal');
            $table->string('nomor_surat_eksternal')->nullable()->after('nomor_surat')
                ->comment('Nomor surat asal dari pihak luar (khusus tipe EKSTERNAL)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surats', function (Blueprint $table) {
            $table->dropIndex(['nomor_surat']);
            $table->dropColumn(['nomor_surat', 'nomor_surat_eksternal']);
        });
    }
};
