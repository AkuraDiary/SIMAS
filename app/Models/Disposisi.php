<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disposisi extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\DisposisiFactory> */
    use InteractsWithMedia;
    use HasFactory, SoftDeletes;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('bukti-disposisi')
            ->singleFile(); // Since you set multiple(false) in Filament
    }

    protected $fillable = [
        'jenis_instruksi',
        'sifat',
        'catatan',
        'tanggal_disposisi',
        'status_disposisi',
        'surat_id',
        'unit_tujuan_id',
        'user_pembuat_id',
        'user_pegawai_jabatan_id',
        'parent_disposisi_id',
    ];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function unitTujuan(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_tujuan_id');
    }

    // Disposisi is made by a person (user), not a unit
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_pembuat_id');
    }

    public function userPegawaiJabatan(): BelongsTo
    {
        return $this->belongsTo(UserPegawaiJabatan::class, 'user_pegawai_jabatan_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Disposisi::class, 'parent_disposisi_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Disposisi::class, 'parent_disposisi_id');
    }
}
