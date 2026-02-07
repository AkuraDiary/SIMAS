<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Surat extends Model
{
    /** @use HasFactory<\Database\Factories\SuratFactory> */
    use HasFactory;

    protected $fillable = [
        'nomor_agenda',
        'nomor_surat',
        'perihal',
        'isi_surat',
        'tanggal_buat',
        'tanggal_kirim',
        'status_surat',
        'unit_pengirim_id',
        'user_pembuat_id',
    ];

    // Unit pengirim surat
    public function unitPengirim(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_pengirim_id');
    }

    // User pembuat surat
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_pembuat_id');
    }

    // Unit tujuan surat (surat masuk)
    public function unitTujuan(): BelongsToMany
    {
        return $this->belongsToMany(
            UnitKerja::class,
            'surat_unit',
            'surat_id',
            'unit_kerja_id'
        )
            ->withPivot([
                'jenis_tujuan',
                'tanggal_terima',
                'status_baca',
            ]);
    }

    // Disposisi surat
    public function disposisis(): HasMany
    {
        return $this->hasMany(Disposisi::class);
    }

    public function lampirans()
    {
        return $this->hasMany(Lampiran::class);
    }
}
