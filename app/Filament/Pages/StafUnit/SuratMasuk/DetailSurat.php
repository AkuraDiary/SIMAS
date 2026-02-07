<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk;

use App\Models\SuratUnit;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\StafUnit\SuratMasuk\SuratMasuk;

class DetailSurat extends Page
{
    protected string $view = 'filament.pages.staf-unit.surat-masuk.detail-surat';
    protected static ?string $slug = 'surat-masuk/{surat_unit_id}';
    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            SuratMasuk::getUrl() => 'Surat Masuk',
            '#' => $this->suratUnit->surat->nomor_surat,
            'Detail',
        ];
    }
    public SuratUnit $suratUnit;
    public string $jenisTujuanLabel;

    public function mount($surat_unit_id): void
    {
        $this->suratUnit = SuratUnit::with([
            'surat.unitPengirim',
            'surat.disposisis',
            'surat.disposisis.pembuat.unitKerja',
            'surat.lampirans'
        ])->findOrFail($surat_unit_id);

        // SECURITY
        abort_if(
            $this->suratUnit->unit_kerja_id !== Auth::user()->unit_kerja_id,
            403
        );

        // MARK AS READ
        if ($this->suratUnit->status_baca !== 'dibaca') {
            $this->suratUnit->update([
                'status_baca' => 'SUDAH',
            ]);
        }

        $this->jenisTujuanLabel = $this->resolveJenisTujuanLabel();
    }

    

    protected function resolveJenisTujuanLabel(): string
    {
        $userUnitId = Auth::user()->unit_kerja_id;

        // Cari disposisi yang TUJUANNYA unit user ini
        $disposisi = $this->suratUnit
            ->surat
            ->disposisis
            ->firstWhere('unit_tujuan_id', $userUnitId);

        if ($disposisi) {
            $unitAsal = $disposisi->pembuat?->unitKerja?->nama_unit;

            return $unitAsal
                ? 'Disposisi dari ' . $unitAsal
                : 'Disposisi';
        }

        // Kalau tidak ada disposisi, berarti penerima langsung
        return match ($this->suratUnit->jenis_tujuan) {
            'utama' => 'Tujuan Utama',
            'tembusan' => 'Tembusan',
            default => ucfirst($this->suratUnit->jenis_tujuan),
        };
    }
}
