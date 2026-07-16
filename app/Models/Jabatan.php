<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A position title scoped to a specific UnitKerja.
 *
 * Hierarchy is two-dimensional:
 *   - Unit depth  : determined by UnitKerja.parent_id chain (global level)
 *   - level_jabatan: rank within the unit (local level, e.g. 1 = Kepala, 2 = Sekretaris)
 *
 * Two Jabatans with the same level_jabatan in units of the same depth
 * are peer-equivalent (e.g. Sekretaris at Fak A == Sekretaris at Fak B),
 * but a Sekretaris in Rektorat (depth 0) outranks them because its unit is higher.
 */
class Jabatan extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'unit_kerja_id',
        'nama_jabatan',
        'level_jabatan',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function pegawaiJabatans(): HasMany
    {
        return $this->hasMany(UserPegawaiJabatan::class);
    }
}
