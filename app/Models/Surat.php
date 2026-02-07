<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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


    public function suratUnits(): HasMany
    {
        return $this->hasMany(SuratUnit::class);
    }

    public function disposisis(): HasMany
    {
        return $this->hasMany(Disposisi::class);
    }

    public function lampirans()
    {
        return $this->hasMany(Lampiran::class);
    }

    public function tujuanUntukUnit(int $unitId): string
    {
        $disposisi = $this->disposisis->first();

        if ($disposisi) {
            return 'Disposisi: ' . $disposisi->parent?->unitTujuan?->nama_unit;
        }

        $suratUnit = $this->suratUnits->first();

        return ucfirst($suratUnit?->jenis_tujuan ?? '-');
    }

    public function scopeUntukUnit(Builder $query, int $unitId): Builder
    {
        return $query->where(function ($q) use ($unitId) {
            $q->whereHas(
                'suratUnits',
                fn($sq) =>
                $sq->where('unit_kerja_id', $unitId)
            )
                ->orWhereHas(
                    'disposisis',
                    fn($dq) =>
                    $dq->where('unit_tujuan_id', $unitId)
                );
        });
    }

    public function scopeMasukLangsung(Builder $query, int $unitId): Builder
    {
        return $query
            ->whereHas(
                'suratUnits',
                fn($q) =>
                $q->where('unit_kerja_id', $unitId)
            )
            ->whereDoesntHave(
                'disposisis',
                fn($q) =>
                $q->where('unit_tujuan_id', $unitId)
            );
    }

    public function scopeDisposisi(Builder $query, int $unitId): Builder
    {
        return $query
            ->whereHas(
                'disposisis',
                fn($q) =>
                $q->where('unit_tujuan_id', $unitId)
            )
            ->whereDoesntHave(
                'suratUnits',
                fn($q) =>
                $q->where('unit_kerja_id', $unitId)
            );
    }
}
