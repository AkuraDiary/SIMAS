<?php

namespace App\Filament\Resources\UserMahasiswas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserMahasiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Mahasiswa')
                    ->description('Data pokok Mahasiswa.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('nim')
                            ->required()
                            ->unique(
                                table: 'users',
                                column: 'username',
                                modifyRuleUsing: fn ($rule, $record) => $record ? $rule->ignore($record->user_id) : $rule
                            )
                            ->disabled(fn(string $context): bool => $context === 'edit')
                            ->dehydrated(),
                        TextInput::make('nama_lengkap')
                            ->required(),
                        DatePicker::make('tanggal_lahir')
                            ->native(false)
                            ->displayFormat('d F Y'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Informasi Akademik')
                    ->description('Status dan penempatan program studi mahasiswa saat ini.')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        TextInput::make('tahun_masuk')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue((int) date('Y') + 1),
                        Select::make('status')
                            ->options([
                                'AKTIF' => 'Aktif',
                                'CUTI' => 'Cuti',
                                'LULUS' => 'Lulus',
                                'KELUAR' => 'Keluar',
                            ])
                            ->default('AKTIF')
                            ->required()
                            ->native(false),
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
                    ])
                    ->columnSpanFull()
                    ->columns(2),

                Section::make('Kontak')
                    ->description('Kontak Akun Mahasiswa')
                    ->icon('heroicon-o-at-symbol')
                    ->collapsible()
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->email)),
                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->afterStateHydrated(fn($component, $record) => $component->state($record?->user?->phone)),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }
}
