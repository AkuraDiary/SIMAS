<?php

namespace App\Filament\Mahasiswa\Resources\Surats\Pages;

use App\Filament\Mahasiswa\Resources\Surats\SuratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSurats extends ListRecords
{
    protected static string $resource = SuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
