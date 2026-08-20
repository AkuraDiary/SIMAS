<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\Pages\Concerns\HasSuratFormActions;
use App\Filament\Resources\Surats\SuratResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditSurat extends EditRecord
{
    use HasSuratFormActions;

    protected static string $resource = SuratResource::class;
    protected Width|string|null $maxContentWidth = 'full';

    public function getBreadcrumbs(): array
    {
        return [
            SuratResource::getUrl('index', ['scope' => 'draft']) => 'Draft Surat',
            '#' => $this->record->nomor_surat ?? 'Draft',
            'Edit Surat',
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Jika form menyembunyikan template_id (karena mode 'scratch'),
        // pastikan nilainya di-set ke null agar menimpa ID lama di database.
        if (!array_key_exists('template_id', $data)) {
            $data['template_id'] = null;
        }
        return $data;
    }

    protected function afterSave(): void
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
