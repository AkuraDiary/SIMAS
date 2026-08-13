<?php

namespace App\Filament\Resources\MahasiswaSurats\Pages;

use App\Filament\Resources\MahasiswaSurats\MahasiswaSuratResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMahasiswaSurat extends EditRecord
{
    protected static string $resource = MahasiswaSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
