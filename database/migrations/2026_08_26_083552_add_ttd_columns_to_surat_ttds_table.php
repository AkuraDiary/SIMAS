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
        Schema::table('surat_ttds', function (Blueprint $table) {
            $table->string('placeholder_key')->nullable()->after('is_visible');
            $table->string('qr_code_path')->nullable()->after('placeholder_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_ttds', function (Blueprint $table) {
            $table->dropColumn(['placeholder_key', 'qr_code_path']);
        });
    }
};
