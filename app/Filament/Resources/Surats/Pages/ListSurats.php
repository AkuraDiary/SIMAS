<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\SuratResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;


class ListSurats extends ListRecords
{
    protected static string $resource = SuratResource::class;

    public string $scope = 'keluar'; // or whatever default

    protected $queryString = [
        'scope' => ['except' => ''],
    ];

    public function getBreadcrumbs(): array
    {
        return [
            SuratResource::getUrl('index', ['scope' => $this->scope]) => $this->getTitle(),
            '#' => 'List Surat',
        ];
    }
    public function getTitle(): string
    {
        return match ($this->scope) {
            'keluar' => 'Surat Keluar',
            'draft'  => 'Draft Surat',
            'arsip'  => 'Arsip Surat',
            default  => 'Semua Surat',
        };
    }



    public function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label("Buat Surat Baru")->visible(fn() => $this->scope !== 'arsip'),
        ];
    }


    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder | \Illuminate\Database\Eloquent\Relations\Relation | null
    {

        $query = static::getResource()::getEloquentQuery();
        $unitId = \Illuminate\Support\Facades\Auth::user()?->unit_kerja_id;

        return match ($this->scope) {
            'persetujuan' => $query
                ->whereIn('status_surat', ['DIPROSES', 'TERKIRIM'])
                ->whereHas('riwayats', function ($q) use ($unitId) {
                    $q->where('status', 'MENUNGGU')
                        ->where('unit_tujuan_id', $unitId);
                }),

            'draft' => $query
                ->where('unit_pengirim_id', $unitId)
                ->where('status_surat', 'DRAFT'),

            'keluar' => $query
                ->where(function ($q) use ($unitId) {
                    $q->where('unit_pengirim_id', $unitId)

                    // manipulate this to deactivate the disposition letter from appearing in surat keluar - Seta
                        ->orWhereHas('disposisis', function ($dq) use ($unitId) {
                            $dq->whereHas('userPegawaiJabatan', function ($qJabatan) use ($unitId) {
                                $qJabatan->where('unit_kerja_id', $unitId);
                            });
                        });
                })
                ->where('status_surat', '!=', 'DRAFT')
                ->whereDoesntHave('arsipSurats', function ($q) use ($unitId) {
                    $q->where('unit_kerja_id', $unitId);
                }),

            'arsip' => $query
                ->whereHas('arsipSurats', fn($q) => $q->where('unit_kerja_id', $unitId))
                ->with(['arsipSurats.kategoriArsip']),

            'pengajuan' => $query
                ->where('tipe_surat', 'PENGAJUAN')
                ->where('status_surat', '!=', 'DRAFT')
                ->whereNull('terbitan_for_surat_id'),

            default => $query
                ->where('unit_pengirim_id', $unitId),
        };
    }
}
