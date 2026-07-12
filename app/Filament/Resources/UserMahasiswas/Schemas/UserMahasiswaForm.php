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
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                TextInput::make('nim')
                    ->required(),
                TextInput::make('nama_lengkap')
                    ->required(),
                DatePicker::make('tanggal_lahir'),
                TextInput::make('tahun_masuk'),
                Select::make('status')
                    ->options(['AKTIF' => 'A k t i f', 'CUTI' => 'C u t i', 'LULUS' => 'L u l u s', 'KELUAR' => 'K e l u a r'])
                    ->default('AKTIF')
                    ->required(),
                Select::make('prodi_id')
                    ->relationship('prodi', 'id'),
                Select::make('fakultas_id')
                    ->relationship('fakultas', 'id'),
            ]);
    }
}
