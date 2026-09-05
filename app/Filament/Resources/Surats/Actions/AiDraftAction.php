<?php

namespace App\Filament\Resources\Surats\Actions;

use App\Services\OllamaService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

class AiDraftAction
{
    /**
     * Check if the current logged-in user can access the AI drafting assistant.
     * Constraint: Logged in users only, strictly excluding ADMIN.
     */
    public static function isAccessible(): bool
    {
        return auth()->check() && auth()->user()?->tipe_entitas !== 'ADMIN';
    }

    /**
     * Build the AI Draft Action for use in page header or form.
     */
    public static function make(string $name = 'ai_draft_assistant'): Action
    {
        return Action::make($name)
            ->label('Bantuan AI (Draft Surat)')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->visible(fn () => static::isAccessible())
            ->modalHeading('Asisten AI - Pembuat Draft Surat')
            ->modalDescription('Gunakan AI untuk membuat atau menyempurnakan draft isi surat resmi sesuai kaidah Tata Naskah Dinas.')
            ->modalIcon('heroicon-o-sparkles')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Terapkan ke Surat')
            ->modalCancelActionLabel('Tutup')
            ->fillForm(function (Get $get, $livewire): array {
                $currentPerihal = '';
                if ($livewire && isset($livewire->data['perihal'])) {
                    $currentPerihal = $livewire->data['perihal'];
                } elseif ($get) {
                    try {
                        $currentPerihal = (string) ($get('perihal') ?? '');
                    } catch (\Throwable $e) {
                        $currentPerihal = '';
                    }
                }

                return [
                    'ai_kategori' => 'Undangan Rapat / Kegiatan',
                    'ai_tone' => 'Standar Kedinasan (Formal, lugas, santun)',
                    'ai_prompt' => '',
                    'ai_perihal' => $currentPerihal,
                    'ai_isi_surat' => '',
                    'ai_penjelasan' => '',
                    'ai_revisi_prompt' => '',
                ];
            })
            ->form([
                Section::make('Kebutuhan & Poin-Poin Surat')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('ai_kategori')
                                ->label('Kategori Surat')
                                ->options([
                                    'Undangan Rapat / Kegiatan' => 'Undangan Rapat / Pertemuan',
                                    'Permohonan / Izin' => 'Permohonan / Pengajuan / Izin',
                                    'Pemberitahuan / Edaran' => 'Pemberitahuan / Pengumuman / Edaran',
                                    'Surat Tugas / Dinas' => 'Surat Tugas / Perjalanan Dinas',
                                    'Surat Rekomendasi' => 'Surat Rekomendasi',
                                    'Surat Keterangan' => 'Surat Keterangan Resmi',
                                    'Lainnya / Umum' => 'Lainnya / Umum',
                                ])
                                ->default('Undangan Rapat / Kegiatan')
                                ->required()
                                ->live(),

                            Select::make('ai_tone')
                                ->label('Gaya Bahasa / Tone')
                                ->options([
                                    'Standar Kedinasan (Formal, lugas, santun)' => 'Standar Kedinasan (Formal & Santun)',
                                    'Sangat Formal / Birokratis' => 'Sangat Formal / Birokratis Ketat',
                                    'Santun & Persuasif' => 'Santun & Persuasif',
                                ])
                                ->default('Standar Kedinasan (Formal, lugas, santun)')
                                ->required(),
                        ]),

                        Textarea::make('ai_prompt')
                            ->label('Instruksi / Catatan Kunci Surat')
                            ->placeholder("Tuliskan poin-poin yang ingin dituangkan dalam surat.\nContoh: Undangan rapat koordinasi kurikulum MBKM pada hari Kamis, 15 Oktober 2026 pukul 09.00 WIB di Ruang Rapat Dekanat. Agenda evaluasi semester ganjil dan penyusunan silabus baru.")
                            ->rows(3)
                            ->helperText('Sebutkan rincian seperti hari/tanggal, waktu, tempat, agenda, atau latar belakang permohonan.')
                            ->live(),

                        Actions::make([
                            Action::make('generate_ai_btn')
                                ->label('Buat Draft dengan AI')
                                ->icon('heroicon-m-sparkles')
                                ->color('primary')
                                ->button()
                                ->action(function (Set $set, Get $get, $livewire) {
                                    $prompt = trim((string) ($get('ai_prompt') ?? ''));
                                    if (empty($prompt)) {
                                        Notification::make()
                                            ->title('Instruksi belum diisi')
                                            ->body('Harap masukkan instruksi atau poin-poin surat terlebih dahulu.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }

                                    $kategori = $get('ai_kategori') ?? 'Undangan Rapat / Kegiatan';
                                    $tone = $get('ai_tone') ?? 'Standar Kedinasan';
                                    $perihalSaatIni = $get('ai_perihal') ?: ($livewire->data['perihal'] ?? '');

                                    $senderName = '';
                                    if (auth()->check()) {
                                        $jabatan = auth()->user()->getActiveJabatan();
                                        $senderName = $jabatan?->unitKerja?->nama_unit ?? auth()->user()->unitKerja?->nama_unit ?? '';
                                    }

                                    try {
                                        $service = app(OllamaService::class);
                                        $result = $service->generateDraft($prompt, [
                                            'kategori' => $kategori,
                                            'tone' => $tone,
                                            'perihal_saat_ini' => $perihalSaatIni,
                                            'unit_pengirim' => $senderName,
                                        ]);

                                        $set('ai_perihal', $result['perihal']);
                                        $set('ai_isi_surat', $result['isi_surat']);
                                        $set('ai_penjelasan', $result['penjelasan']);

                                        Notification::make()
                                            ->title('Draft AI Berhasil Dibuat')
                                            ->body('Draft surat telah dihasilkan. Silakan tinjau dan sesuaikan di bawah.')
                                            ->success()
                                            ->send();
                                    } catch (\Throwable $e) {
                                        Notification::make()
                                            ->title('Layanan AI Mengalami Kendala')
                                            ->body($e->getMessage() . "\n\nAnda juga dapat menggunakan tombol 'Gunakan Standar Kedinasan Cepat' jika layanan AI sedang offline.")
                                            ->danger()
                                            ->persistent()
                                            ->send();
                                    }
                                }),

                            Action::make('generate_standard_btn')
                                ->label('Gunakan Standar Kedinasan (Cepat / Offline)')
                                ->icon('heroicon-m-document-text')
                                ->color('gray')
                                ->outlined()
                                ->action(function (Set $set, Get $get) {
                                    $prompt = trim((string) ($get('ai_prompt') ?? 'Kegiatan Kedinasan Unit'));
                                    $kategori = $get('ai_kategori') ?? 'Undangan Rapat / Kegiatan';

                                    $service = app(OllamaService::class);
                                    $result = $service->generateFallbackDraft($prompt, [
                                        'kategori' => $kategori,
                                        'tone' => $get('ai_tone'),
                                    ]);

                                    $set('ai_perihal', $result['perihal']);
                                    $set('ai_isi_surat', $result['isi_surat']);
                                    $set('ai_penjelasan', $result['penjelasan']);

                                    Notification::make()
                                        ->title('Format Standar Kedinasan Dibuat')
                                        ->body('Draft dengan format standar siap ditinjau.')
                                        ->info()
                                        ->send();
                                }),
                        ]),
                    ]),

                Section::make('Pratinjau & Penyesuaian Draft')
                    ->schema([
                        TextInput::make('ai_perihal')
                            ->label('Rekomendasi Perihal Surat')
                            ->placeholder('Contoh: Undangan Rapat Koordinasi Kurikulum MBKM')
                            ->live(),

                        Textarea::make('ai_isi_surat')
                            ->label('Draft Isi Surat (HTML / Format Editor)')
                            ->rows(8)
                            ->helperText('Anda dapat mengedit langsung teks/HTML ini di sini sebelum diterapkan ke formulir surat.')
                            ->live(),

                        Placeholder::make('ai_preview_box')
                            ->label('Pratinjau Tampilan Dokumen')
                            ->content(function (Get $get): HtmlString {
                                $html = $get('ai_isi_surat');
                                if (empty($html)) {
                                    return new HtmlString('<div class="p-3 text-sm text-gray-400 italic">Belum ada isi surat.</div>');
                                }
                                return new HtmlString(
                                    '<div class="p-4 bg-white dark:bg-gray-900 border rounded-lg shadow-sm text-sm text-gray-800 dark:text-gray-200 leading-relaxed font-serif prose dark:prose-invert max-w-none">' .
                                    $html .
                                    '</div>'
                                );
                            }),
                    ])
                    ->visible(fn (Get $get) => !empty($get('ai_isi_surat'))),

                Section::make('Revisi Percakapan Lanjut (Chat Refinement)')
                    ->schema([
                        TextInput::make('ai_revisi_prompt')
                            ->label('Instruksi Perbaikan / Tambahan')
                            ->placeholder('Contoh: Tolong tambahkan poin agar peserta membawa laptop dan berpakaian batik.')
                            ->live(),

                        Actions::make([
                            Action::make('refine_ai_btn')
                                ->label('Perbaiki Draft dengan AI')
                                ->icon('heroicon-m-arrow-path')
                                ->color('primary')
                                ->button()
                                ->action(function (Set $set, Get $get) {
                                    $revisi = trim((string) ($get('ai_revisi_prompt') ?? ''));
                                    $currentDraft = (string) ($get('ai_isi_surat') ?? '');

                                    if (empty($revisi)) {
                                        Notification::make()
                                            ->title('Instruksi revisi masih kosong')
                                            ->warning()
                                            ->send();
                                        return;
                                    }

                                    try {
                                        $service = app(OllamaService::class);
                                        $result = $service->refineDraft($currentDraft, $revisi, [
                                            'kategori' => $get('ai_kategori'),
                                            'perihal_saat_ini' => $get('ai_perihal'),
                                        ]);

                                        $set('ai_perihal', $result['perihal']);
                                        $set('ai_isi_surat', $result['isi_surat']);
                                        $set('ai_penjelasan', $result['penjelasan']);
                                        $set('ai_revisi_prompt', '');

                                        Notification::make()
                                            ->title('Draft Berhasil Diperbarui')
                                            ->body('Revisi berhasil diproses.')
                                            ->success()
                                            ->send();
                                    } catch (\Throwable $e) {
                                        Notification::make()
                                            ->title('Gagal Memproses Revisi')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->persistent()
                                            ->send();
                                    }
                                }),
                        ]),
                    ])
                    ->visible(fn (Get $get) => !empty($get('ai_isi_surat'))),
            ])
            ->action(function (array $data, Set $set, Get $get, $livewire) {
                $generatedContent = $data['ai_isi_surat'] ?? null;
                $generatedPerihal = $data['ai_perihal'] ?? null;

                if (empty($generatedContent)) {
                    Notification::make()
                        ->title('Tidak Ada Draft untuk Diterapkan')
                        ->body('Silakan buat draft terlebih dahulu dengan AI.')
                        ->warning()
                        ->send();
                    return;
                }

                // 1. Update via Livewire component data if available (e.g. CreateSurat or EditSurat)
                if ($livewire instanceof CreateRecord || $livewire instanceof EditRecord || isset($livewire->data)) {
                    $formData = $livewire->data ?? [];
                    $formData['metode_pembuatan'] = 'scratch';

                    $content = $formData['content'] ?? [];
                    if (!is_array($content)) {
                        $content = [];
                    }
                    $content['isi_surat'] = $generatedContent;
                    $formData['content'] = $content;

                    if (empty($formData['perihal']) && !empty($generatedPerihal)) {
                        $formData['perihal'] = $generatedPerihal;
                    }

                    $livewire->data = $formData;

                    if (method_exists($livewire, 'form') && $livewire->form) {
                        try {
                            $livewire->form->fill($formData);
                        } catch (\Throwable $e) {
                            // Silently continue
                        }
                    }
                }

                // 2. Also update via form Set utility
                try {
                    $set('metode_pembuatan', 'scratch');
                    $set('content.isi_surat', $generatedContent);
                    if (empty($get('perihal')) && !empty($generatedPerihal)) {
                        $set('perihal', $generatedPerihal);
                    }
                } catch (\Throwable $e) {
                    // Silently continue
                }

                Notification::make()
                    ->title('Draft AI Berhasil Diterapkan')
                    ->body('Isi surat dan perihal telah diperbarui. Mode otomatis diatur ke "Tulis dari Awal".')
                    ->success()
                    ->send();
            });
    }

    /**
     * Helper to make an action specifically for the form schema (e.g. next to TinyEditor).
     */
    public static function makeForFormComponent(string $name = 'ai_form_assistant'): Action
    {
        return static::make($name)
            ->label('Asisten AI (Draft Surat)')
            ->icon('heroicon-m-sparkles')
            ->color('primary')
            ->button();
    }

    /**
     * Helper to make an action specifically for page header actions.
     */
    public static function makeForPage(string $name = 'ai_header_assistant'): Action
    {
        return static::make($name)
            ->label('Asisten AI (Draft)')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->button();
    }
}
