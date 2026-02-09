<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\SuratResource;
use App\Models\Surat;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
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

    protected function afterCreate(): void
    {
        $surat = $this->record;

        $unitIds = $this->data['unitTujuan'] ?? [];

        foreach ($unitIds as $index => $unitId) {
            $surat->unitTujuan()->updateExistingPivot($unitId, [
                'jenis_tujuan' => $index === 0 ? 'utama' : 'tembusan',
                'status_baca' => 'BELUM',
            ]);
        }

        // dd($unitIds);
    }

    protected function getSaveDraftAction(): Action
    {
        return Action::make('saveDraft')
            ->label('Simpan Draft')
            ->color('primary')
            ->action(function () {

                $data = $this->form->getState();


                $data['status_surat'] = 'DRAFT';

                $this->create();
                Notification::make()
                    ->title('Draft berhasil disimpan')
                    ->success()
                    ->send();

                    $this->redirect(SuratResource::getUrl('index', ['scope' => 'draft']));
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
                // if (empty($data['unitTujuan']) || count($data['unitTujuan']) === 0) {
                //     Notification::make()
                //         ->title('Tujuan wajib diisi sebelum surat dikirim')
                //         ->danger()
                //         ->send();
    
                //     return;
                // }

                $data['status_surat']   = 'TERKIRIM';
                $data['tanggal_kirim']  = now();

                $this->create();


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
}
