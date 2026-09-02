<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->json('approval_path')->nullable()->after('is_ttd_enabled')
                  ->comment('Format: [{"jabatan_id": 1, "is_signer": false, "order": 1}]');
        });

        Schema::table('surats', function (Blueprint $table) {
            $table->json('approval_path')->nullable()->after('status_surat')
                  ->comment('Actual path for this specific letter instance');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('approval_path');
        });

        Schema::table('surats', function (Blueprint $table) {
            $table->dropColumn('approval_path');
        });
    }
};
