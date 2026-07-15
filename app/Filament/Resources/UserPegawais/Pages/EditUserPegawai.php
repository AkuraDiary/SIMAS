<?php

namespace App\Filament\Resources\UserPegawais\Pages;

use App\Filament\Resources\UserPegawais\UserPegawaiResource;
use App\Services\UserProvisioningService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUserPegawai extends EditRecord
{
    protected static string $resource = UserPegawaiResource::class;

    protected ?array $pendingData = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingData = [
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'unit_kerja_id' => $data['unit_kerja_id'] ?? null,
            'jabatan_id' => $data['jabatan_id'] ?? null,
        ];

        unset($data['email'], $data['phone'], $data['unit_kerja_id'], $data['jabatan_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->user?->update([
            'email' => $this->pendingData['email'],
            'phone' => $this->pendingData['phone'],
        ]);

        app(UserProvisioningService::class)->updateJabatanAktif(
            $this->record,
            $this->pendingData['unit_kerja_id'],
            $this->pendingData['jabatan_id']
        );
    }
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
