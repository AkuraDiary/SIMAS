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

        if (blank($data['nip'] ?? null)) {
            throw new \InvalidArgumentException('NIP wajib diisi untuk membuat akun pegawai.');
        }

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'username' => $data['nip'],
                'password' => Hash::make($data['nip']),
                'tipe_entitas' => 'STAF',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => false,
            ]);

            $pegawai = UserPegawai::create([
                'user_id' => $user->id,
                'nip' => $data['nip'],
                'nama_lengkap' => $data['nama_lengkap'],
            ]);

            foreach ($data['jabatan_assignments'] ?? [] as $assignment) {
                if (blank($assignment['unit_kerja_id'] ?? null) || blank($assignment['jabatan_id'] ?? null)) {
                    continue;
                }

                $pegawai->jabatans()->create([
                    'unit_kerja_id' => $assignment['unit_kerja_id'],
                    'jabatan_id' => $assignment['jabatan_id'],
                    'status_jabatan' => $assignment['status_jabatan'] ?? 'AKTIF',
                ]);
            }

            return $pegawai;
        });
    }


    public function updateJabatanAktif(UserPegawai $pegawai, ?int $unitKerjaId, ?int $jabatanId): void
    {
        if (blank($unitKerjaId) || blank($jabatanId)) {
            return;
        }

        $pegawai->jabatanAktif()->updateOrCreate(
            ['status_jabatan' => 'AKTIF'],
            ['unit_kerja_id' => $unitKerjaId, 'jabatan_id' => $jabatanId]
        );
    }

    public function syncJabatanAssignments(UserPegawai $pegawai, array $assignments): void
    {
        $pegawai->jabatans()->delete();

        foreach ($assignments as $assignment) {
            if (blank($assignment['unit_kerja_id'] ?? null) || blank($assignment['jabatan_id'] ?? null)) {
                continue;
            }

            $pegawai->jabatans()->create([
                'unit_kerja_id' => $assignment['unit_kerja_id'],
                'jabatan_id' => $assignment['jabatan_id'],
                'status_jabatan' => $assignment['status_jabatan'] ?? 'AKTIF',
            ]);
        }
    }
    public function createOrUpdatePegawaiFromImport(array $data): UserPegawai
{
    return DB::transaction(function () use ($data) {
        $pegawai = UserPegawai::where('nip', $data['nip'])->first();

        if (! $pegawai) {
            return $this->createPegawai([
                'nip' => $data['nip'],
                'nama_lengkap' => $data['nama_lengkap'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'jabatan_assignments' => [
                    [
                        'unit_kerja_id' => $data['unit_kerja_id'] ?? null,
                        'jabatan_id' => $data['jabatan_id'] ?? null,
                    ],
                ],
            ]);
        }

        $pegawai->update([
            'nama_lengkap' => $data['nama_lengkap'],
        ]);

        $pegawai->user?->update([
            'email' => $data['email'] ?? $pegawai->user->email,
            'phone' => $data['phone'] ?? $pegawai->user->phone,
        ]);

        $this->updateJabatanAktif(
            $pegawai,
            $data['unit_kerja_id'] ?? null,
            $data['jabatan_id'] ?? null
        );

        return $pegawai;
    });
}
}
