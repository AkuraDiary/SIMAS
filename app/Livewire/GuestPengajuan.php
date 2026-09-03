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
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;


#[Layout('components.layouts.app')]
class GuestPengajuan extends Component implements HasForms
{
    use InteractsWithForms;

    // We will bind all form data to this array
    public ?array $data = [];

    public bool $submitted = false;
    public ?string $trackingCode = null;

    public function mount(): void
    {
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
                                \Filament\Forms\Components\Placeholder::make('detail_surat_title')
                                    ->hiddenLabel()
                                    ->content(new \Illuminate\Support\HtmlString('<h2 class="text-xl font-bold text-gray-900 mb-2">Detail Surat</h2>')),

                                Select::make('unit_tujuan')
                                    ->label('Unit Tujuan')
                                    ->options(function() {
                                        return \App\Models\UnitKerja::pluck('nama_unit', 'id');
                                    })
                                    ->required(fn(Get $get) => $get('template_id') === 'scratch'),

                                TextInput::make('perihal')
                                    ->label('Subjek / Judul')
                                    ->required(fn(Get $get) => $get('template_id') === 'scratch'),

                                TinyEditor::make('content_scratch')
                                    ->label('Isi Surat')
                                    ->profile('full')
                                    ->placeholder('Tuliskan isi surat secara bebas di sini...')
                                    ->visible(fn(Get $get) => $get('template_id') === 'scratch')
                                    ->required(fn(Get $get) => $get('template_id') === 'scratch')
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn(Get $get) => $get('template_id') === 'scratch'),

                            // TEMPLATE MODE
                            Group::make()->schema(function(Get $get) {
                                $templateId = $get('template_id');
                                if (! $templateId || $templateId === 'scratch') return [];

                                $template = \App\Models\Template::find($templateId);
                                if (! $template) return [];

                                $components = [];

                                // Header info
                                $components[] = \Filament\Forms\Components\Placeholder::make('template_info')
                                    ->hiddenLabel()
                                    ->content(new \Illuminate\Support\HtmlString('
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-900 mb-4">Detail Surat</h2>
                                            <div class="flex items-center gap-2 mb-4">
                                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                <span class="text-sm text-gray-600">Template Terpilih:</span>
                                                <span class="text-sm font-bold text-primary-600">'. e($template->nama_template) .'</span>
                                            </div>
                                            <hr class="border-gray-200">
                                        </div>
                                    '))->columnSpanFull();

                                // Variabel Template Section
                                $fieldVariables = $template->field_variables ?? [];

                                if (empty($fieldVariables)) {
                                    $components[] = \Filament\Forms\Components\Placeholder::make('no_vars')
                                        ->hiddenLabel()
                                        ->content('Template ini tidak membutuhkan isian variabel tambahan.');
                                } else {
                                    $service = app(\App\Services\FormSchemaService::class);
                                    $dynamicSchema = $service->generateFilamentSchema($fieldVariables);

                                    $components[] = \Filament\Forms\Components\Section::make('VARIABEL TEMPLATE')
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
                            FileUpload::make('lampiran')
                                ->label('Unggah Lampiran')
                                ->helperText('Format yang didukung: PDF, JPG, PNG. Ukuran maksimal 5MB per file.')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(5120)
                                ->multiple() // Allows multiple files as per your design
                                ->panelLayout('grid')
                                ->downloadable()
                                ->columnSpanFull(),
                        ]),
                    // STEP 5: PRATINJAU
                    Step::make('Pratinjau')
                        ->description('Tinjau kembali pengajuan Anda')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('summary')
                                ->hiddenLabel()
                                ->content(function(Get $get) {
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
                                        ]
                                    ]);
                                })
                                ->columnSpanFull(),

                            Section::make()
                                ->schema([
                                    Checkbox::make('konfirmasi')
                                        ->label('Saya menyatakan bahwa seluruh data yang diisi adalah benar dan sah sesuai dengan peraturan Universitas. Saya bertanggung jawab sepenuhnya atas kebenaran informasi dalam pengajuan ini.')
                                        ->required()
                                        ->accepted(),
                                ])
                                ->extraAttributes(['class' => 'bg-gray-50 border border-gray-200 shadow-sm'])
                                ->columnSpanFull(),
                        ]),
                ])

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


                    ->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-6 rounded-lg transition shadow-sm shadow-primary-200">Kirim Sekarang &nearr;</button>'))
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $state = $this->form->getState();

        $isScratch = $state['template_id'] === 'scratch';

        $surat = new \App\Models\Surat();
        $surat->tipe_surat = 'PENGAJUAN';
        $surat->status_surat = 'PENDING';
        $surat->pengirim_nama = $state['pengirim_nama'] ?? null;
        $surat->pengirim_email = $state['pengirim_email'] ?? null;

        // Generate random tracking code
        $surat->tracking_code = strtoupper(\Illuminate\Support\Str::random(10));

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

        if ($isScratch) {
            $surat->template_id = null;
            $surat->perihal = $state['perihal'] ?? 'Pengajuan Guest';
            $surat->content = ['html' => $state['content_scratch'] ?? ''];
        } else {
            $surat->template_id = $state['template_id'];
            $template = \App\Models\Template::find($state['template_id']);
            $surat->perihal = 'Pengajuan ' . ($template?->nama_template ?? '');
            $surat->content = $state['content'] ?? [];
        }

        $surat->save();

        // Attach unit tujuan
        if ($isScratch && !empty($state['unit_tujuan'])) {
            $surat->unitTujuan()->attach($state['unit_tujuan'], [
                'jenis_tujuan' => 'UTAMA',
                'tanggal_terima' => now(),
                'status_baca' => false,
            ]);
        } elseif (!$isScratch && isset($template) && $template->entry_point_unit_id) {
            $surat->unitTujuan()->attach($template->entry_point_unit_id, [
                'jenis_tujuan' => 'UTAMA',
                'tanggal_terima' => now(),
                'status_baca' => false,
            ]);
        }

        // Process file uploads (Spatie Media Library)
        if (!empty($state['lampiran'])) {
            foreach ($state['lampiran'] as $file) {
                if (is_string($file)) {
                    $path = storage_path('app/public/' . $file);
                    if (file_exists($path)) {
                        $surat->addMedia($path)
                              ->toMediaCollection('lampiran-surat');
                    }
                }
            }
        }

        // Clear the form data
        $this->data = [];

        // Show the success screen with the tracking code
        $this->trackingCode = $surat->tracking_code;
        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.guest-pengajuan');
    }
}
