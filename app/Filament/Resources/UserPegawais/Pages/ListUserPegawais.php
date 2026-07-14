<?php

namespace App\Filament\Resources\UserPegawais\Pages;

use App\Filament\Resources\UserPegawais\UserPegawaiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserPegawais extends ListRecords
{
    protected static string $resource = UserPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
