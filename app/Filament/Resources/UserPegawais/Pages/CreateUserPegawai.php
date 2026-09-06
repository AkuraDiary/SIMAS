<?php

namespace App\Filament\Resources\UserPegawais\Pages;

use App\Filament\Resources\UserPegawais\UserPegawaiResource;
use App\Services\UserProvisioningService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUserPegawai extends CreateRecord
{
    protected static string $resource = UserPegawaiResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(UserProvisioningService::class)->createPegawai($data);
    }

    protected function afterCreate(): void
    {
        $opsi = $this->data['opsi_aktivasi'] ?? 'nanti';
        if ($opsi !== 'nanti' && $this->record->user) {
            $result = app(\App\Services\AccountActivationService::class)->sendActivationLink($this->record->user, $opsi);

            if ($result['status'] ?? false) {
                \Filament\Notifications\Notification::make()
                    ->title("Tautan aktivasi berhasil dikirim via " . strtoupper($opsi))
                    ->success()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title("Akun terbuat, namun gagal mengirim link: " . ($result['reason'] ?? 'Kesalahan jaringan'))
                    ->warning()
                    ->send();
            }
        }
    }
}
