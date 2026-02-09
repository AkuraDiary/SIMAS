<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\SuratResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

    protected function getSaveDraftAction(): Action
    {
        return Action::make('saveDraft')
            ->label('Simpan Draft')
            ->color('gray')
            ->action(function () {

                $data = $this->form->getState();

                // $draftTujuan = $data['draft_surat_units'] ?? [];
                // unset($data['draft_surat_units']);
                // // $this->record->draftTujuan()->delete();

                $lampirans = $data['lampirans'] ?? [];
                unset($data['lampirans']);

                // dd($data);
                $this->record->update($data);

                // $this->record->draftSuratUnits()->delete();
                // if (! empty($draftTujuan)) {
                //     $this->record->draftSuratUnits()->createMany(
                //         collect($draftTujuan)->map(fn($item) => [
                //             'unit_kerja_id' => $item['unit_kerja_id'],
                //             'jenis_tujuan'  => $item['jenis_tujuan'],
                //         ])->toArray()
                //     );
                // }

                if (! empty($lampirans)) {
                    $this->record->lampirans()->createMany(
                        collect($lampirans)->map(function ($path) {
                            return [
                                'path_file' => $path,
                                'nama_file' => basename($path),
                                'mime_type' => Storage::mimeType($path),
                                'size'      => Storage::size($path),
                            ];
                        })->toArray()
                    );
                }


                Notification::make()
                    ->title('Draft berhasil disimpan')
                    ->success()
                    ->send();
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

                // Kalau langsung kirim harus ada tujuannya
                if (empty($data['draft_surat_units'])) {
                    Notification::make()
                        ->title('Tujuan surat wajib diisi')
                        ->danger()
                        ->send();

                    return;
                }

                // Generate nomor surat & tanggal kirim
                $data['status_surat']   = 'TERKIRIM';
                $data['tanggal_kirim']  = now();

                $this->record->update($data);

                // $draftTujuan = $data['draft_surat_units'] ?? [];
                // unset($data['draft_surat_units']);

                // // Simpan tujuan ke pivot
                // $this->record->unitTujuan()->sync(
                //     collect($draftTujuan)->mapWithKeys(fn($item) => [
                //         $item['unit_kerja_id'] => [
                //             'jenis_tujuan' => $item['jenis_tujuan'],
                //         ],
                //     ])
                // );

                // Simpan tujuan ke pivot
                $this->record->unitTujuan()->sync(
                    collect($data['draft_surat_units'])->mapWithKeys(fn($item) => [
                        $item['unit_kerja_id'] => [
                            'jenis_tujuan' => $item['jenis_tujuan'],
                        ],
                    ])
                );

                // hapus tabel draft tujuan
                $this->record->draftTujuan()->delete();

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


    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
