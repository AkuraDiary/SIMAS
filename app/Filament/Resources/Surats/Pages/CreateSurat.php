<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\SuratResource;
use App\Models\Surat;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\isEmpty;

class CreateSurat extends CreateRecord
{
    protected static string $resource = SuratResource::class;
    
    protected static ?string $title = 'Buat Surat';

    public function getBreadcrumbs(): array
    {
        return [
            SuratResource::getUrl('index', ['scope' => Request::query('draft')]) => 'Draft Surat',
            '#' => 'Buat Surat Baru',
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
            ->before(function (Action $action) {
                $unitIds = $this->data['unitTujuan'] ?? [];

                if (isEmpty($unitIds)) {
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
