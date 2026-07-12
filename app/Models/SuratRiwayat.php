<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratRiwayat extends Model
{
    protected $fillable = [
        'surat_id',
        'parent_id',
        'unit_asal_id',
        'unit_tujuan_id',
        'user_aktor_id',
        'status',
        'catatan',
        'expired_at',
        'actioned_at',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'actioned_at' => 'datetime',
        ];
    }

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SuratRiwayat::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SuratRiwayat::class, 'parent_id');
    }

    public function unitAsal(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_asal_id');
    }

    public function unitTujuan(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_tujuan_id');
    }

    public function aktor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_aktor_id');
    }
}
