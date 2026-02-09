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

    protected function getSaveDraftAction(): Action
    {
        return Action::make('saveDraft')
            ->label('Simpan Draft')
            ->color('primary')
            ->action(function () {

                $data = $this->form->getState();

                $data['status_surat'] = 'DRAFT';

                $draftTujuan = $data['draftSuratUnits'] ?? [];
                $lampirans = $data['lampirans'] ?? [];
                // unset($data['draft_surat_units']);
                unset($data['lampirans']);

                dd($data);

                $surat = $this->handleRecordCreation($data);
                

                // if (! empty($draftTujuan)) {
                //     $surat->draftSuratUnits()->createMany(
                //         collect($draftTujuan)->map(fn($item) => [
                //             'unit_kerja_id' => $item['unit_kerja_id'],
                //             'jenis_tujuan'  => $item['jenis_tujuan'],
                //         ])->toArray()
                //     );
                // }

                // if (! empty($lampirans)) {
                //     $surat->lampirans()->createMany(
                //         collect($lampirans)->map(function ($path) {
                //             return [
                //                 'path_file' => $path,
                //                 'nama_file' => basename($path),
                //                 'mime_type' => Storage::mimeType($path),
                //                 'size'      => Storage::size($path),
                //             ];
                //         })->toArray()
                //     );
                // }

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
            ->outlined()
            ->requiresConfirmation()
            ->action(function () {

                $data = $this->form->getState();
                // dd($data);
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

                // Strip lampiran & draft Tujuan
                $draftTujuan = $data['draft_surat_units'] ?? [];
                $lampirans = $data['lampirans'] ?? [];
                unset($data['draft_surat_units']);
                unset($data['lampirans']);

                $surat = $this->handleRecordCreation($data);

                // Simpan tujuan ke pivot

                $surat->unitTujuan()->createMany(
                    collect($draftTujuan)->mapWithKeys(fn($item) => [
                        $item['unit_kerja_id'] => [
                            'jenis_tujuan' => $item['jenis_tujuan'],
                        ],
                    ])
                );
                if (! empty($lampirans)) {
                    $surat->lampirans()->createMany(
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
                

                // hapus tabel draft tujuan
                $surat->draftTujuan()->delete();


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
