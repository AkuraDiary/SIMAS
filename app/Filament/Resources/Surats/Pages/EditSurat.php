<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\SuratResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\isEmpty;

class EditSurat extends EditRecord
{
    protected static string $resource = SuratResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            SuratResource::getUrl('index', ['scope' => 'draft']) => 'Draft Surat',
            '#' => $this->record->nomor_surat,
            'Edit Surat',
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveDraftAction(),
            $this->getSendNowAction(),
            $this->getCancelAction(),
        ];
    }

    protected function afterSave(): void
    {
        $surat = $this->record;

        $unitIds = $this->data['unitTujuan'] ?? [];

        foreach ($unitIds as $index => $unitId) {
            $surat->unitTujuan()->updateExistingPivot($unitId, [
                'jenis_tujuan' => $index === 0 ? 'utama' : 'tembusan',
                'status_baca' => 'BELUM',
            ]);
        }
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
           
            ->before(function (Action $action) {

                $unitIds = $this->data['unitTujuan'] ?? [];

                if (empty($unitIds)) {
                    Notification::make()
                        ->title('Tujuan Tidak Boleh Kosong')
                        ->danger()
                        ->send();
                    $action->halt();
                }

                $surat = $this->record;

                foreach ($unitIds as $index => $unitId) {
                    $surat->unitTujuan()->updateExistingPivot($unitId, [
                        'jenis_tujuan' => $index === 0 ? 'utama' : 'tembusan',
                        'status_baca' => 'BELUM',
                    ]);
                }
            })
            ->action(function () {

                $data = $this->form->getState();

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
