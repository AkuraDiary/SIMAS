<?php

namespace App\Filament\Resources\MahasiswaSurats\Schemas;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use App\Models\Template;
use App\Models\UnitKerja;
use App\Services\FormSchemaService;
use App\Services\PlaceholderService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SuratForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    // -------------------------------------------------------------
                    // LANGKAH 1: PILIH TEMPLATE (Reuse Component Template Selector)
                    // -------------------------------------------------------------
                    Step::make('Pilih Template')
                        ->description('Pilih template permohonan surat')
                        ->schema([
                            ViewField::make('template_id')
                                ->view('components.template-selector')
                                ->default('scratch')
                                ->columnSpanFull()
                                ->required(),
                        ]),
                    // -------------------------------------------------------------
                    // LANGKAH 2: DATA PEMOHON (Otomatis Terisi dari Akun Login)
                    // -------------------------------------------------------------
                    Step::make('Data Pemohon')
                        ->description('Verifikasi identitas mahasiswa')
                        ->schema(function () {
                            $user = Auth::user();
                            $mhs = $user?->mahasiswa;
                            return [
                                Section::make('Identitas Terverifikasi')
                                    ->description('Informasi diambil langsung dari data profil akademik Anda.')
                                    ->schema([
                                        TextInput::make('pengirim_nama')
                                            ->label('Nama Lengkap')
                                            ->default($mhs?->nama_lengkap ?? $user?->nama_lengkap)
                                            ->readOnly()
                                            ->required(),
                                        TextInput::make('pengirim_nim')
                                            ->label('NIM')
                                            ->default($mhs?->nim ?? '-')
                                            ->readOnly()
                                            ->required(),
                                        TextInput::make('pengirim_email')
                                            ->label('Email')
                                            ->default($user?->email)
                                            ->readOnly()
                                            ->required(),
                                        TextInput::make('pengirim_telp')
                                            ->label('Nomor WhatsApp (Aktif)')
                                            ->default($user?->phone)
                                            ->placeholder('Contoh: 081234567890')
                                            ->helperText('Notifikasi kemajuan surat akan dikirimkan ke nomor ini.')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ];
                        }),
                    // -------------------------------------------------------------
                    // LANGKAH 3: ISI DOKUMEN & LAMPIRAN
                    // -------------------------------------------------------------
                    Step::make('Isi Dokumen & Berkas')
                        ->description('Lengkapi variabel surat & dokumen pendukung')
                        ->schema([
                            // A. JIKA MEMILIH SCRATCH (BEBAS)
                            Group::make()->schema([
                                TextInput::make('perihal')
                                    ->label('Perihal Surat')
                                    ->placeholder('Contoh: Permohonan Surat Rekomendasi Beasiswa')
                                    ->required(fn(Get $get) => $get('template_id') === 'scratch')
                                    ->columnSpanFull(),
                                Select::make('unit_tujuan')
                                    ->label('Unit / Fakultas / Prodi Tujuan')
                                    ->options(fn() => UnitKerja::where('is_active', true)->pluck('nama_unit', 'id'))
                                    ->searchable()
                                    ->required(fn(Get $get) => $get('template_id') === 'scratch')
                                    ->columnSpanFull(),
                                TinyEditor::make('content_scratch')
                                    ->label('Isi Surat Permohonan')
                                    ->setCustomConfigs([
                                        'font_family_formats' => 'Arial=arial,helvetica,sans-serif; Times New Roman=times new roman,times; Verdana=verdana,geneva',
                                    ])
                                    ->profile('full')
                                    ->placeholder('Tuliskan permohonan Anda secara jelas di sini...')
                                    ->required(fn(Get $get) => $get('template_id') === 'scratch')
                                    ->columnSpanFull(),
                            ])
                                ->visible(fn(Get $get) => $get('template_id') === 'scratch' || empty($get('template_id'))),
                            // B. JIKA MEMILIH TEMPLATE RESMI (Dynamic Schema dari FormSchemaService)
                            Group::make()->schema(function (Get $get) {
                                $templateId = $get('template_id');
                                if (!$templateId || $templateId === 'scratch') return [];
                                $template = Template::find($templateId);
                                if (!$template) return [];
                                $components = [];
                                // Header info template
                                $components[] = TextEntry::make('info_template_terpilih')
                                    ->hiddenLabel()
                                    ->state(new HtmlString("
                                        <div class='p-4 bg-primary-50/60 dark:bg-primary-950/20 border border-primary-200 dark:border-primary-800 rounded-xl mb-4'>
                                            <span class='text-xs font-bold uppercase tracking-wider text-primary-700 dark:text-primary-400'>Template Terpilih:</span>
                                            <p class='text-base font-bold text-gray-900 dark:text-white mt-0.5'>{$template->nama_template}</p>
                                            <p class='text-xs text-gray-500 dark:text-gray-400 mt-1'>{$template->deskripsi}</p>
                                        </div>
                                    "))->columnSpanFull();
                                // Gunakan kembali FormSchemaService untuk merender seluruh variabel template
                                $dynamicFields = app(FormSchemaService::class)->generateFilamentSchema($template->field_variables ?? []);
                                return array_merge($components, $dynamicFields);
                            })
                                ->visible(fn(Get $get) => filled($get('template_id')) && $get('template_id') !== 'scratch'),
                            // C. DOKUMEN PENDUKUNG (Spatie Media Library Upload)
                            Section::make('Dokumen Pendukung / Lampiran')
                                ->schema([
                                    SpatieMediaLibraryFileUpload::make('lampiran')
                                        ->label('Unggah Berkas Persyaratan')
                                        ->collection('lampiran-surat')
                                        ->multiple()
                                        ->reorderable()
                                        ->maxFiles(5)
                                        ->maxSize(10240)
                                        ->helperText('Format PDF, JPG, atau PNG (Maks. 10MB per file). Unggah KTM, Transkrip, atau berkas pendukung lainnya.')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    // -------------------------------------------------------------
                    // LANGKAH 4: PRATINJAU DRAF SURAT
                    // -------------------------------------------------------------
                    Step::make('Pratinjau')
                        ->description('Tinjau kembali permohonan surat Anda')
                        ->schema([
                            TextEntry::make('summary')
                                ->hiddenLabel()
                                ->state(function (Get $get) {
                                    $isScratch = ($get('template_id') ?? '') === 'scratch';
                                    $renderedHtml = '';

                                    if ($isScratch) {
                                        $renderedHtml = $get('content_scratch') ?? '';
                                        $fakeTemplate = new Template();
                                        $fakeTemplate->content_html = $renderedHtml;
                                        $renderedHtml = app(PlaceholderService::class)->renderHtml($fakeTemplate, $get('content') ?? []);
                                    } else {
                                        $templateId = $get('template_id');
                                        if ($templateId) {
                                            $template = Template::find($templateId);
                                            if ($template) {
                                                $renderedHtml = app(PlaceholderService::class)->renderHtml($template, $get('content') ?? []);
                                            }
                                        }
                                    }

                                    $user = Auth::user();
                                    $mhs = $user?->mahasiswa;

                                    return view('components.pengajuan-summary', [
                                        'data' => [
                                            'template_id'       => $get('template_id'),
                                            'tipe_pengirim'     => 'mahasiswa',
                                            'pengirim_nama'     => $get('pengirim_nama') ?? $mhs?->nama_lengkap ?? $user?->nama_lengkap,
                                            'pengirim_nim'      => $get('pengirim_nim') ?? $mhs?->nim ?? '-',
                                            'pengirim_fakultas' => $mhs?->fakultas_id,
                                            'pengirim_prodi'    => $mhs?->prodi_id,
                                            'pengirim_email'    => $get('pengirim_email') ?? $user?->email,
                                            'pengirim_telp'     => $get('pengirim_telp') ?? $user?->phone,
                                            'unit_tujuan'       => $get('unit_tujuan'),
                                            'perihal'           => $get('perihal'),
                                            'content'           => $get('content'),
                                            'lampiran'          => $get('lampiran'),
                                        ],
                                        'renderedHtml' => $renderedHtml
                                    ]);
                                })
                                ->columnSpanFull(),

                            Section::make()
                                ->schema([
                                    Checkbox::make('konfirmasi')
                                        ->label('Saya menyatakan bahwa seluruh data yang diisi adalah benar dan sah sesuai dengan peraturan Universitas. Saya bertanggung jawab sepenuhnya atas kebenaran informasi dalam pengajuan ini.')
                                        ->required()
                                        ->accepted()
                                        ->dehydrated(false),
                                ])
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->submitAction(
                        new HtmlString('<button type="submit" class="filament-button px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">Kirim Pengajuan Sekarang &rarr;</button>')
                    ),
            ]);
    }
}
