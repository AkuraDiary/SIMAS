<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\UserPegawaiJabatan;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * assign_unit_kerja_id / assign_jabatan_id aren't columns on User (unit assignment
     * now lives in user_pegawai_jabatans), so they're pulled out here before the User
     * record is saved and applied afterwards.
     */
    protected ?array $pendingJabatan = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingJabatan = [
            'unit_kerja_id' => $data['assign_unit_kerja_id'] ?? null,
            'jabatan_id' => $data['assign_jabatan_id'] ?? null,
        ];

        unset($data['assign_unit_kerja_id'], $data['assign_jabatan_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $unitKerjaId = $this->pendingJabatan['unit_kerja_id'] ?? null;
        $jabatanId = $this->pendingJabatan['jabatan_id'] ?? null;

        if ($this->record->pegawai && $unitKerjaId && $jabatanId) {
            UserPegawaiJabatan::updateOrCreate(
                [
                    'user_pegawai_id' => $this->record->pegawai->id,
                    'status_jabatan' => 'AKTIF',
                ],
                [
                    'unit_kerja_id' => $unitKerjaId,
                    'jabatan_id' => $jabatanId,
                ]
            );
        }

        // redirect to list page
        $this->redirect(UserResource::getUrl());
    }
}
