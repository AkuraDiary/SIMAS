<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitKerja extends Model
{
    /** @use HasFactory<\Database\Factories\UnitKerjaFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'jenis_unit_id',
        'nama_unit',
        'singkatan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'nama_unit';
    }

    public function jenisUnit(): BelongsTo
    {
        return $this->belongsTo(JenisUnit::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(UnitKerja::class, 'parent_id');
    }

    /**
     * Active/inactive jabatan assignments of staff placed in this unit.
     * A unit's "staff roster" is now this relation (not a direct users() FK),
     * since a user's unit is assigned via user_pegawai_jabatans, not a column on users.
     */
    public function pegawaiJabatans(): HasMany
    {
        return $this->hasMany(UserPegawaiJabatan::class);
    }

    public function arsipSurats(): HasMany
    {
        return $this->hasMany(ArsipSurat::class);
    }

    public function kategoriArsips(): HasMany
    {
        return $this->hasMany(KategoriArsip::class);
    }

    public function formatNomorSurats(): HasMany
    {
        return $this->hasMany(FormatNomorSurat::class);
    }

    public function templateAkses(): BelongsToMany
    {
        return $this->belongsToMany(
            Template::class,
            'template_unit_akses',
            'unit_kerja_id',
            'template_id'
        );
    }

    // Surat yang dikirim oleh unit
    public function suratKeluar(): HasMany
    {
        return $this->hasMany(Surat::class, 'unit_pengirim_id');
    }

    // Surat masuk ke unit
    public function suratMasuk(): BelongsToMany
    {
        return $this->belongsToMany(
            Surat::class,
            'surat_unit',
            'unit_kerja_id',
            'surat_id'
        )
            ->withPivot([
                'jenis_tujuan',
                'tanggal_terima',
                'status_baca',
            ]);
    }
}
