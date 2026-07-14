<?php

namespace App\Filament\Resources\UserMahasiswas\Schemas;

use App\Models\UserMahasiswa;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserMahasiswaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nama_lengkap'),
                TextEntry::make('nim'),
                TextEntry::make('user.username')
                    ->label('Username'),
                TextEntry::make('user.email')
                    ->label('Email')
                    ->placeholder('-'),
                TextEntry::make('user.is_active')
                    ->label('Status Akun')
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Aktif' : 'Belum Aktivasi')
                    ->color(fn(bool $state): string => $state ? 'success' : 'gray'),
                TextEntry::make('tanggal_lahir')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('tahun_masuk')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('prodi.nama_unit')
                    ->label('Prodi')
                    ->placeholder('-'),
                TextEntry::make('fakultas.nama_unit')
                    ->label('Fakultas')
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn(UserMahasiswa $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                
                
            ]);
    }
}
