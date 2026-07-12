<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormatNomorSurat extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_kerja_id',
        'nama_format',
        'format_penomoran',
        'nomor_urut_terakhir',
        'tahun',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function nomorSuratLogs(): HasMany
    {
        return $this->hasMany(NomorSuratLog::class, 'format_nomor_id');
    }
}
