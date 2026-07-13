<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMahasiswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserProvisioningService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createMahasiswa(array $mahasiswaData): UserMahasiswa
    {
        return DB::transaction(function () use ($mahasiswaData) {
            $user = User::create([
                'username' => $mahasiswaData['nim'],
                'password' => Hash::make($mahasiswaData['nim']), // TODO: replace once activation flow exists
                'tipe_entitas' => 'MAHASISWA',
                'is_active' => false, // pending activation
            ]);

            return UserMahasiswa::create([
                'user_id' => $user->id,
                'nim' => $mahasiswaData['nim'],
                'nama_lengkap' => $mahasiswaData['nama_lengkap'],
                'prodi_id' => $mahasiswaData['prodi_id'],
                'fakultas_id' => $mahasiswaData['fakultas_id'] ?? null,
                'tahun_masuk' => $mahasiswaData['tahun_masuk'] ?? null,
                'status' => 'AKTIF',
            ]);
        });
    }
}
