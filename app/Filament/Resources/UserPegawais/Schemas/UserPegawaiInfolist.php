<?php

namespace App\Filament\Resources\UserPegawais\Schemas;

use App\Models\UserPegawai;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserPegawaiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Pegawai')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextEntry::make('nip')
                            ->placeholder('-'),
                        TextEntry::make('nama_lengkap'),

                    ]),

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


                Section::make('Informasi Jabatan')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        RepeatableEntry::make('jabatans')
                            ->label('')
                            ->schema([
                                TextEntry::make('jabatan.nama_jabatan')
                                    ->label('Jabatan'),
                                TextEntry::make('unitKerja.nama_unit')
                                    ->label('Unit Kerja'),
                                TextEntry::make('status_jabatan')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'AKTIF' => 'success',
                                        'NONAKTIF' => 'gray',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(3),
                    ])->columnSpanFull(),

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
