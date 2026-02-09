<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\SuratResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EditSurat extends EditRecord
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


    // Tujuan Unit tidak terbaca
    protected function getSaveDraftAction(): Action
    {
        return Action::make('saveDraft')
            ->label('Simpan Draft')
            ->color('primary')
            ->outlined()
            ->action(function () {

                $data = $this->form->getState();

                $this->record->update($data);

                Notification::make()
                    ->title('Draft berhasil disimpan')
                    ->success()
                    ->send();

                    $this->redirect(SuratResource::getUrl('index', ['scope' => 'draft']));
            });
    }



    protected function getSendNowAction(): Action
    {
        return Action::make('sendNow')
            ->label('Kirim Langsung')
            ->color('primary')
            ->requiresConfirmation()
            ->action(function () {

                $data = $this->form->getState();

                // validasi tujuan
                if (empty($data['unitTujuan']) || count($data['unitTujuan']) === 0) {
                    Notification::make()
                        ->title('Tujuan wajib diisi sebelum surat dikirim')
                        ->danger()
                        ->send();
    
                    return;
                }

                // Generate nomor surat & tanggal kirim
                $data['status_surat']   = 'TERKIRIM';
                $data['tanggal_kirim']  = now();

                $this->record->update($data);

                Notification::make()
                    ->title('Surat berhasil dikirim')
                    ->success()
                    ->send();

                $this->redirect(SuratResource::getUrl('index', ['scope' => 'keluar']));
                
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
