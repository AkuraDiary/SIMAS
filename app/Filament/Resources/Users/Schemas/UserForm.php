<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Jabatan;
use App\Models\UnitKerja;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable(true)
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create')
                    ->label('Password'),
                // Identity fields now live on user_pegawai, saved automatically via
                // Filament's relationship-dot-notation support for the pegawai() HasOne relation.
                TextInput::make('pegawai.nip')
                    ->label('NIP')
                    ->nullable(),
                TextInput::make('pegawai.nama_lengkap')
                    ->label('Nama Lengkap')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->nullable(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Select::make('tipe_entitas')
                    ->options(['STAF' => 'Staf Unit'])
                    ->default('STAF')
                    ->required(),
                // Not a real relationship (a user's unit is now reached via
                // user_pegawai_jabatans), handled manually in Create/EditUser pages.
                Select::make('assign_unit_kerja_id')
                    ->label('Unit Kerja')
                    ->options(fn() => UnitKerja::query()->where('is_active', true)->pluck('nama_unit', 'id'))
                    ->searchable()
                    ->preload()
                    ->afterStateHydrated(function (Select $component, $record) {
                        if ($record) {
                            $component->state($record->jabatanAktif?->unit_kerja_id);
                        }
                    }),
                Select::make('assign_jabatan_id')
                    ->label('Jabatan')
                    ->options(fn() => Jabatan::query()->pluck('nama_jabatan', 'id'))
                    ->searchable()
                    ->preload()
                    ->afterStateHydrated(function (Select $component, $record) {
                        if ($record) {
                            $component->state($record->jabatanAktif?->jabatan_id);
                        }
                    }),
            ]);
    }
}
