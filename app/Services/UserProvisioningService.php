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
                'email' => $mahasiswaData['email'] ?? null,
                'phone' => $mahasiswaData['phone'] ?? null,
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

    public function createOrUpdateMahasiswaFromImport(array $data): UserMahasiswa
    {
        return DB::transaction(function () use ($data) {
            $mahasiswa = UserMahasiswa::where('nim', $data['nim'])->first();

            if (! $mahasiswa) {
                return $this->createMahasiswa($data);
            }

            $mahasiswa->update([
                'nama_lengkap' => $data['nama_lengkap'],
                'tanggal_lahir' => $data['tanggal_lahir'] ?? $mahasiswa->tanggal_lahir,
                'tahun_masuk' => $data['tahun_masuk'] ?? $mahasiswa->tahun_masuk,
                'status' => $data['status'] ?? $mahasiswa->status,
                'prodi_id' => $data['prodi_id'] ?? $mahasiswa->prodi_id,
                'fakultas_id' => $data['fakultas_id'] ?? $mahasiswa->fakultas_id,
            ]);

            $mahasiswa->user?->update([
                'email' => $data['email'] ?? $mahasiswa->user->email,
                'phone' => $data['phone'] ?? $mahasiswa->user->phone,
            ]);

            return $mahasiswa;
        });
    }
}
