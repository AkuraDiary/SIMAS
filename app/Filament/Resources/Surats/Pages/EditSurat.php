<?php

namespace App\Filament\Resources\Surats\Pages;

use App\Filament\Pages\StafUnit\SuratMasuk\DetailSurat;
use App\Filament\Resources\Surats\Pages\Concerns\HasSuratFormActions;
use App\Filament\Resources\Surats\SuratResource;
use App\Filament\Resources\Surats\Actions\AiDraftAction;
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
        // Simpan custom_nomor_tags ke content jika ada
        if (!empty($data['custom_nomor_tags']) && is_array($data['custom_nomor_tags'])) {
            $content = $data['content'] ?? [];
            $content['nomor_surat_tags'] = array_merge($content['nomor_surat_tags'] ?? [], $data['custom_nomor_tags']);
            $data['content'] = $content;
        }

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

        // Penanganan jika nomor_surat ditetapkan saat edit
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

    protected function getHeaderActions(): array
    {
        return [
            // AiDraftAction::makeForPage(),

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
