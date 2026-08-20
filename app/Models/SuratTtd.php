<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratTtd extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'surat_id',
        'user_id',
        'tipe',
        'is_visible',
        'jabatan_saat_ttd',
        'unit_saat_ttd',
        'halaman',
        'posisi_x',
        'posisi_y',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'signed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
