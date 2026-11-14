<?php

namespace App\Filament\Resources\AiTokens\Pages;

use App\Filament\Resources\AiTokens\AiTokenResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAiToken extends EditRecord
{
    protected static string $resource = AiTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
