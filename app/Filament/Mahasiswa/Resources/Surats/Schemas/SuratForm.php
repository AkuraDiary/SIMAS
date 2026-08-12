<?php

namespace App\Filament\Mahasiswa\Resources\Surats\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SuratForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('perihal')
                    ->label('Perihal / Tujuan Permohonan')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('content')
                    ->label('Keterangan / Isi Ringkas')
                    ->rows(4)
                    ->columnSpanFull(),
                \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('lampiran')
                    ->label('Dokumen Pendukung')
                    ->collection('lampiran')
                    ->multiple()
                    ->reorderable()
                    ->maxFiles(5)
                    ->columnSpanFull()
                    ->helperText('Unggah dokumen pendukung untuk permohonan ini (misalnya form pengajuan, transkrip, dll).'),
            ]);
    }
}
