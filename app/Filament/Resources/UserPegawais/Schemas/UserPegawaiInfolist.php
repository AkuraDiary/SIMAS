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
                TextEntry::make('user.id')
                    ->label('User'),
                TextEntry::make('nip')
                    ->placeholder('-'),
                TextEntry::make('nama_lengkap'),

                Section::make('Riwayat Jabatan')
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
                    ]),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn(UserPegawai $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),

            ]);
    }
}
