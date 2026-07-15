<?php

namespace App\Filament\Resources\UserPegawais\Pages;

use App\Filament\Resources\UserPegawais\UserPegawaiResource;
use App\Services\UserProvisioningService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUserPegawai extends CreateRecord
{
    protected static string $resource = UserPegawaiResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(UserProvisioningService::class)->createPegawai($data);
    }
}
