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
                TextEntry::make('user.id')
                    ->label('User'),
                TextEntry::make('nim'),
                TextEntry::make('nama_lengkap'),
                TextEntry::make('tanggal_lahir')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('tahun_masuk')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('prodi.id')
                    ->label('Prodi')
                    ->placeholder('-'),
                TextEntry::make('fakultas.id')
                    ->label('Fakultas')
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (UserMahasiswa $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
