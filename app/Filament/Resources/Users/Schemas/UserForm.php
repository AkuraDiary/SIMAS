<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create')
                    ->label('Password'),
                TextInput::make('nama_lengkap')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Select::make('peran')
                    ->options(['stafunit' => 'Staf Unit'])
                    ->required(),
                Select::make('status_user')
                    ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])
                    ->required(),
                Select::make('unit_kerja_id')
                    ->relationship('unitKerja', 'nama_unit')
                    ->searchable()
                    ->label("Unit Kerja")
                    ->preload(),

            ]);
    }
}
