<?php

namespace App\Filament\Resources\MahasiswaSurats\Pages;

use App\Filament\Resources\MahasiswaSurats\SuratResource;
use App\Filament\Resources\MahasiswaSurats\MahasiswaSuratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMahasiswaSurats extends ListRecords
{
    protected static string $resource = MahasiswaSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
