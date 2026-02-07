<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk;

use App\Models\SuratUnit;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DetailSurat extends Page
{
    protected string $view = 'filament.pages.staf-unit.surat-masuk.detail-surat';
    protected static ?string $slug = 'surat-masuk/{surat_unit_id}';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $panel = 'simas';


    public SuratUnit $suratUnit;

    public function mount($surat_unit_id): void
    {
        $this->suratUnit = SuratUnit::with([
            'surat.unitPengirim',
            'surat.disposisis',
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
    }
}
