<?php

namespace App\Filament\Resources\UnitKerjas\Pages;

use App\Filament\Resources\UnitKerjas\UnitKerjaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnitKerja extends CreateRecord
{
    protected static string $resource = UnitKerjaResource::class;
    protected function afterCreate(): void
    {
        // redirect to list page
        $this->redirect(UnitKerjaResource::getUrl());
    }
}
