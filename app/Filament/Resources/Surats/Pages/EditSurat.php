<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Pages\StafUnit\SuratMasuk\DetailSurat;
use App\Filament\Resources\Surats\Pages\Concerns\HasSuratFormActions;
use App\Filament\Resources\Surats\SuratResource;
use App\Models\Template;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
        // Jika sedang Revisi
        if ($this->record->status_surat === 'REVISI') {
            return [
                SuratResource::getUrl('index', ['scope' => 'keluar']) => 'Surat Keluar',
                DetailSurat::getUrl(['surat' => $this->record, 'record' => $this->record, 'scope' => 'keluar']) => $this->record->nomor_surat ?? 'Revisi',
                'Perbaiki Surat',
            ];
        }
        // Default: Jika sedang Draft
        return [
            SuratResource::getUrl('index', ['scope' => 'draft']) => 'Draft Surat',
            '#' => $this->record->nomor_surat ?? 'Draft',
            'Edit Surat',
        ];
    }


    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return $this->record->status_surat === 'REVISI'
            ? 'Perbaiki Surat (Revisi)'
            : 'Edit Draft Surat';
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
            ActionGroup::make([
                // 1. Tombol Unduh Template Kosong
                // Tombol ini akan muncul jika user sudah memilih template di form
                Action::make('download_blank')
                    ->label('Unduh Template Asli (Kosong)')
                    ->icon('heroicon-o-document')
                    ->visible(fn () => isset($this->data['template_id']))
                    ->action(function () {
                        $template = Template::find($this->data['template_id']);
                        if (!$template) return;
                        $path = app(\App\Services\DocxTemplateService::class)->downloadBlankDocx($template);
                        return response()->download($path, 'Template_Kosong_' . $template->nama_template . '.docx');
                    }),

                // 2. Tombol Unduh Draft Surat (.docx)
                // Tombol ini hanya muncul di EditSurat (atau jika surat sudah di-save sebagai draft)
                // karena kita butuh data Surat yang sudah tersimpan di database
                Action::make('download_filled')
                    ->label('Unduh Draft Surat (.docx)')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn () => $this->record !== null)
                    ->action(function () {
                        $path = app(\App\Services\DocxTemplateService::class)->downloadFilledDocx($this->record);
                        return response()->download($path, 'Draft_Surat_' . $this->record->perihal . '.docx');
                    }),
            ])
            ->label('Unduh Dokumen')
            ->icon('heroicon-o-arrow-down-tray')
            ->button()
            ->color('gray'),

            DeleteAction::make(),

        ];
    }
}
