<?php

namespace App\Filament\Resources\UserPegawais\Schemas;

use App\Models\Jabatan;
use App\Models\UnitKerja;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserPegawaiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // No user_id field — the linked User is provisioned automatically
                // (username/password = NIP), see UserProvisioningService::createStaf().
                TextInput::make('nip')
                    ->required()
                    ->disabled(fn(string $context): bool => $context === 'edit')
                    ->dehydrated(),
                TextInput::make('nama_lengkap')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->email)),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->phone)),

                Repeater::make('jabatan_assignments')
                    ->label('Jabatan')
                    ->schema([
                        Select::make('unit_kerja_id')
                            ->label('Unit Kerja')
                            ->options(fn() => UnitKerja::query()->where('is_active', true)->pluck('nama_unit', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('jabatan_id')
                            ->label('Jabatan')
                            ->options(fn() => Jabatan::query()->pluck('nama_jabatan', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Tambah Jabatan')
                    ->defaultItems(1)
                    ->visibleOn('create'),

                Select::make('unit_kerja_id')
                    ->label('Unit Kerja')
                    ->options(fn() => UnitKerja::query()->where('is_active', true)->pluck('nama_unit', 'id'))
                    ->searchable()
                    ->preload()
                    ->visibleOn('edit')
                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->jabatanAktifSatu?->unit_kerja_id)),
                Select::make('jabatan_id')
                    ->label('Jabatan')
                    ->options(fn() => Jabatan::query()->pluck('nama_jabatan', 'id'))
                    ->searchable()
                    ->preload()
                    ->visibleOn('edit')
                    ->afterStateHydrated(fn($component, $record) => $component->state($record?->jabatanAktifSatu?->jabatan_id)),
                // Select::make('unit_kerja_id')
                //     ->label('Unit Kerja')
                //     ->options(fn() => UnitKerja::query()->where('is_active', true)->pluck('nama_unit', 'id'))
                //     ->searchable()
                //     ->preload()
                //     ->afterStateHydrated(fn($component, $record) => $component->state($record?->jabatanAktifSatu?->unit_kerja_id)),
                // Select::make('jabatan_id')
                //     ->label('Jabatan')
                //     ->options(fn() => Jabatan::query()->pluck('nama_jabatan', 'id'))
                //     ->searchable()
                //     ->preload()
                //     ->afterStateHydrated(fn($component, $record) => $component->state($record?->jabatanAktifSatu?->jabatan_id)),
            ]);
    }
}
