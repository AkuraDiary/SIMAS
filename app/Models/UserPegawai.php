<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

        protected static function booted(): void
    {
        static::deleting(function (UserPegawai $pegawai) {
            $user = $pegawai->user()->withTrashed()->first();

            if ($pegawai->isForceDeleting()) {
                $user?->forceDelete();
            } else {
                // Soft deleting: beri suffix __deleted_{timestamp} untuk membebaskan NIP & username
                $timestamp = time();
                if (!str_contains($pegawai->nip ?? '', '__deleted_')) {
                    $pegawai->nip = "{$pegawai->nip}__deleted_{$timestamp}";
                    $pegawai->saveQuietly();
                }

                if ($user && !str_contains($user->username ?? '', '__deleted_')) {
                    $user->username = "{$user->username}__deleted_{$timestamp}";
                    if ($user->email && !str_contains($user->email, '__deleted_')) {
                        $user->email = "{$user->email}__deleted_{$timestamp}";
                    }
                    $user->saveQuietly();
                    $user->delete();
                }
            }
        });

        static::restoring(function (UserPegawai $pegawai) {
            // Bersihkan suffix __deleted_
            $cleanNip = preg_replace('/__deleted_\d+$/', '', $pegawai->nip);

            // Cek apakah ada UserPegawai aktif lain yang sudah menggunakan clean NIP ini
            if (UserPegawai::where('nip', $cleanNip)->where('id', '!=', $pegawai->id)->exists()) {
                throw new \Exception("Gagal memulihkan: NIP {$cleanNip} saat ini sudah aktif digunakan oleh data pegawai lain.");
            }

            $user = $pegawai->user()->withTrashed()->first();
            if ($user) {
                $cleanUsername = preg_replace('/__deleted_\d+$/', '', $user->username);
                $cleanEmail = $user->email ? preg_replace('/__deleted_\d+$/', '', $user->email) : null;

                if (User::where('username', $cleanUsername)->where('id', '!=', $user->id)->exists()) {
                    throw new \Exception("Gagal memulihkan: Akun login untuk NIP {$cleanUsername} saat ini sudah aktif digunakan.");
                }

                $user->username = $cleanUsername;
                $user->email = $cleanEmail;
                $user->saveQuietly();
                $user->restore();
            }

            $pegawai->nip = $cleanNip;
        });
    }

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
        return $this->hasMany(UserPegawaiJabatan::class)->latest();
    }

    public function jabatanAktifSatu(): HasOne
    {
        return $this->hasOne(UserPegawaiJabatan::class)
            ->where('status_jabatan', 'AKTIF')
            ->latestOfMany();
    }

    public function jabatanAktif(): HasMany
    {
        return $this->jabatans()->where('status_jabatan', 'AKTIF');
    }
}
