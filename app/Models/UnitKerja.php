<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UnitKerja extends Model
{
    /** @use HasFactory<\Database\Factories\UnitKerjaFactory> */
    use HasFactory;

    protected $fillable = [
        'nama_unit',
        'jenis_unit',
        'status_unit',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'nama_unit'; 
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
