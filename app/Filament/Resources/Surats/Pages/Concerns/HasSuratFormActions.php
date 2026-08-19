<?php

namespace App\Filament\Resources\Surats\Pages\Concerns;

use App\Filament\Resources\Surats\SuratResource;
use App\Services\SuratRoutingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

use function Illuminate\Support\now;

trait HasSuratFormActions
{
    protected function getFormActions(): array
    {
        return [
            $this->getSaveDraftAction(),
            $this->getSubmitAction(),
            $this->getCancelAction(),
        ];
    }

    protected function getSaveDraftAction(): Action
    {
        return Action::make('saveDraft')
            ->label('Simpan Draft')
            ->color('primary')
            ->outlined()
            ->action(function () {
                $this->data['status_surat'] = 'DRAFT';

                if ($this instanceof CreateRecord) {
                    $this->create();
                } else {
                    $this->save();
                }

                Notification::make()
                    ->title('Draft berhasil disimpan')
                    ->success()
                    ->send();

                $this->redirect(SuratResource::getUrl('index', ['scope' => 'draft']));
            });
    }

    protected function getSubmitAction(): Action
    {
        return Action::make('submitSurat')
            ->label('Kirim / Pengajuan')
            ->color('success')
            ->before(function (Action $action) {
                $unitIds = $this->data['unitTujuan'] ?? [];
                if (empty($unitIds)) {
                    Notification::make()
                        ->title('Tujuan Unit Tidak Boleh Kosong')
                        ->danger()
                        ->send();
                    $action->halt();
                }
            })
            ->action(function () {
                if ($this instanceof CreateRecord) {
                    $this->create();
                } else {
                    $this->save();
                }

                $surat = $this->record;
                $surat->tanggal_kirim = now();
                $unitTujuan = $this->data['unitTujuan'][0] ?? $surat->unit_pengirim_id;

                app(SuratRoutingService::class)->submitForApproval(
                    surat: $surat,
                    unitTujuanId: (int) $unitTujuan,
                    catatan: ''
                );

                $unitIds = $this->data['unitTujuan'] ?? [];
                foreach ($unitIds as $uId) {
                    $targetUsers = \App\Models\User::ofUnitKerja($uId)->get();
                    if ($targetUsers->isNotEmpty()) {
                        Notification::make()
                            ->title('Surat Masuk Baru')
                            ->body("Ada surat masuk baru dari " . ($surat->unitPengirim?->nama_unit ?? 'Luar') . ": " . $surat->perihal)
                            ->info()
                            ->sendToDatabase($targetUsers);
                    }
                }

                Notification::make()
                    ->title('Surat berhasil dikirim untuk diproses')
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
