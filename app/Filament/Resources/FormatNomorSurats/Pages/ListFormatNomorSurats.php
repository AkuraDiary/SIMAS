<?php

namespace App\Filament\Resources\FormatNomorSurats\Pages;

use App\Filament\Resources\FormatNomorSurats\FormatNomorSuratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFormatNomorSurats extends ListRecords
{
    protected static string $resource = FormatNomorSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label("Buat Nomor Surat Baru"),
        ];
    }
}
