<?php

namespace App\Filament\Imports;

use App\Models\Jabatan;
use App\Models\UnitKerja;
use App\Models\UserPegawai;
use App\Services\UserProvisioningService;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class UserPegawaiImporter extends Importer
{
    protected static ?string $model = UserPegawai::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nip')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->example('198501012010011001'),
            ImportColumn::make('nama_lengkap')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->example('Ahmad Dahlan'),
            ImportColumn::make('email')
                ->rules(['nullable', 'email'])
                ->example('dahlan.a@simas.bebek.id'),
            ImportColumn::make('phone')
                ->rules(['nullable', 'string'])
                ->example('081234567890'),
            ImportColumn::make('unit_kerja')
                ->label('Unit Kerja (Nama Unit)')
                ->example('Sistem Informasi'),
            ImportColumn::make('jabatan')
                ->label('Jabatan')
                ->example('Kepala Unit'),
        ];
    }
    
    public function resolveRecord(): ?UserPegawai
    {
        return UserPegawai::firstOrNew(['nip' => $this->data['nip']]);
    }
    
    public function saveRecord(): void
    {
        $unitKerjaId = UnitKerja::where('nama_unit', $this->data['unit_kerja'] ?? null)->value('id');
        $jabatanId = Jabatan::where('nama_jabatan', $this->data['jabatan'] ?? null)->value('id');
    
        app(UserProvisioningService::class)->createOrUpdatePegawaiFromImport([
            ...$this->data,
            'unit_kerja_id' => $unitKerjaId,
            'jabatan_id' => $jabatanId,
        ]);
    }

    // public function resolveRecord(): UserPegawai
    // {
    //     return UserPegawai::firstOrNew([
    //         'nip' => $this->data['nip'],
    //     ]);
    // }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your user pegawai import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
