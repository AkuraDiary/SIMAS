<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            'key' => 'ai_drafting_enabled',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('ai_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('label');
            $table->text('api_key')->nullable();
            $table->string('model')->nullable();
            $table->string('base_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_tokens');
        Schema::dropIfExists('system_settings');
    }
};
