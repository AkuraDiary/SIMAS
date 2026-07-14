<?php

namespace App\Filament\Resources\UserPegawais\Pages;

use App\Filament\Resources\UserPegawais\UserPegawaiResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListUserPegawais extends ListRecords
{
    protected static string $resource = UserPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ImportAction::make()
            //     ->importer(UserMahasiswaImporter::class)
            //     ->label('Import Data Mahasiswa'),
            // ExportAction::make()
            //     ->exporter(UserMahasiswaExporter::class)
            //     ->label('Export'),
            CreateAction::make()
                ->label('Tambah Pegawai')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
