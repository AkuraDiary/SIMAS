<?php

namespace App\Filament\Resources\UnitKerjas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UnitKerjaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_unit')
                    ->required(),
                Select::make('jenis_unit')
                    ->options(['fakultas' => 'Fakultas', 'non-fakultas' => 'Non fakultas'])
                    ->required(),
                Select::make('status_unit')
                    ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])
                    ->required(),
            ]);
    }
}
