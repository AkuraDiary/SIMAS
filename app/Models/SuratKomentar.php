<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratKomentar extends Model
{
    protected $fillable = [
        'surat_id',
        'user_id',
        'unit_kerja_id',
        'pesan',
        'parent_id',
    ];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SuratKomentar::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SuratKomentar::class, 'parent_id');
    }
}
