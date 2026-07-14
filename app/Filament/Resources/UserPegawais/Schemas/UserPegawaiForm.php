<?php

namespace App\Filament\Resources\UserPegawais\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserPegawaiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                TextInput::make('nip'),
                TextInput::make('nama_lengkap')
                    ->required(),
            ]);
    }
}
