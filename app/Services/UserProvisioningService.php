<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMahasiswa;
use App\Models\UserPegawai;
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

    public function createMahasiswa(array $data): UserMahasiswa
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'username' => $data['nim'],
                'password' => Hash::make($data['nim']), // TODO: replace once activation flow exists
                'tipe_entitas' => 'MAHASISWA',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => false, // pending activation
            ]);

            return UserMahasiswa::create([
                'user_id' => $user->id,
                'nim' => $data['nim'],
                'nama_lengkap' => $data['nama_lengkap'],
                'prodi_id' => $data['prodi_id'],
                'fakultas_id' => $data['fakultas_id'] ?? null,
                'tahun_masuk' => $data['tahun_masuk'] ?? null,
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


    public function createPegawai(array $data): UserPegawai
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'username' => $data['nip'],
                'password' => Hash::make($data['nip']), // TODO: replace once activation flow exists
                'tipe_entitas' => 'STAF',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => false, // pending activation
            ]);

            return UserPegawai::create([
                'user_id' => $user->id,
                'nip' => $data['nip'],
                'nama_lengkap' => $data['nama_lengkap'],
            ]);
        });
    }

    public function createOrUpdatePegawaiFromImport(array $data): UserPegawai
    {
        return DB::transaction(function () use ($data) {
            $pegawai = UserPegawai::where('nip', $data['nip'])->first();

            if (! $pegawai) {
                return $this->createMahasiswa($data);
            }

            $pegawai->update([
                'nama_lengkap' => $data['nama_lengkap'], 
            ]);

            $pegawai->user?->update([
                'email' => $data['email'] ?? $pegawai->user->email,
                'phone' => $data['phone'] ?? $pegawai->user->phone,
            ]);

            return $pegawai;
        });
    }
}
