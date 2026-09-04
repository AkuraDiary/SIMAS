<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPegawaiJabatan extends Model
{
    use SoftDeletes;

    protected $table = 'user_pegawai_jabatans';

    protected $fillable = [
        'user_pegawai_id',
        'unit_kerja_id',
        'jabatan_id',
        'status_jabatan',
        'akses_surat_masuk',
        'can_disposisi',
    ];

    protected function casts(): array
    {
        return [
            'can_disposisi' => 'boolean',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(UserPegawai::class, 'user_pegawai_id');
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
