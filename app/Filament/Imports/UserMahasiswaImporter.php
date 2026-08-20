<?php

namespace App\Filament\Imports;

use App\Models\UnitKerja;
use App\Models\UserMahasiswa;
use App\Services\UserProvisioningService;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class UserMahasiswaImporter extends Importer
{
    protected static ?string $model = UserMahasiswa::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nim')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->example('2310801001'),
            ImportColumn::make('nama_lengkap')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->example('Ahmad Dahlan'),
            ImportColumn::make('email')
                ->rules(['nullable', 'email'])
                ->example('dahlan.a@simas.ac.id'),
            ImportColumn::make('phone')
                ->rules(['nullable', 'string'])
                ->example('081234567890'),
            ImportColumn::make('tanggal_lahir')
                ->rules(['nullable', 'date'])
                ->example('2005-03-14'),
            ImportColumn::make('tahun_masuk')
                ->rules(['nullable', 'integer'])
                ->example('2023'),
            ImportColumn::make('status')
                ->rules(['nullable', 'in:AKTIF,CUTI,LULUS,KELUAR'])
                ->example('AKTIF'),
            ImportColumn::make('prodi')
                ->label('Prodi (Nama Unit)')
                ->example('Sistem Informasi'),
            ImportColumn::make('fakultas')
                ->label('Fakultas (Nama Unit)')
                ->example('Fakultas Teknik'),
        ];
    }

    public function resolveRecord(): ?UserMahasiswa
    {
        // Not actually used for persistence (see saveRecord below) — Filament still
        // calls this for its own duplicate-tracking/reporting, so keep it accurate.
        return UserMahasiswa::firstOrNew(['nim' => $this->data['nim']]);
    }

    public function saveRecord(): void
    {
        // activate this line, if only need to import new datas (skip existing nim) ~ Seta
        // if (UserMahasiswa::where('nim', $this->data['nim'])->exists()) {
        //     return; // skip — NIM already exists
        // }
        $prodiId = UnitKerja::where('nama_unit', $this->data['prodi'] ?? null)->value('id');
        $fakultasId = UnitKerja::where('nama_unit', $this->data['fakultas'] ?? null)->value('id');

        app(UserProvisioningService::class)->createOrUpdateMahasiswaFromImport([
            ...$this->data,
            'prodi_id' => $prodiId,
            'fakultas_id' => $fakultasId,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your user mahasiswa import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
