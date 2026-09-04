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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Simpan custom_nomor_tags ke content jika ada
        if (!empty($data['custom_nomor_tags']) && is_array($data['custom_nomor_tags'])) {
            $content = $data['content'] ?? [];
            $content['nomor_surat_tags'] = array_merge($content['nomor_surat_tags'] ?? [], $data['custom_nomor_tags']);
            $data['content'] = $content;
        }

        // Jika pakai template dan Path Builder manual kosong, copy dari Template!
        if (($data['metode_pembuatan'] ?? 'template') === 'template' && !empty($data['template_id'])) {
            if (empty($data['approval_path'])) {
                $template = \App\Models\Template::find($data['template_id']);
                if ($template && !empty($template->approval_path)) {
                    $data['approval_path'] = $template->approval_path;
                }
            }
        }

        return $data;
    }

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

        // Penanganan jika nomor_surat ditetapkan saat pembuatan
        if (!empty($surat->nomor_surat) && $surat->nomorSuratLogs()->doesntExist()) {
            $formatId = $this->data['format_id_input'] ?? null;
            $format = $formatId ? \App\Models\FormatNomorSurat::find($formatId) : null;
            if (!$format) {
                $format = app(\App\Services\NomorSuratService::class)->resolveFormat(
                    $surat->unit_pengirim_id,
                    $surat->tipe_surat
                );
            }

            if ($format) {
                $isManual = (bool) ($this->data['is_manual_sisipan'] ?? false);
                $incrementCounter = $isManual ? (bool) ($this->data['increment_counter_input'] ?? false) : true;
                $tglSurat = !empty($this->data['tanggal_surat_input']) ? \Carbon\Carbon::parse($this->data['tanggal_surat_input']) : now();
                $customTags = array_merge(
                    $surat->content['nomor_surat_tags'] ?? [],
                    $this->data['custom_nomor_tags'] ?? []
                );

                app(\App\Services\NomorSuratService::class)->assignNomorSurat($surat, $format, [
                    'tanggal_surat' => $tglSurat,
                    'nomor_surat_preview' => $surat->nomor_surat,
                    'is_manual' => $isManual,
                    'increment_counter' => $incrementCounter,
                    'alasan_backdate' => $this->data['alasan_backdate_input'] ?? null,
                    'custom_tags' => $customTags,
                    'user_id' => auth()->id(),
                ]);
            }
        }
    }
}
