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

    protected static function booted(): void
    {
        static::deleting(function (UserMahasiswa $mahasiswa) {
            $user = $mahasiswa->user()->withTrashed()->first();

            if ($mahasiswa->isForceDeleting()) {
                $user?->forceDelete();
            } else {
                // Soft deleting: beri suffix __deleted_{timestamp} untuk membebaskan NIM & username
                $timestamp = time();
                if (!str_contains($mahasiswa->nim ?? '', '__deleted_')) {
                    $mahasiswa->nim = "{$mahasiswa->nim}__deleted_{$timestamp}";
                    $mahasiswa->saveQuietly();
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

        static::restoring(function (UserMahasiswa $mahasiswa) {
            // Bersihkan suffix __deleted_
            $cleanNim = preg_replace('/__deleted_\d+$/', '', $mahasiswa->nim);

            // Cek apakah ada UserMahasiswa aktif lain yang sudah menggunakan clean NIM ini
            if (UserMahasiswa::where('nim', $cleanNim)->where('id', '!=', $mahasiswa->id)->exists()) {
                throw new \Exception("Gagal memulihkan: NIM {$cleanNim} saat ini sudah aktif digunakan oleh data mahasiswa lain.");
            }

            $user = $mahasiswa->user()->withTrashed()->first();
            if ($user) {
                $cleanUsername = preg_replace('/__deleted_\d+$/', '', $user->username);
                $cleanEmail = $user->email ? preg_replace('/__deleted_\d+$/', '', $user->email) : null;

                if (User::where('username', $cleanUsername)->where('id', '!=', $user->id)->exists()) {
                    throw new \Exception("Gagal memulihkan: Akun login untuk NIM {$cleanUsername} saat ini sudah aktif digunakan.");
                }

                $user->username = $cleanUsername;
                $user->email = $cleanEmail;
                $user->saveQuietly();
                $user->restore();
            }

            $mahasiswa->nim = $cleanNim;
        });
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
