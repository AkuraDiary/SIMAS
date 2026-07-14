<?php

namespace App\Filament\Resources\UserPegawais\Pages;

use App\Filament\Resources\UserPegawais\UserPegawaiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserPegawai extends ViewRecord
{
    protected static string $resource = UserPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
