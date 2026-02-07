<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk;

use App\Models\SuratUnit;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\StafUnit\SuratMasuk\SuratMasuk;
use App\Models\Surat;

class DetailSurat extends Page
{
    protected string $view = 'filament.pages.staf-unit.surat-masuk.detail-surat';
    protected static ?string $slug = 'surat-masuk/{surat}';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            SuratMasuk::getUrl() => 'Surat Masuk',
            '#' => $this->surat->nomor_surat,
            'Detail',
        ];
    }
    public Surat $surat;
    public ?SuratUnit $suratUnit = null;
    public ?string $jenisTujuanLabel = null;

    public function mount(Surat $surat): void
    {
        $userUnitId = Auth::user()->unit_kerja_id;

        $this->surat = $surat->load([
            'unitPengirim',
            'lampirans',
            'suratUnits' => fn($q) => $q->where('unit_kerja_id', $userUnitId),
            'disposisis' => fn($q) => $q->where('unit_tujuan_id', $userUnitId),
            'disposisis.pembuat.unitKerja',
        ]);

        // Ambil SuratUnit jika ada (langsung)
        $this->suratUnit = $this->surat->suratUnits->first();

        // Security: harus salah satu
        abort_if(
            ! $this->suratUnit && $this->surat->disposisis->isEmpty(),
            403
        );

        // Mark read ONLY if lewat surat_unit
        if ($this->suratUnit && $this->suratUnit->status_baca === 'BELUM') {
            $this->suratUnit->update(['status_baca' => 'SUDAH']);
        }

        $this->jenisTujuanLabel = $this->resolveJenisTujuanLabel();
    }


    protected function resolveJenisTujuanLabel(): string
    {
        $userUnitId = Auth::user()->unit_kerja_id;

        $disposisi = $this->surat
            ->disposisis
            ->firstWhere('unit_tujuan_id', $userUnitId);

        if ($disposisi) {
            $unitAsal = $disposisi->pembuat?->unitKerja?->nama_unit;
            return $unitAsal
                ? 'Disposisi dari ' . $unitAsal
                : 'Disposisi';
        }

        return match ($this->suratUnit?->jenis_tujuan) {
            'utama' => 'Tujuan Utama',
            'tembusan' => 'Tembusan',
            default => '-',
        };
    }
}
