<?php

namespace App\Filament\Resources\UserMahasiswas\Pages;

use App\Filament\Resources\UserMahasiswas\UserMahasiswaResource;
use App\Services\UserProvisioningService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUserMahasiswa extends CreateRecord
{
    protected static string $resource = UserMahasiswaResource::class;

    /**
     * Bypasses Filament's default UserMahasiswa::create($data) — creating a mahasiswa
     * also has to provision a linked User account (default password = NIM, pending
     * activation), so the whole thing goes through UserProvisioningService instead.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(UserProvisioningService::class)->createMahasiswa($data);
    }
}
