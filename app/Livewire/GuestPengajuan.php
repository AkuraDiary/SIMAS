<?php

namespace App\Livewire;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Vinkla\Hashids\Facades\Hashids;


#[Layout('components.layouts.app', ['showHeader' => true])]
class GuestPengajuan extends Component implements HasForms
{
    use InteractsWithForms;

    // We will bind all form data to this array
    public ?array $data = [];

    public bool $submitted = false;
    public ?string $trackingCode = null;

    public ?string $revisiCode = null;
    public ?\App\Models\Surat $revisiSurat = null;
    public ?string $catatanRevisiTerakhir = null;

    public function mount(): void
    {

        $revisiParam = request()->query('revisi');
        if ($revisiParam) {
            $surat = \App\Models\Surat::with(['unitTujuan', 'riwayats'])
                ->where('tracking_code', trim($revisiParam))
                ->where('status_surat', 'REVISI')
                ->first();
            if ($surat) {
                $this->revisiCode = $surat->tracking_code;
                $this->revisiSurat = $surat;
                $this->catatanRevisiTerakhir = $surat->riwayats->where('status', 'REVISI')->last()?->catatan;
                // Pre-fill form wizard dengan seluruh data lama (baik Scratch maupun Variabel Template)
                $metadata = $surat->pengirim_metadata ?? [];
                $this->form->fill([
                    'template_id'           => $surat->template_id ?: 'scratch',
                    'tipe_pengirim'         => $metadata['tipe_pengirim'] ?? ($surat->pengirim_nim ? 'mahasiswa' : 'guest'),
                    'pengirim_nama'         => $surat->pengirim_nama,
                    'pengirim_email'        => $surat->pengirim_email,
                    'pengirim_telp'         => $metadata['telp'] ?? null,
                    'pengirim_nim'          => $surat->pengirim_nim,
                    'pengirim_fakultas'     => $metadata['fakultas_id'] ?? null,
                    'pengirim_prodi'        => $metadata['prodi_id'] ?? null,
                    'pengirim_instansi'     => $metadata['instansi'] ?? null,
                    'nomor_surat_eksternal' => $surat->nomor_surat_eksternal,
                    'unit_tujuan'           => $surat->unitTujuan->first()?->id,
                    'perihal'               => $surat->perihal,
                    'content_scratch'       => $surat->content['isi_surat'] ?? null,
                    'content'               => $surat->content ?? [], // 🟢 Mengisi otomatis seluruh variabel template!
                ]);
                return;
            }
        }
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([

                    // STEP 1: TEMPLATE
                    Step::make('Template')
                        ->description('Pilih jenis surat')
                        ->schema([
                            ViewField::make('template_id')
                                ->view('components.template-selector') // We will create this file next
                                ->columnSpanFull()
                                ->required(),
                        ]),
                    // STEP 2: DATA PENGIRIM
                    Step::make('Data Pengirim')
                        ->description('Informasi pemohon')
                        ->schema([
                            ViewField::make('tipe_pengirim')
                                ->view('components.tipe-pengirim-selector')
                                ->default('guest')
                                ->columnSpanFull(),

                            TextInput::make('pengirim_nama')
                                ->label('Nama Lengkap')
                                ->placeholder('Masukkan nama lengkap sesuai identitas')
                                ->required()
                                ->columnSpan(fn(Get $get) => $get('tipe_pengirim') === 'guest' ? 'full' : 1),

                            TextInput::make('pengirim_nim')
                                ->label('NIM')
                                ->placeholder('Masukkan NIM anda')
                                ->visible(fn(Get $get) => $get('tipe_pengirim') === 'mahasiswa')
                                ->required(fn(Get $get) => $get('tipe_pengirim') === 'mahasiswa'),

                            TextInput::make('pengirim_email')
                                ->label('Alamat Email')
                                ->placeholder('contoh@email.com')
                                ->email()
                                ->required(),

                            TextInput::make('pengirim_telp')
                                ->label('Nomor Telepon / WhatsApp')
                                ->placeholder('08xxxxxxxxxx')
                                ->required(),

                            TextInput::make('pengirim_instansi')
                                ->label('Instansi / Asal Identitas')
                                ->placeholder('Nama Universitas, Perusahaan, atau Instansi Asal')
                                ->visible(fn(Get $get) => $get('tipe_pengirim') === 'guest')
                                ->required(fn(Get $get) => $get('tipe_pengirim') === 'guest')
                                ->columnSpanFull(),

                            Select::make('pengirim_fakultas')
                                ->label('Fakultas')
                                ->placeholder('Pilih Fakultas')
                                ->options(function () {
                                    return \App\Models\UnitKerja::whereHas('jenisUnit', function ($query) {
                                        $query->where('nama_jenis', 'like', '%Fakultas%');
                                    })->pluck('nama_unit', 'id');
                                })
                                ->live()
                                ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Set $set) => $set('pengirim_prodi', null))
                                ->visible(fn(Get $get) => $get('tipe_pengirim') === 'mahasiswa')
                                ->required(fn(Get $get) => $get('tipe_pengirim') === 'mahasiswa'),

                            Select::make('pengirim_prodi')
                                ->label('Prodi')
                                ->placeholder(fn(Get $get) => $get('pengirim_fakultas') ? 'Pilih Prodi' : 'Pilih Fakultas terlebih dahulu')
                                ->options(function (Get $get) {
                                    $fakultasId = $get('pengirim_fakultas');

                                    if (! $fakultasId) {
                                        return [];
                                    }

                                    return \App\Models\UnitKerja::where('parent_id', $fakultasId)
                                        ->whereHas('jenisUnit', function ($query) {
                                            $query->where('nama_jenis', 'like', '%Prodi%')
                                                ->orWhere('nama_jenis', 'like', '%Program Studi%');
                                        })
                                        ->pluck('nama_unit', 'id');
                                })
                                ->disabled(fn(Get $get) => ! $get('pengirim_fakultas'))
                                ->visible(fn(Get $get) => $get('tipe_pengirim') === 'mahasiswa')
                                ->required(fn(Get $get) => $get('tipe_pengirim') === 'mahasiswa'),
                        ])->columns(2),
                    // STEP 3: ISI SURAT
                    Step::make('Isi Surat')
                        ->description('Lengkapi detail surat')
                        ->schema([
                            // SCRATCH MODE
                            Group::make()->schema([
                                TextEntry::make('detail_surat_title')
                                    ->hiddenLabel()
                                    ->state(new \Illuminate\Support\HtmlString('<h2 class="text-xl font-bold text-gray-900 mb-2">Detail Surat</h2>')),

                                Select::make('unit_tujuan')
                                    ->label('Unit Tujuan')
                                    ->options(function () {
                                        return \App\Models\UnitKerja::pluck('nama_unit', 'id');
                                    })
                                    ->required(fn(Get $get) => $get('template_id') === 'scratch'),

                                TextInput::make('perihal')
                                    ->label('Subjek / Judul')
                                    ->required(fn(Get $get) => $get('template_id') === 'scratch'),

                                TextInput::make('nomor_surat_eksternal')
                                    ->label('Nomor Surat Asal / Referensi (Opsional)')
                                    ->placeholder('Contoh: 045/DIR-PLN/IX/2026')
                                    ->helperText('Isi jika dokumen yang dikirimkan memiliki nomor surat resmi dari instansi Anda (misal: surat undangan, proposal, kerjasama).')
                                    ->visible(fn(Get $get) => $get('tipe_pengirim') === 'guest' && $get('template_id') === 'scratch')
                                    ->columnSpanFull(),

                                TinyEditor::make('content_scratch')
                                    ->label('Isi Surat')
                                    ->setCustomConfigs([
                                        'font_family_formats' => 'Arial=arial,helvetica,sans-serif; Times New Roman=times new roman,times; Verdana=verdana,geneva',
                                    ])
                                    ->profile('full')
                                    ->placeholder('Tuliskan isi surat secara bebas di sini...')
                                    ->visible(fn(Get $get) => $get('template_id') === 'scratch')
                                    ->required(fn(Get $get) => $get('template_id') === 'scratch')
                                    ->columnSpanFull(),
                            ])
                                ->visible(fn(Get $get) => $get('template_id') === 'scratch'),

                            // TEMPLATE MODE
                            Group::make()->schema(function (Get $get) {

                                $templateId = $get('template_id');
                                if (! $templateId || $templateId === 'scratch') return [];

                                $template = \App\Models\Template::find($templateId);
                                if (! $template) return [];

                                $components = [];

                                // Header info
                                $components[] = TextEntry::make('template_info')
                                    ->hiddenLabel()
                                    ->state(new \Illuminate\Support\HtmlString('
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-900 mb-4">Detail Surat</h2>
                                            <div class="flex items-center gap-2 mb-4">
                                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                <span class="text-sm text-gray-600">Template Terpilih:</span>
                                                <span class="text-sm font-bold text-primary-600">' . e($template->nama_template) . '</span>
                                            </div>
                                            <hr class="border-gray-200">
                                            <div class="flex items-center gap-2 mt-4">
                                                <span class="text-sm font-bold text-primary-600">' . e($template->deskripsi) . '</span>
                                            </div>
                                        </div>
                                    '))->columnSpanFull();

                                // Variabel Template Section
                                $fieldVariables = $template->field_variables ?? [];

                                $components[] = TextInput::make('perihal')
                                    ->label('Subjek / Judul');

                                if (empty($fieldVariables)) {
                                    $components[] = TextEntry::make('no_vars')
                                        ->hiddenLabel()
                                        ->state('Template ini tidak membutuhkan isian variabel tambahan.');
                                } else {
                                    $service = app(\App\Services\FormSchemaService::class);
                                    $dynamicSchema = $service->generateFilamentSchema($fieldVariables);

                                    $components[] = Section::make('VARIABEL TEMPLATE')
                                        ->schema($dynamicSchema)
                                        ->icon('heroicon-o-list-bullet')
                                        ->collapsible(false);
                                }

                                return $components;
                            })
                                ->visible(fn(Get $get) => $get('template_id') !== 'scratch' && $get('template_id') !== null),
                        ]),
                    // STEP 4: LAMPIRAN
                    Step::make('Lampiran')
                        ->description('Upload dokumen pendukung')
                        ->schema([

                            TextEntry::make('lampiran_sebelumnya')
                                ->label('Berkas Lampiran Sebelumnya')
                                ->visible(fn() => (bool) $this->revisiSurat && $this->revisiSurat->getMedia('lampiran-surat')->isNotEmpty())
                                ->state(function () {
                                    $mediaList = $this->revisiSurat ? $this->revisiSurat->getMedia('lampiran-surat') : collect();
                                    if ($mediaList->isEmpty()) {
                                        return null;
                                    }
                                    $itemsHtml = '';
                                    foreach ($mediaList as $media) {
                                        $sizeKb = number_format($media->size / 1024, 1);
                                        $ext = strtoupper($media->extension ?: 'FILE');
                                        $itemsHtml .= <<<HTML
                                        <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                                            <div class="flex items-center gap-3 overflow-hidden">
                                                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0 text-primary-600 font-bold text-xs uppercase">
                                                    {$ext}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate max-w-[180px] sm:max-w-[220px]" title="{$media->file_name}">
                                                        {$media->file_name}
                                                    </p>
                                                    <p class="text-xs text-gray-400">{$sizeKb} KB</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <button type="button" wire:click="downloadExistingMedia({$media->id})" title="Unduh Berkas" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                </button>
                                                <button type="button" wire:click="deleteExistingMedia({$media->id})" wire:confirm="Yakin ingin menghapus berkas lampiran ini?" title="Hapus Berkas" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </div>
                                        HTML;
                                    }
                                    return new HtmlString(<<<HTML
                                    <div class="mb-4 rounded-xl shadow-sm border border-gray-200 p-4 bg-white">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                <span class="text-sm font-bold text-gray-900 dark:text-white">Lampiran yang Tersimpan Sebelumnya</span>
                                            </div>
                                            <span class="text-xs text-red-500">Hapus jika ingin membuang/menggantinya</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            {$itemsHtml}
                                        </div>
                                    </div>
                                    HTML);
                                })
                                ->columnSpanFull(),

                            FileUpload::make('lampiran')
                                ->label('Unggah Lampiran')
                                ->helperText('Format yang didukung: PDF, JPG, PNG. Ukuran maksimal 5MB per file.')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(5120)
                                ->multiple() // Allows multiple files as per your design
                                ->storeFileNamesIn('lampiran_names')
                                ->panelLayout('grid')
                                ->downloadable()
                                ->columnSpanFull(),
                        ]),
                    // STEP 5: PRATINJAU
                    Step::make('Pratinjau')
                        ->description('Tinjau kembali pengajuan Anda')
                        ->schema([

                            TextEntry::make('summary')
                                ->hiddenLabel()
                                ->state(function (Get $get) {
                                    $isScratch = ($get('template_id') ?? '') === 'scratch';
                                    $renderedHtml = '';

                                    if ($isScratch) {
                                        $renderedHtml = $get('content_scratch') ?? '';
                                        $service = app(\App\Services\PlaceholderService::class);
                                        $fakeTemplate = new \App\Models\Template();
                                        $fakeTemplate->content_html = $renderedHtml;
                                        $renderedHtml = $service->renderHtml($fakeTemplate, $get('content') ?? []);
                                    } else {
                                        $templateId = $get('template_id');
                                        if ($templateId) {
                                            $template = \App\Models\Template::find($templateId);
                                            $service = app(\App\Services\PlaceholderService::class);
                                            $renderedHtml = $service->renderHtml($template, $get('content') ?? []);
                                        }
                                    }

                                    return view('components.pengajuan-summary', [
                                        'data' => [
                                            'template_id' => $get('template_id'),
                                            'tipe_pengirim' => $get('tipe_pengirim'),
                                            'pengirim_nama' => $get('pengirim_nama'),
                                            'pengirim_nim' => $get('pengirim_nim'),
                                            'pengirim_fakultas' => $get('pengirim_fakultas'),
                                            'pengirim_instansi' => $get('pengirim_instansi'),
                                            'pengirim_email' => $get('pengirim_email'),
                                            'pengirim_telp' => $get('pengirim_telp'),
                                            'unit_tujuan' => $get('unit_tujuan'),
                                            'perihal' => $get('perihal'),
                                            'content' => $get('content'),
                                            'lampiran' => $get('lampiran'),
                                            'lampiran_names' => $get('lampiran_names'),
                                        ],
                                        'renderedHtml' => $renderedHtml
                                    ]);
                                })
                                ->columnSpanFull(),

                            Section::make()
                                ->schema([


                                    // show only on mode revisi
                                    \Filament\Forms\Components\Textarea::make('catatan_perbaikan')
                                        ->label('Penjelasan Perbaikan untuk Petugas')
                                        ->placeholder('Jelaskan bagian apa saja yang telah Anda perbaiki...')
                                        ->rows(3)
                                        ->visible(fn() => (bool) $this->revisiSurat)
                                        ->required(fn() => (bool) $this->revisiSurat),

                                    Checkbox::make('konfirmasi')
                                        ->label('Saya menyatakan bahwa seluruh data yang diisi adalah benar dan sah sesuai dengan peraturan Universitas. Saya bertanggung jawab sepenuhnya atas kebenaran informasi dalam pengajuan ini.')
                                        ->required()
                                        ->accepted(),
                                ])

                                ->columnSpanFull(),
                        ]),
                ])
                    ->startOnStep(fn() => $this->revisiSurat ? 2 : 1)
                    ->skippable(fn() => (bool) $this->revisiSurat)
                    ->previousAction(
                        fn(Action $action) => $action
                            ->label('Kembali')
                    )
                    ->nextAction(
                        fn(Action $action) => $action
                            ->label('Lanjutkan')
                            ->extraAttributes([
                                'style' => ' background-color: var(--color-primary-600);',
                                // Injects your specific RGB definitions directly into the inline utility
                                'class' => '!text-white'
                            ])
                    )
                    ->persistStepInQueryString()
                    ->contained(false)

                    ->submitAction(
                        new \Illuminate\Support\HtmlString(
                            '<button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-6 rounded-lg transition shadow-sm shadow-primary-200">' .
                                ($this->revisiSurat ? 'Kirim Ulang Perbaikan &nearr;' : 'Kirim Sekarang &nearr;') .
                                '</button>'
                        )
                    )
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $state = $this->form->getState();

        $isScratch = $state['template_id'] === 'scratch';

        if ($this->revisiSurat) {
            $surat = $this->revisiSurat;
            // 1. Update data identitas pengirim
            $surat->pengirim_nama = $state['pengirim_nama'] ?? $surat->pengirim_nama;
            $surat->pengirim_email = $state['pengirim_email'] ?? $surat->pengirim_email;
            $surat->nomor_surat_eksternal = $state['nomor_surat_eksternal'] ?? null;
            $metadata = $surat->pengirim_metadata ?? [];
            $metadata['telp'] = $state['pengirim_telp'] ?? null;
            if ($state['tipe_pengirim'] === 'mahasiswa') {
                $surat->pengirim_nim = $state['pengirim_nim'] ?? null;
                $metadata['fakultas_id'] = $state['pengirim_fakultas'] ?? null;
                $metadata['prodi_id'] = $state['pengirim_prodi'] ?? null;
            } else {
                $metadata['instansi'] = $state['pengirim_instansi'] ?? null;
            }
            $surat->pengirim_metadata = $metadata;
            // 2. Update konten surat (Scratch vs Template)
            if ($isScratch) {
                $surat->perihal = $state['perihal'] ?? $surat->perihal;
                $scratchContent = $state['content'] ?? [];
                $scratchContent['isi_surat'] = $state['content_scratch'] ?? '';
                $surat->content = $scratchContent;
            } else {
                $template = \App\Models\Template::find($state['template_id']);
                $surat->perihal = 'Pengajuan ' . ($template?->nama_template ?? '');
                $surat->content = $state['content'] ?? []; // Menyimpan isian variabel template yang baru dikoreksi
            }
            // 3. Simpan lampiran baru (jika pemohon mengunggah berkas baru)
            if (!empty($state['lampiran'])) {
                $privateStorage = \Illuminate\Support\Facades\Storage::disk('private');
                foreach ($state['lampiran'] as $index => $file) {
                    if (is_object($file) && method_exists($file, 'getRealPath')) {
                        $surat->addMedia($file->getRealPath())
                            ->usingFileName($file->getClientOriginalName())
                            ->toMediaCollection('lampiran-surat');
                    } elseif (is_string($file)) {
                        $fullPath = $privateStorage->path($file);
                        if (file_exists($fullPath)) {
                            $originalName = $state['lampiran_names'][$file] ?? $state['lampiran_names'][$index] ?? basename($file);
                            $surat->addMedia($fullPath)
                                ->usingFileName($originalName)
                                ->toMediaCollection('lampiran-surat');
                        }
                    }
                }
            }
            $surat->save();
            // 4. Catat riwayat alur persetujuan: DIPERBARUI & MENUNGGU
            $lastRevisi = $surat->riwayats->where('status', 'REVISI')->last();
            $targetUnitId = $lastRevisi?->unit_tujuan_id ?? $surat->unitTujuan->first()?->id;
            $unitAsalId = $surat->unit_pengirim_id ?? $targetUnitId;
            \App\Models\SuratRiwayat::create([
                'surat_id'       => $surat->id,
                'parent_id'      => $lastRevisi?->id,
                'unit_asal_id'   => $unitAsalId,
                'unit_tujuan_id' => $targetUnitId,
                'user_aktor_id'  => null,
                'status'         => 'DIPERBARUI',
                'catatan'        => $state['catatan_perbaikan'] ?? 'Pemohon telah memperbarui dokumen permohonan.',
                'actioned_at'    => now(),
            ]);
            \App\Models\SuratRiwayat::create([
                'surat_id'       => $surat->id,
                'parent_id'      => null,
                'unit_asal_id'   => $unitAsalId,
                'unit_tujuan_id' => $targetUnitId,
                'user_aktor_id'  => null,
                'status'         => 'MENUNGGU',
                'catatan'        => '',
                'actioned_at'    => null,
            ]);
            // 5. Kembalikan status surat ke DIPROSES
            $surat->update(['status_surat' => 'DIPROSES']);
            // 6. Notifikasi sistem ke unit pemeriksa
            if ($targetUnitId) {
                $targetUsers = \App\Models\User::ofUnitKerja($targetUnitId)->get();
                if ($targetUsers->isNotEmpty()) {
                    \Filament\Notifications\Notification::make()
                        ->title('Berkas Pengajuan Telah Diperbaiki')
                        ->body('Pemohon ' . ($surat->pengirim_nama ?? 'Guest') . ' telah memperbarui berkas untuk surat: ' . $surat->perihal)
                        ->info()
                        ->viewData([
                            'unit_kerja_id' => (int) $targetUnitId,
                            'surat_id'      => $surat->id,
                        ])
                        ->sendToDatabase($targetUsers);
                }
            }
            // Redirect kembali ke halaman pelacakan
            return redirect()->route('lacak', ['code' => $surat->tracking_code]);
        }

        $surat = new \App\Models\Surat();

        $surat->status_surat = 'TERKIRIM';
        $surat->pengirim_nama = $state['pengirim_nama'] ?? null;
        $surat->pengirim_email = $state['pengirim_email'] ?? null;
        $surat->tanggal_kirim = now();

        $metadata = [
            'tipe_pengirim' => $state['tipe_pengirim'] ?? 'guest',
            'telp' => $state['pengirim_telp'] ?? null,
        ];

        if ($state['tipe_pengirim'] === 'mahasiswa') {
            $surat->pengirim_nim = $state['pengirim_nim'] ?? null;
            $metadata['fakultas_id'] = $state['pengirim_fakultas'] ?? null;
            $metadata['prodi_id'] = $state['pengirim_prodi'] ?? null;
        } else {
            $metadata['instansi'] = $state['pengirim_instansi'] ?? null;
        }
        $surat->pengirim_metadata = $metadata;
        $surat->nomor_surat_eksternal = $state['nomor_surat_eksternal'] ?? null;

        if ($isScratch) {
            $surat->tipe_surat = !empty($state['nomor_surat_eksternal']) ? 'EKSTERNAL' : 'PENGAJUAN'; // Default surat from external
            $surat->template_id = null;
            $surat->perihal = $state['perihal'] ?? 'Pengajuan Guest';
            $scratchContent = $state['content'] ?? [];
            $scratchContent['isi_surat'] = $state['content_scratch'] ?? '';
            $surat->content = $scratchContent;
        } else {
            $surat->template_id = $state['template_id'];
            $template = \App\Models\Template::find($state['template_id']);

            $surat->tipe_surat = $template?->tipe_surat ?: (!empty($state['nomor_surat_eksternal']) ? 'EKSTERNAL' : 'PENGAJUAN');

            if (!empty($template?->approval_path)) {
                $surat->approval_path = $template->approval_path;
            }

            $surat->perihal = $state['perihal']; //'Pengajuan ' . ($template?->nama_template ?? '');
            $surat->content = $state['content'] ?? [];
        }

        $surat->save();


        // Generate random tracking code
        // Buat hash 6 karakter dari ID + APP_KEY agar tidak mudah ditebak urutan ID-nya
        $surat->tracking_code = 'REQ-' . strtoupper(Hashids::encode($surat->id));
        // Format: REQ-{ID}-{HASH}, contoh: REQ-1042-8F2A1C
        // $surat->tracking_code = "REQ-{$surat->id}-{$hash}";
        $surat->save();

        // Attach unit tujuan
        if ($isScratch && !empty($state['unit_tujuan'])) {
            $surat->unitTujuan()->attach($state['unit_tujuan'], [
                'jenis_tujuan' => 'UTAMA',
                'tanggal_terima' => now(),
                'status_baca' => 'BELUM',
            ]);
        } elseif (!$isScratch && isset($template) && $template->entry_point_unit_id) {
            $surat->unitTujuan()->attach($template->entry_point_unit_id, [
                'jenis_tujuan' => 'UTAMA',
                'tanggal_terima' => now(),
                'status_baca' => 'BELUM',
            ]);
        }

        $targetUnitId = null;
        if ($isScratch && !empty($state['unit_tujuan'])) {
            $targetUnitId = (int) $state['unit_tujuan'];
        } elseif (!$isScratch && isset($template) && $template->entry_point_unit_id) {
            $targetUnitId = (int) $template->entry_point_unit_id;
        }


        // Inisialisasi langkah awal alur persetujuan (Workflow)
        if ($targetUnitId) {

            $surat->unitTujuan()->syncWithoutDetaching([
                $targetUnitId => [
                    'jenis_tujuan'   => 'UTAMA',
                    'tanggal_terima' => now(),
                    'status_baca'    => 'BELUM',
                ],
            ]);

            $unitAsalId = $state['pengirim_prodi']
                ?? $state['pengirim_fakultas']
                ?? $targetUnitId;

            \App\Models\SuratRiwayat::create([
                'surat_id'       => $surat->id,
                'parent_id'      => null,
                'unit_asal_id'   => $unitAsalId,
                'unit_tujuan_id' => $targetUnitId,
                'user_aktor_id'  => null,
                'status'         => 'MENUNGGU',
                'catatan'        => 'Pengajuan baru dari: ' . ($surat->pengirim_nama ?? 'Guest'),
                'actioned_at'    => null,
            ]);
        }


        // Process file uploads (Spatie Media Library)
        if (!empty($state['lampiran'])) {
            $privateStorage = \Illuminate\Support\Facades\Storage::disk('private');
            foreach ($state['lampiran'] as $index => $file) {
                // Jika berupa object upload langsung
                if (is_object($file) && method_exists($file, 'getRealPath')) {
                    $surat->addMedia($file->getRealPath())
                        ->usingFileName($file->getClientOriginalName())
                        ->toMediaCollection('lampiran-surat');
                }
                // Jika berupa string path dari FileUpload
                elseif (is_string($file)) {
                    $fullPath = $privateStorage->path($file);
                    if (file_exists($fullPath)) {
                        $originalName = $state['lampiran_names'][$file]
                            ?? $state['lampiran_names'][$index]
                            ?? basename($file);
                        $surat->addMedia($fullPath)
                            ->usingFileName($originalName)
                            ->toMediaCollection('lampiran-surat');
                    }
                }
            }
        }
        // if (!empty($state['lampiran'])) {
        //     foreach ($state['lampiran'] as $file) {
        //         if (is_object($file) && method_exists($file, 'getRealPath')) {
        //             $surat->addMedia($file->getRealPath())
        //                 ->usingFileName($file->getClientOriginalName())
        //                 ->toMediaCollection('lampiran-surat');
        //         } elseif (is_string($file)) {
        //             $path = storage_path('app/public/' . $file);
        //             if (file_exists($path)) {
        //                 $surat->addMedia($path)
        //                     ->toMediaCollection('lampiran-surat');
        //             }
        //         }
        //     }
        // }

        // Clear the form data
        $this->data = [];

        // Show the success screen with the tracking code
        $this->trackingCode = $surat->tracking_code;
        $this->submitted = true;

        // NOTIFIKASI KE PENERIMA SESUAI KEBIJAKAN AKSES UNIT
        $targetUnitId = $targetUnitId ?? $surat->unitTujuan()->first()?->id;

        if ($targetUnitId) {
            // Ambil staf unit dan filter HANYA mereka yang berhak menerima/melihat surat masuk
            // (Memperhitungkan: Kepala Unit, delegasi SEMUA, kebijakan TERBUKA/LEVEL_JABATAN/TERBATAS_DISPOSISI)
            $targetUsers = \App\Models\User::ofUnitKerja($targetUnitId)
                ->get()
                ->filter(fn(\App\Models\User $user) => $user->canViewAllSuratMasukUnit($targetUnitId));

            // Fallback pengaman: jika policy sangat ketat hingga kosong, pastikan minimal Kepala Unit menerima
            if ($targetUsers->isEmpty()) {
                $kepala = \App\Models\UnitKerja::find($targetUnitId)?->getKepalaUnit()?->pegawai?->user;
                if ($kepala) {
                    $targetUsers = collect([$kepala]);
                }
            }

            if ($targetUsers->isNotEmpty()) {
                // 1. Notifikasi In-App Database Filament (Hanya ke user yang berhak melihat surat)
                \Filament\Notifications\Notification::make()
                    ->title('Pengajuan Surat Baru Masuk')
                    ->body('Terdapat permohonan surat baru dari ' . ($surat->pengirim_nama ?? 'Pemohon') . ': ' . $surat->perihal)
                    ->info()
                    ->viewData([
                        'unit_kerja_id' => (int) $targetUnitId,
                        'surat_id'      => $surat->id,
                    ])
                    ->sendToDatabase($targetUsers);

                // 2. Notifikasi WhatsApp ke Petugas yang Berhak (dan mengaktifkan notif WA surat_masuk)
                app(\App\Services\WhatsAppNotificationService::class)->notifySuratMasuk($surat, $targetUsers);
            }
        }

        // 3. (Opsional) Kirim Tanda Terima & Tautan Lacak Langsung ke WhatsApp Pemohon
        $pemohonPhone = $state['pengirim_telp'] ?? null;
        if (!empty($pemohonPhone)) {
            $appUrl = rtrim(config('app.url', config('app.asset_url', url('/'))), '/');
            $namaPemohon = $surat->pengirim_nama ?? 'Pemohon';
            $pesanKonfirmasi = "*SIMAS: Konfirmasi Pengajuan Surat*\n"
                . "Halo, *{$namaPemohon}*!\n\n"
                . "Permohonan surat Anda telah berhasil dikirimkan ke unit tujuan dengan rincian:\n"
                . "• *Kode Lacak*: *{$surat->tracking_code}*\n"
                . "• *Perihal*: {$surat->perihal}\n"
                . "• *Waktu Pengajuan*: " . now()->translatedFormat('d F Y H:i') . "\n\n"
                . "Anda dapat memantau status surat secara berkala melalui tautan:\n"
                . "🔗 {$appUrl}/lacak?code={$surat->tracking_code}\n\n"
                . "_Pesan otomatis dikirim oleh Sistem Informasi Manajemen Arsip dan Surat (SIMAS)._";

            app(\App\Services\FonnteService::class)->send($pemohonPhone, $pesanKonfirmasi);
        }
    }

    public function downloadExistingMedia(int $mediaId)
    {
        if ($this->revisiSurat) {
            $media = $this->revisiSurat->media()->where('id', $mediaId)->first();
            if ($media && file_exists($media->getPath())) {
                return response()->download($media->getPath(), $media->file_name);
            }
        }
    }

    public function deleteExistingMedia(int $mediaId): void
    {
        if ($this->revisiSurat) {
            $media = $this->revisiSurat->media()->where('id', $mediaId)->first();
            if ($media) {
                $media->delete();
                $this->revisiSurat->load('media');

                \Filament\Notifications\Notification::make()
                    ->title('Lampiran Dihapus')
                    ->body('Berkas lampiran lama berhasil dihapus.')
                    ->success()
                    ->send();
            }
        }
    }

    public function downloadDraft()
    {
        $state = $this->form->getRawState();

        // Extract lampiran names from TemporaryUploadedFile or file array if lampiran_names is missing
        if (empty($state['lampiran_names']) && !empty($state['lampiran'])) {
            $lampiranNames = [];
            foreach ($state['lampiran'] as $file) {
                if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                    $lampiranNames[] = $file->getClientOriginalName();
                } elseif (is_string($file)) {
                    $lampiranNames[] = basename($file);
                }
            }
            $state['lampiran_names'] = $lampiranNames;
        }

        $isScratch = ($state['template_id'] ?? '') === 'scratch';

        $pengirim = $state['pengirim_nama'] ?? 'Guest';
        $tujuan = '-';
        $perihal = $state['perihal'] ?? '-';
        $renderedHtml = '';

        if ($isScratch) {
            $unitId = $state['unit_tujuan'] ?? null;
            if ($unitId) {
                $tujuan = \App\Models\UnitKerja::find($unitId)?->nama_unit ?? '-';
            }
            $renderedHtml = $state['content_scratch'] ?? '';
        } else {
            $templateId = $state['template_id'] ?? null;
            if ($templateId) {
                $template = \App\Models\Template::with('entryPointUnit')->find($templateId);
                $perihal = 'Pengajuan ' . ($template?->nama_template ?? '');
                $tujuan = $template?->entryPointUnit?->nama_unit ?? 'Sesuai Template';

                $service = app(\App\Services\PlaceholderService::class);
                $renderedHtml = $service->renderHtml($template, $state['content'] ?? []);
            }
        }

        // Setup mock Surat for the surat view to prevent relation null errors
        $mockSurat = new \App\Models\Surat([
            'nomor_surat' => 'DRAF',
            'nomor_agenda' => '-',
            'perihal' => $perihal,
            'tanggal_kirim' => now(),
        ]);

        $mockUnit = new \App\Models\UnitKerja(['nama_unit' => $tujuan]);
        $mockPembuat = new \App\Models\User(['nama_lengkap' => $pengirim]);

        $mockSurat->setRelation('unitPengirim', $mockUnit);
        $mockSurat->setRelation('pembuat', $mockPembuat);

        // Generate the HTML for the main letter (reusing the clean, watermark-free view)
        $suratHtml = view('filament.exports.surat.surat', [
            'surat' => $mockSurat,
            'isArsip' => false,
            'renderedHtml' => $renderedHtml
        ])->render();

        // Generate the separate metadata page
        $metadataHtml = view('filament.exports.surat.metadata', [
            'state' => $state,
            'tujuan' => $tujuan,

        ])->render();

        // Inject metadata at the end of the surat HTML with a page break
        $combinedHtml = str_replace('</body>', '<div style="page-break-before: always;"></div>' . $metadataHtml . '</body>', $suratHtml);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($combinedHtml);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Draf_Pengajuan_' . date('Ymd_His') . '.pdf');
    }

    public function render(): View
    {
        return view('livewire.guest-pengajuan');
    }
}
