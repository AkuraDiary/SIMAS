<?php

namespace App\Filament\Resources\UserMahasiswas\Pages;

use App\Filament\Resources\UserMahasiswas\UserMahasiswaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserMahasiswas extends ListRecords
{
    protected static string $resource = UserMahasiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
