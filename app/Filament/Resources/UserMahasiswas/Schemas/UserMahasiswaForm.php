<?php

namespace App\Filament\Resources\UserMahasiswas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserMahasiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Removed user_id field — the linked User account is provisioned
                // automatically (via UserProvisioningService) when a mahasiswa is
                // created, not selected from existing accounts. See CreateUserMahasiswa.

                TextInput::make('nim')
                    ->required()
                    ->disabled(fn(string $context): bool => $context === 'edit')
                    ->dehydrated(),
                TextInput::make('nama_lengkap')
                    ->required(),
                DatePicker::make('tanggal_lahir'),
                TextInput::make('tahun_masuk')
                    ->numeric(),
                Select::make('status')
                    ->label("Status Mahasiswa")
                    ->options([
                        'AKTIF' => 'Aktif',
                        'CUTI' => 'Cuti',
                        'LULUS' => 'Lulus',
                        'KELUAR' => 'Keluar',
                    ])
                    ->default('AKTIF')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->email)),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->phone)),
                Select::make('prodi_id')
                    ->label('Prodi')
                    ->relationship('prodi', 'nama_unit')
                    ->searchable()
                    ->preload(),
                Select::make('fakultas_id')
                    ->label('Fakultas')
                    ->relationship('fakultas', 'nama_unit')
                    ->searchable()
                    ->preload(),

            ]);
    }
}
