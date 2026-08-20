<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->foreignId('user_pegawai_jabatan_id')->nullable()->constrained('user_pegawai_jabatans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->dropForeign(['user_pegawai_jabatan_id']);
            $table->dropColumn('user_pegawai_jabatan_id');
        });
    }
};
