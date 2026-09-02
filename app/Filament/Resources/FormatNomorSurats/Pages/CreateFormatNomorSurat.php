<?php

namespace App\Filament\Resources\FormatNomorSurats\Pages;

use App\Filament\Resources\FormatNomorSurats\FormatNomorSuratResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFormatNomorSurat extends CreateRecord
{
    protected static string $resource = FormatNomorSuratResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Jika pembuatnya adalah Staf, otomatis kaitkan format ini ke Unit kerjanya
        if (auth()->user()?->tipe_entitas === 'STAF') {
            $data['unit_kerja_id'] = auth()->user()->unit_kerja_id;
        }

        return $data;
    }
}
