<?php

namespace App\Filament\Resources\UserMahasiswas\Pages;

use App\Filament\Imports\UserMahasiswaImporter;
use App\Filament\Resources\UserMahasiswas\UserMahasiswaResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListUserMahasiswas extends ListRecords
{
    protected static string $resource = UserMahasiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(UserMahasiswaImporter::class)
                ->label('Import Data Mahasiswa'),
            // ExportAction::make()
            //     ->exporter(UserMahasiswaExporter::class)
            //     ->label('Export'),
            CreateAction::make()
                ->label('Tambah Mahasiswa')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
