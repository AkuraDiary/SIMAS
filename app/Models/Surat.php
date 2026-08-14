<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Surat extends Model implements HasMedia
{
    use InteractsWithMedia;
    /** @use HasFactory<\Database\Factories\SuratFactory> */
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'template_id',
        'user_pegawai_jabatan_id',
        'reply_to_surat_id',
        'terbitan_for_surat_id',
        'unit_pengirim_id',
        'user_pembuat_id',
        'pengirim_nim',
        'pengirim_nama',
        'pengirim_email',
        'pengirim_metadata',
        'perihal',
        'tipe_surat',
        'status_surat',
        'content',
        'tracking_code',
        'qr_code_payload',
    ];

    protected function casts(): array
    {
        return [
            'pengirim_metadata' => 'array',
            'content' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    // Jabatan (unit+jabatan) staf pembuat surat, NULL jika diajukan Mahasiswa/Guest
    public function userPegawaiJabatan(): BelongsTo
    {
        return $this->belongsTo(UserPegawaiJabatan::class);
    }

    // Thread balasan NDE
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Surat::class, 'reply_to_surat_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Surat::class, 'reply_to_surat_id');
    }

    // TERBITAN sebagai output dari PENGAJUAN
    public function terbitanFor(): BelongsTo
    {
        return $this->belongsTo(Surat::class, 'terbitan_for_surat_id');
    }

    public function terbitans(): HasMany
    {
        return $this->hasMany(Surat::class, 'terbitan_for_surat_id');
    }

    // Unit pengirim surat
    public function unitPengirim(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_pengirim_id');
    }

    // In Disposisi.php
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('lampiran-surat')
            ->useDisk('private');

        $this->addMediaCollection('lampiran-preview')
            ->useDisk('private');
    }
    public function suratUnits(): HasMany
    {
        return $this->hasMany(SuratUnit::class);
    }

    public function disposisis(): HasMany
    {
        return $this->hasMany(Disposisi::class);
    }

    public function komentars(): HasMany
    {
        return $this->hasMany(SuratKomentar::class);
    }

    public function riwayats(): HasMany
    {
        return $this->hasMany(SuratRiwayat::class);
    }

    public function ttds(): HasMany
    {
        return $this->hasMany(SuratTtd::class);
    }

    public function nomorSuratLogs(): HasMany
    {
        return $this->hasMany(NomorSuratLog::class);
    }

    // User pembuat surat (NULL jika Guest tanpa login)
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_pembuat_id');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media && $media->mime_type === 'application/pdf') {
            $this->addMediaConversion('thumb')
                ->width(300)
                ->height(400)
                ->sharpen(10)
                ->nonQueued();
        }
    }

    public function arsipSurats(): HasMany
    {
        return $this->hasMany(ArsipSurat::class);
    }


    // Helper method in pages
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

    public function scopeUntukUnit(Builder $query, int $unitId): Builder
    {
        return $query
            ->where('status_surat', '<>', 'DRAFT')
            ->where(function ($q) use ($unitId) {
                $q->whereHas(
                    'suratUnits',
                    fn($sq) => $sq->where('unit_kerja_id', $unitId)
                )
                ->orWhereHas(
                    'disposisis',
                    fn($dq) => $dq->where('unit_tujuan_id', $unitId)
                )
                ->orWhereHas(
                    'riwayats',
                    fn($rq) => $rq->where('unit_tujuan_id', $unitId)
                );
            });
    }

    public function scopeMasukLangsung(Builder $query, int $unitId): Builder
    {
        return $query
            ->where('status_surat', '<>', 'DRAFT')
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
            ->where('status_surat', '<>', 'DRAFT')
            ->whereHas(
                'disposisis',
                fn($q) =>
                $q->where('unit_tujuan_id', $unitId)
            )

            // untuk disposisi yang kembali ke awal (TODO jangan disentuh dulu)
            // ->orWhereHas(
            //     'disposisis',
            //     fn($q) =>
            //     $q->where('pembuat->unit_kerja_id', $unitId)
            // )
            ->whereDoesntHave(
                'suratUnits',
                fn($q) =>
                $q->where('unit_kerja_id', $unitId)
            );
    }
}
