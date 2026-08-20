<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scopes each Jabatan to the UnitKerja that owns it.
 *
 * Rationale:
 *   "Sekretaris" in Rektorat and "Sekretaris" in Fakultas are structurally
 *   different positions (different unit depth, different access context).
 *   By adding unit_kerja_id here, each Jabatan record becomes unit-specific,
 *   allowing independent role/access attributes per position per unit.
 *
 *   Global hierarchy = unit depth (via parent_id chain) + level_jabatan within unit.
 *
 * Backward compatibility:
 *   Existing jabatan rows get unit_kerja_id = NULL (legacy/unassigned).
 *   Reassign them via the Organisasi page's Edit Unit modal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jabatans', function (Blueprint $table) {
            $table->foreignId('unit_kerja_id')
                ->nullable()
                ->after('id')
                ->constrained('unit_kerjas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jabatans', function (Blueprint $table) {
            $table->dropForeign(['unit_kerja_id']);
            $table->dropColumn('unit_kerja_id');
        });
    }
};
