<?php

namespace App\Filament\Resources\UserMahasiswas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserMahasiswaInfolist
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Mahasiswa')
                    ->icon('heroicon-o-identification')

                    ->schema([
                        TextEntry::make('nama_lengkap'),
                        TextEntry::make('nim')
                            ->copyable(),
                        TextEntry::make('tanggal_lahir')
                            ->date()
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Informasi Akademik')
                    ->icon('heroicon-o-academic-cap')

                    ->schema([
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('tahun_masuk')
                            ->placeholder('-'),
                        TextEntry::make('prodi.nama_unit')
                            ->label('Prodi')
                            ->placeholder('-'),
                        TextEntry::make('fakultas.nama_unit')
                            ->label('Fakultas')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make('Informasi Akun & Kontak')
                    ->icon('heroicon-o-at-symbol')

                    ->schema([
                        TextEntry::make('user.username')
                            ->label('Username')
                            ->copyable(),
                        TextEntry::make('user.email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('user.phone')
                            ->label('Nomor Telepon')
                            ->placeholder('-'),
                        TextEntry::make('user.is_active')
                            ->label('Status Akun')
                            ->badge()
                            ->formatStateUsing(fn(bool $state): string => $state ? 'Aktif' : 'Belum Aktivasi')
                            ->color(fn(bool $state): string => $state ? 'success' : 'gray'),
                    ])
                    ->columns(2),

                Section::make('Metadata')
                    ->icon('heroicon-o-clock')

                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')->dateTime()->placeholder('-'),
                        TextEntry::make('updated_at')->dateTime()->placeholder('-'),
                        TextEntry::make('deleted_at')->dateTime()->visible(fn($record) => $record->trashed()),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
