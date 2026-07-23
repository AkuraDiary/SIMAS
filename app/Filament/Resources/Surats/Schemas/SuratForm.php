<?php

namespace App\Filament\Resources\Surats\Schemas;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use App\Models\Template;
use App\Models\UnitKerja;
use App\Services\PlaceholderService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                Grid::make(4)->schema([
                    // LEFT COLUMN
                    Group::make()->schema([
                        Section::make('Detail Surat')->schema([
                            Radio::make('metode_pembuatan')
                                ->label('Metode Pembuatan')
                                ->options([
                                    'template' => 'Gunakan Template',
                                    'scratch' => 'Tulis dari Awal (From Scratch)',
                                ])
                                ->default('template')
                                ->inline()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('template_id', null);
                                    $set('content', []);
                                    $set('isi_surat', null);
                                }),

                            Select::make('template_id')
                                ->label('Pilih Template Surat')
                                ->options(Template::query()->pluck('nama_template', 'id'))
                                ->required(fn(Get $get) => $get('metode_pembuatan') === 'template')
                                ->visible(fn(Get $get) => $get('metode_pembuatan') === 'template')
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('content', []);
                                }),
                                
                            Select::make('user_pegawai_jabatan_id')
                                ->label('Kirim Sebagai (Peran / Jabatan)')
                                ->options(function () {
                                    $pegawai = Auth::user()->pegawai;
                                    if (!$pegawai) return [];
                                    return $pegawai->jabatanAktif()
                                        ->with(['jabatan', 'unitKerja'])
                                        ->get()
                                        ->mapWithKeys(function ($upj) {
                                            $jabatanName = $upj->jabatan->nama_jabatan ?? 'Unknown';
                                            $unitName = $upj->unitKerja->nama_unit ?? 'Unknown';
                                            return [$upj->id => "{$jabatanName} - {$unitName}"];
                                        });
                                })
                                ->required()
                                ->default(function () {
                                    $pegawai = Auth::user()->pegawai;
                                    if (!$pegawai) return null;
                                    $first = $pegawai->jabatanAktif()->first();
                                    return $first ? $first->id : null;
                                })
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state) {
                                        $upj = \App\Models\UserPegawaiJabatan::find($state);
                                        if ($upj) {
                                            $set('unit_pengirim_id', $upj->unit_kerja_id);
                                        }
                                    }
                                }),

                            Grid::make()->schema([
                                Select::make('tipe_surat')
                                    ->options(['INTERNAL' => 'Internal', 'EKSTERNAL' => 'Eksternal'])
                                    ->default('INTERNAL')
                                    ->dehydrated()
                                    ->reactive(),
                                TextInput::make('pengirim_eksternal')
                                    ->label('Asal Pengirim')
                                    ->dehydrated()
                                    ->required(fn(Get $get) => $get('tipe_surat') === 'EKSTERNAL')
                                    ->visible(fn(Get $get) => $get('tipe_surat') === 'EKSTERNAL'),
                            ]),
                            
                            Select::make('unitTujuan')
                                ->helperText('Unit pertama dianggap sebagai tujuan utama, sisanya sebagai tembusan')
                                ->label('Penerima (Recipient)')
                                ->multiple()
                                ->relationship(
                                    'unitTujuan',
                                    'nama_unit',
                                    modifyQueryUsing: fn($query) => $query->where('unit_kerjas.id', '<>', Auth::user()->unit_kerja_id)
                                )
                                ->searchable()
                                ->preload(),

                            TextInput::make('perihal')
                                ->label('Perihal Surat (Subject)')
                                ->placeholder('Masukkan judul surat...')
                                ->required(),

                            // Dynamic Content / Rich Editor
                            Group::make()->schema(function (Get $get) {
                                if ($get('metode_pembuatan') === 'scratch') {
                                    return [
                                        TinyEditor::make('isi_surat')
                                            ->profile('full')
                                            ->label('Isi Surat (Content)')
                                            ->placeholder('Tuliskan isi surat secara formal di sini...')
                                            ->required()
                                            ->columnSpanFull()
                                    ];
                                }

                                $templateId = $get('template_id');
                                if (!$templateId) {
                                    return [];
                                }

                                $template = Template::find($templateId);
                                if (!$template || empty($template->field_variables)) {
                                    return [
                                        TextEntry::make('info')
                                            ->label('')
                                            ->state('Template ini tidak memiliki formulir dinamis yang perlu diisi.')
                                    ];
                                }
                                // dd($template);
                                $service = app(PlaceholderService::class);
                                $schema = $service->generateFilamentSchema($template->field_variables);
                                
                                $schema[] = TextEntry::make('preview')
                                    ->label('Pratinjau Surat')
                                    ->state(function (Get $get) use ($template, $service) {
                                        $data = $get('content') ?? [];
                                        
                                        // Force dependency tracking for all nested content keys
                                        foreach ($template->field_variables ?? [] as $field) {
                                            if (!empty($field['key'])) {
                                                if ($field['type'] === 'repeater') {
                                                    $get('content.' . $field['key']);
                                                    foreach ($field['repeater_fields'] ?? [] as $sub) {
                                                        $get('content.' . $field['key'] . '.*.' . $sub['key']);
                                                    }
                                                } elseif ($field['type'] === 'signature') {
                                                    $get('content.' . $field['key'] . '_method');
                                                    $get('content.' . $field['key'] . '_draw');
                                                    $get('content.' . $field['key'] . '_upload');
                                                } else {
                                                    $get('content.' . $field['key']);
                                                }
                                            }
                                        }

                                        return new HtmlString(
                                            view('filament.forms.components.template-preview', [
                                                'html' => $service->renderHtml($template, $data)
                                            ])->render()
                                        );
                                    })
                                    ->columnSpanFull();

                                return $schema;
                            })->columnSpanFull(),
                        ]),

                        Section::make('Lampiran File')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('lampirans')
                                    ->label("Klik atau seret file ke sini")
                                    ->helperText("PDF, DOCX, atau JPG (Maks. 10MB)")
                                    ->multiple()
                                    ->collection('lampiran-surat')
                                    ->preserveFilenames()
                                    ->conversion('thumb')
                                    ->maxSize(10240)
                                    ->panelLayout('integrated')
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 3]),

                    // RIGHT COLUMN
                    Section::make('RINGKASAN PENGIRIMAN')
                        ->schema([
                            TextEntry::make('pengirim_label')
                                ->label('Pengirim')
                                ->state(fn() => Auth::user()->name . (Auth::user()->tipe_entitas === 'ADMIN' ? ' (Admin User)' : '')),

                            TextEntry::make('peran_label')
                                ->label('Peran')
                                ->state(function (Get $get) {
                                    $upjId = $get('user_pegawai_jabatan_id');
                                    if (!$upjId) return '-';
                                    $upj = \App\Models\UserPegawaiJabatan::with(['jabatan', 'unitKerja'])->find($upjId);
                                    if (!$upj) return '-';
                                    return ($upj->jabatan->nama_jabatan ?? 'Unknown') . ' - ' . ($upj->unitKerja->nama_unit ?? 'Unknown');
                                }),

                            TextEntry::make('tanggal_label')
                                ->label('Tanggal')
                                ->state(now()->translatedFormat('d F Y')),

                            TextEntry::make('tipe_label')
                                ->label('Tipe')
                                ->state('TERBITAN'),
                        ])->columnSpan(['lg' => 1]),

                ])->columns(4)->columnSpan(4),

                // Hidden Field
                Hidden::make('status_surat')
                    ->disabled()
                    ->default('DRAFT')
                    ->dehydrated(),

                Hidden::make('unit_pengirim_id')
                    ->default(function () {
                        $pegawai = Auth::user()->pegawai;
                        if (!$pegawai) return Auth::user()->unit_kerja_id;
                        $first = $pegawai->jabatanAktif()->first();
                        return $first ? $first->unit_kerja_id : Auth::user()->unit_kerja_id;
                    })
                    ->dehydrated(),

                Hidden::make('user_pembuat_id')
                    ->default(fn() => Auth::user()->id)
                    ->dehydrated(),

                Hidden::make('tanggal_buat')
                    ->default(now())
                    ->dehydrated(),

                Hidden::make('tanggal_kirim')
                    ->default(null)
                    ->dehydrated(),
            ]);
    }
}
