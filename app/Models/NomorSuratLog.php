<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NomorSuratLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'surat_id',
        'format_nomor_id',
        'nomor_urut',
        'nomor_lengkap',
        'is_backdate',
        'is_manual',
        'alasan_backdate',
        'user_id',
        'tanggal_ditetapkan',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_backdate' => 'boolean',
            'is_manual' => 'boolean',
            'tanggal_ditetapkan' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function formatNomor(): BelongsTo
    {
        return $this->belongsTo(FormatNomorSurat::class, 'format_nomor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
