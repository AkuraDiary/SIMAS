<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\SuratResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class CreateSurat extends CreateRecord
{
    protected static string $resource = SuratResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getSaveDraftAction(),
            $this->getSendNowAction(),
            $this->getCancelAction(),
        ];
    }

    // Tujuan & Lampiran tidak terbaca
    protected function getSaveDraftAction(): Action
    {
        return Action::make('saveDraft')
            ->label('Simpan Draft')
            ->color('primary')
            ->action(function () {

                $data = $this->form->getState();

                $data['status_surat'] = 'DRAFT';

                $this->handleRecordCreation($data);
                
                Notification::make()
                    ->title('Draft berhasil disimpan')
                    ->success()
                    ->send();
            });
    }


    // Belum Dites
    protected function getSendNowAction(): Action
    {
        return Action::make('sendNow')
            ->label('Kirim Langsung')
            ->color('primary')
            ->outlined()
            ->requiresConfirmation()
            ->action(function () {

                $data = $this->form->getState();
                
                // Kalau langsung kirim harus ada tujuannya
                if (empty($data['draft_surat_units'])) {
                    Notification::make()
                        ->title('Tujuan surat wajib diisi')
                        ->danger()
                        ->send();
                    return;
                }

                $data['status_surat']   = 'TERKIRIM';
                $data['tanggal_kirim']  = now();
    
                $surat = $this->handleRecordCreation($data);
            

                Notification::make()
                    ->title('Surat berhasil dikirim')
                    ->success()
                    ->send();

                $this->redirect(SuratResource::getUrl());
            });
    }

    protected function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Batal')
            ->color('danger')
            ->url(SuratResource::getUrl())
            ->outlined();
    }
}
