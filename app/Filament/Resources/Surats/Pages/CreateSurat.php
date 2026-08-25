<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Resources\Surats\Pages\Concerns\HasSuratFormActions;
use App\Filament\Resources\Surats\SuratResource;
use App\Models\Template;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
        ];
    }

    public function mount(): void
    {
        parent::mount();

        if (Request::has('tipe_surat') || Request::has('terbitan_for_surat_id')) {
            $this->form->fill([
                'tipe_surat' => Request::query('tipe_surat', 'INTERNAL'),
                'terbitan_for_surat_id' => Request::query('terbitan_for_surat_id'),
                'status_surat' => 'DRAFT',
            ]);
        }
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
