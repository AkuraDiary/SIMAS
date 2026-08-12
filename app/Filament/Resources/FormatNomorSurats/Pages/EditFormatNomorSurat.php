<?php

namespace App\Filament\Resources\FormatNomorSurats\Pages;

use App\Filament\Resources\FormatNomorSurats\FormatNomorSuratResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditFormatNomorSurat extends EditRecord
{
    protected static string $resource = FormatNomorSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
