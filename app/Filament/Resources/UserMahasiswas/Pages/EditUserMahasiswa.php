<?php

namespace App\Filament\Resources\UserMahasiswas\Pages;

use App\Filament\Resources\UserMahasiswas\UserMahasiswaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUserMahasiswa extends EditRecord
{
    protected static string $resource = UserMahasiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
