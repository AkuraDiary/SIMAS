<?php

namespace App\Filament\Resources\UserMahasiswas\Pages;

use App\Filament\Resources\UserMahasiswas\UserMahasiswaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserMahasiswa extends ViewRecord
{
    protected static string $resource = UserMahasiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
