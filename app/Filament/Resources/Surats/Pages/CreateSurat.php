<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\Pages\Concerns\HasSuratFormActions;
use App\Filament\Resources\Surats\SuratResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Request;

class CreateSurat extends CreateRecord
{
    use HasSuratFormActions;

    protected static string $resource = SuratResource::class;
    
    protected static ?string $title = 'Buat Surat';
    protected Width|string|null $maxContentWidth = 'full';

    public function getBreadcrumbs(): array
    {
        return [
            SuratResource::getUrl('index', ['scope' => Request::query('draft')]) => 'Draft Surat',
            '#' => 'Buat Surat Baru',
        ];
    }

    protected function afterCreate(): void
    {
        $surat = $this->record;
        $unitIds = $this->data['unitTujuan'] ?? [];

        foreach ($unitIds as $index => $unitId) {
            $surat->unitTujuan()->updateExistingPivot($unitId, [
                'jenis_tujuan' => $index === 0 ? 'UTAMA' : 'TEMBUSAN',
                'status_baca' => 'BELUM',
            ]);
        }
    }
}
