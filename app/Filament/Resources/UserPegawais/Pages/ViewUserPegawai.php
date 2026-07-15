<?php

namespace App\Filament\Resources\UserPegawais\Pages;

use App\Filament\Resources\UserPegawais\UserPegawaiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserPegawai extends ViewRecord
{
    protected static string $resource = UserPegawaiResource::class;

    public function getTitle(): string
    {
        return $this->getRecordTitle();
    }
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
