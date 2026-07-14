<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserMahasiswa extends Model
{
    use SoftDeletes;

    protected $table = 'user_mahasiswa';

    protected $fillable = [
        'user_id',
        'nim',
        'nama_lengkap',
        'tanggal_lahir',
        'tahun_masuk',
        'status',
        'prodi_id',
        'fakultas_id',
    ];

    public function getRouteKeyName(): string
    {
        return 'nim';
    }

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'prodi_id');
    }

    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'fakultas_id');
    }
}
