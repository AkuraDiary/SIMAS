<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\UserPegawaiJabatan;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * assign_unit_kerja_id / assign_jabatan_id aren't columns on User (unit assignment
     * now lives in user_pegawai_jabatans), so they're pulled out here before the User
     * record is created and applied afterwards.
     */
    protected ?array $pendingJabatan = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingJabatan = [
            'unit_kerja_id' => $data['assign_unit_kerja_id'] ?? null,
            'jabatan_id' => $data['assign_jabatan_id'] ?? null,
        ];

        unset($data['assign_unit_kerja_id'], $data['assign_jabatan_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $unitKerjaId = $this->pendingJabatan['unit_kerja_id'] ?? null;
        $jabatanId = $this->pendingJabatan['jabatan_id'] ?? null;

        // pegawai is created automatically by Filament via the pegawai.* dot-notation
        // fields in UserForm, since User::pegawai() is a real HasOne relation.
        if ($unitKerjaId && $jabatanId && $this->record->pegawai) {
            UserPegawaiJabatan::create([
                'user_pegawai_id' => $this->record->pegawai->id,
                'unit_kerja_id' => $unitKerjaId,
                'jabatan_id' => $jabatanId,
                'status_jabatan' => 'AKTIF',
            ]);
        }

        // redirect to list page
        $this->redirect(UserResource::getUrl());
    }
}
