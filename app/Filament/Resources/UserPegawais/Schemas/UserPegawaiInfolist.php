<?php

namespace App\Filament\Resources\UserPegawais\Schemas;

use App\Models\UserPegawai;
use Filament\Infolists\Components\TextEntry;
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
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (UserPegawai $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
