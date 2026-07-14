<?php

namespace App\Filament\Resources\UserPegawais\Pages;

use App\Filament\Resources\UserPegawais\UserPegawaiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUserPegawai extends EditRecord
{
    protected static string $resource = UserPegawaiResource::class;

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
