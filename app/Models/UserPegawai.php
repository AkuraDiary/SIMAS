<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPegawai extends Model
{
    use SoftDeletes;

    protected $table = 'user_pegawai';

    protected $fillable = [
        'user_id',
        'nip',
        'nama_lengkap',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function getRouteKeyName(): string
    {
        return 'nip';
    }

    public function jabatans(): HasMany
    {
        return $this->hasMany(UserPegawaiJabatan::class);
    }

    public function jabatanAktif(): HasMany
    {
        return $this->jabatans()->where('status_jabatan', 'AKTIF');
    }
}
