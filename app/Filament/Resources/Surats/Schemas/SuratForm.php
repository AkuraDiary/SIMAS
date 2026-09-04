<?php

namespace App\Filament\Resources\Surats\Schemas;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use App\Models\Surat;
use App\Models\Template;
use App\Models\UnitKerja;
use App\Services\FormSchemaService;
use App\Services\PlaceholderService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Carbon\Carbon;
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
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

use function Illuminate\Support\now;

class SuratForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(4)->schema([


                    // LEFT COLUMN
                    Group::make()->schema([
                        // TAMBAHKAN SECTION INI: Warning Box Revisi
                        Section::make('Catatan Revisi dari Pemeriksa')
                            ->schema([
                                TextEntry::make('revisi_note')
                                    ->hiddenLabel()
                                    ->state(
                                        fn(?Surat $record) =>

                                        nl2br(e($record?->riwayats()->where('status', 'REVISI')->latest()->first()?->catatan ?? 'Silakan perbaiki dokumen ini.'))
                                    ),
                            ])
                            ->icon('heroicon-o-exclamation-triangle')
                            ->extraAttributes(['class' => 'bg-red-50 dark:bg-red-900/30 dark:border-red-800 shadow-sm'])
                            ->visible(fn(?\App\Models\Surat $record) => $record && $record->status_surat === 'REVISI'),

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
                                ->dehydrated(false)
                                ->afterStateHydrated(function (\Filament\Forms\Components\Radio $component, ?\Illuminate\Database\Eloquent\Model $record) {
                                    if ($record) {
                                        if ($record->template_id) {
                                            $component->state('template');
                                        } elseif (isset($record->content['isi_surat'])) {
                                            $component->state('scratch');
                                        }
                                    }
                                })
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    // Simpan isi_surat jika ada, dan hapus sisa variabel template
                                    $currentContent = $get('content') ?? [];
                                    $isiSurat = $currentContent['isi_surat'] ?? null;

                                    if ($isiSurat) {
                                        $set('content', ['isi_surat' => $isiSurat]);
                                    } else {
                                        $set('content', []);
                                    }
                                    // $set('template_id', null);
                                    // if ($get('metode_pembuatan') === 'scratch') {
                                    //     $set('content', []);
                                    // }
                                }),

                            Select::make('template_id')
                                ->label('Pilih Template Surat')
                                ->options(function (Get $get) {
                                    $unitId = null;
                                    $upjId = $get('user_pegawai_jabatan_id');

                                    if ($upjId) {
                                        $upj = \App\Models\UserPegawaiJabatan::find($upjId);
                                        $unitId = $upj?->unit_kerja_id;
                                    } else {
                                        $activeJabatan = \Illuminate\Support\Facades\Auth::user()?->getActiveJabatan();
                                        $unitId = $activeJabatan?->unit_kerja_id ?? \Illuminate\Support\Facades\Auth::user()?->unit_kerja_id;
                                    }

                                    $query = \App\Models\Template::where('aksesibilitas', 'INTERNAL')
                                        ->where('is_active', true);

                                    if ($unitId) {
                                        $query->where(function ($q) use ($unitId) {
                                            $q->doesntHave('unitAkses')
                                                ->orWhereHas('unitAkses', function ($sub) use ($unitId) {
                                                    $sub->where('unit_kerja_id', $unitId);
                                                });
                                        });
                                    } else {
                                        $query->doesntHave('unitAkses');
                                    }

                                    return $query->pluck('nama_template', 'id');
                                })
                                ->required(fn(Get $get) => $get('metode_pembuatan') === 'template')
                                ->visible(fn(Get $get) => $get('metode_pembuatan') === 'template')
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state) {
                                        $template = Template::find($state);
                                        $content = [];
                                        foreach ($template->field_variables ?? [] as $field) {
                                            $key = $field['key'] ?? null;
                                            if ($key) {
                                                if (($field['type'] ?? '') === 'signature') {
                                                    $content[$key . '_method'] = 'draw';
                                                    $content[$key . '_draw'] = null;
                                                    $content[$key . '_upload'] = null;
                                                } else {
                                                    $content[$key] = null;
                                                }
                                            }
                                        }
                                        $set('content', $content);
                                    } else {
                                        $set('content', []);
                                    }
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
                                    $activeJabatan = Auth::user()?->getActiveJabatan();
                                    if ($activeJabatan) {
                                        return $activeJabatan->id;
                                    }
                                    $pegawai = Auth::user()?->pegawai;
                                    return $pegawai?->jabatanAktif()->first()?->id;
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

                            Grid::make(2)->schema([
                                Select::make('tipe_surat')
                                    ->label('Jenis Surat')
                                    ->options([
                                        'INTERNAL' => 'Internal',
                                        'PENGAJUAN' => 'Pengajuan',
                                        'TERBITAN' => 'Terbitan (Surat Resmi)',
                                        'EKSTERNAL' => 'Eksternal',
                                    ])
                                    ->default('INTERNAL')
                                    ->dehydrated()
                                    ->live(),

                                Select::make('terbitan_for_surat_id')
                                    ->label('Merujuk ke Pengajuan')
                                    ->options(function (?\App\Models\Surat $record) {
                                        // =========================================================================
                                        // [PENGATURAN STATUS PENGAJUAN RUJUKAN]
                                        // Ubah atau tambahkan status di sini jika diperlukan (misal: ['SELESAI', 'DIPROSES']).
                                        // =========================================================================
                                        $allowedStatuses = ['SELESAI'];

                                        $activeUnitId = \Illuminate\Support\Facades\Auth::user()?->getActiveJabatan()?->unit_kerja_id
                                            ?? \Illuminate\Support\Facades\Auth::user()?->unit_kerja_id;

                                        $requestedId = $record?->terbitan_for_surat_id
                                            ?? request()->query('terbitan_for_surat_id');

                                        return \App\Models\Surat::query()
                                            ->where('tipe_surat', 'PENGAJUAN')
                                            // 1. Filter Status Pengajuan
                                            ->where(function ($sq) use ($allowedStatuses, $requestedId) {
                                                $sq->whereIn('status_surat', $allowedStatuses);
                                                if ($requestedId) {
                                                    $sq->orWhere('id', $requestedId);
                                                }
                                            })
                                            // 2. Filter Belum Pernah Dibuatkan Terbitan Resmi (Hindari Terbitan Ganda)
                                            ->where(function ($q) use ($record, $requestedId) {
                                                $q->whereDoesntHave('terbitans', function ($tq) use ($record) {
                                                    $tq->whereNotIn('status_surat', ['DIBATALKAN', 'DITOLAK']);
                                                    if ($record?->id) {
                                                        $tq->where('id', '!=', $record->id);
                                                    }
                                                });
                                                if ($requestedId) {
                                                    $q->orWhere('id', $requestedId);
                                                }
                                            })
                                            // 3. Filter Berdasarkan Unit Kerja Aktif Penerima
                                            ->when($activeUnitId, function ($q) use ($activeUnitId, $requestedId) {
                                                $q->where(function ($uq) use ($activeUnitId, $requestedId) {
                                                    $uq->untukUnit($activeUnitId);
                                                    if ($requestedId) {
                                                        $uq->orWhere('id', $requestedId);
                                                    }
                                                });
                                            })
                                            ->with([
                                                'userPegawaiJabatan.pegawai',
                                                'userPegawaiJabatan.jabatan',
                                                'userPegawaiJabatan.unitKerja',
                                                'unitPengirim',
                                                'pembuat.pegawai',
                                                'pembuat.mahasiswa',
                                            ])
                                            ->latest()
                                            ->limit(50) // Batasi 50 pengajuan terbaru agar dropdown cepat dan rapi
                                            ->get()
                                            ->mapWithKeys(function (\App\Models\Surat $surat) {
                                                $pengirim = $surat->getIdentitasPengirim();
                                                $nomor = $surat->nomor_surat ? "[{$surat->nomor_surat}] " : '';
                                                return [$surat->id => "{$nomor}{$surat->perihal} (Pengirim: {$pengirim})"];
                                            })
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->nullable()
                                    ->helperText('Pilih surat pengajuan yang menjadi rujukan (hanya pengajuan selesai dan belum diterbitkan).')
                                    ->visible(fn(Get $get) => $get('tipe_surat') === 'TERBITAN'),

                                TextInput::make('pengirim_eksternal')
                                    ->label('Asal Pengirim Eksternal')
                                    ->placeholder('Contoh: PT Telkom Indonesia / Kemendikbud')
                                    ->dehydrated()
                                    ->required(fn(Get $get) => $get('tipe_surat') === 'EKSTERNAL')
                                    ->visible(fn(Get $get) => $get('tipe_surat') === 'EKSTERNAL'),

                                TextInput::make('nomor_surat_eksternal')
                                    ->label('Nomor Surat Asal (Eksternal)')
                                    ->placeholder('Contoh: 012/DIR-PLN/VIII/2026')
                                    ->helperText('Nomor surat yang tertera pada dokumen dari pihak luar.')
                                    ->dehydrated()
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

                            // Penomoran Surat (di antara metadata dan konten surat)
                            Section::make('Penomoran Surat')
                                ->description('Pilih apakah nomor surat digenerate otomatis saat dikirim atau ditetapkan sekarang (termasuk opsi backdate & sisipan).')
                                ->icon('heroicon-o-hashtag')
                                ->schema([
                                    Radio::make('mode_penomoran')
                                        ->label('Pilihan Penomoran')
                                        ->options([
                                            'auto' => 'Generate Otomatis saat Surat Dikirim',
                                            'manual' => 'Tetapkan Nomor Sekarang / Backdate',
                                        ])
                                        ->default('auto')
                                        ->inline()
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function ($component, ?\Illuminate\Database\Eloquent\Model $record) {
                                            if ($record && !empty($record->nomor_surat)) {
                                                $component->state('manual');
                                            }
                                        })
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            if ($get('mode_penomoran') === 'auto') {
                                                $set('nomor_surat', null);
                                            } else {
                                                static::updateFormNomorPreview($set, $get);
                                            }
                                        }),

                                    Placeholder::make('info_auto_nomor')
                                        ->hiddenLabel()
                                        ->content(function (Get $get) {
                                            $unitId = Auth::user()?->unit_kerja_id;
                                            $tipeSurat = $get('tipe_surat') ?? 'INTERNAL';
                                            $format = app(\App\Services\NomorSuratService::class)->resolveFormat($unitId, $tipeSurat);
                                            $formatName = $format ? "[{$format->nama_format}] {$format->format_penomoran}" : '-';

                                            $customTags = array_merge(
                                                $get('content.nomor_surat_tags') ?? [],
                                                $get('custom_nomor_tags') ?? [],
                                                $get('content') ?? []
                                            );
                                            $nextEstimate = $format ? app(\App\Services\NomorSuratService::class)->previewNomor($format, now(), null, Auth::user()?->unitKerja, $tipeSurat, $customTags) : '-';

                                            return new \Illuminate\Support\HtmlString("
                                                <div class='flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-900 dark:text-blue-200'>
                                                    <svg class='w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z' /></svg>
                                                    <div>
                                                        <span>Nomor surat akan dibuat otomatis saat dikirim mengikuti pola: <strong>{$formatName}</strong>.</span>
                                                        <br><span class='text-xs opacity-80 font-mono'>Estimasi nomor berikutnya: {$nextEstimate}</span>
                                                    </div>
                                                </div>
                                            ");
                                        })
                                        ->visible(fn(Get $get) => ($get('mode_penomoran') ?? 'auto') === 'auto')
                                        ->columnSpanFull(),

                                    Grid::make(3)->schema([
                                        DatePicker::make('tanggal_surat_input')
                                            ->label('Tanggal Surat (Bisa Backdate)')
                                            ->default(now())
                                            ->live()
                                            ->dehydrated(false)
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                static::updateFormNomorPreview($set, $get);
                                            }),

                                        Select::make('format_id_input')
                                            ->label('Pola Format Penomoran')
                                            ->options(function (Get $get) {
                                                $unitId = Auth::user()?->unit_kerja_id;
                                                return app(\App\Services\NomorSuratService::class)->getAvailableFormats($unitId, $get('tipe_surat'));
                                            })
                                            ->default(function (Get $get) {
                                                $unitId = Auth::user()?->unit_kerja_id;
                                                return app(\App\Services\NomorSuratService::class)->resolveFormat($unitId, $get('tipe_surat'))?->id;
                                            })
                                            ->live()
                                            ->dehydrated(false)
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                static::updateFormNomorPreview($set, $get);
                                            }),

                                        Toggle::make('is_manual_sisipan')
                                            ->label('Kustomisasi / Sisipan Manual')
                                            ->default(false)
                                            ->live()
                                            ->dehydrated(false)
                                            ->helperText('Aktifkan jika ingin menyisipkan nomor manual (cth: 045.A) atau mengedit format.')
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                static::updateFormNomorPreview($set, $get);
                                            }),
                                    ])->visible(fn(Get $get) => $get('mode_penomoran') === 'manual'),

                                    Grid::make(2)->schema([
                                        TextInput::make('nomor_sisipan_input')
                                            ->label('Nomor / Sisipan')
                                            ->placeholder('Contoh: 045.A')
                                            ->helperText('Menggantikan token {NOMOR} pada template.')
                                            ->live()
                                            ->dehydrated(false)
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                static::updateFormNomorPreview($set, $get);
                                            })
                                            ->visible(fn(Get $get) => (bool) $get('is_manual_sisipan')),

                                        Checkbox::make('increment_counter_input')
                                            ->label('Naikkan counter nomor urut?')
                                            ->default(false)
                                            ->dehydrated(false)
                                            ->helperText('Biarkan tidak dicentang agar nomor sisipan lampau tidak memajukan counter berjalan.')
                                            ->visible(fn(Get $get) => (bool) $get('is_manual_sisipan')),
                                    ])->visible(fn(Get $get) => $get('mode_penomoran') === 'manual'),

                                    // Dynamic Custom Tags Inputs
                                    Group::make()
                                        ->schema(function (Get $get) {
                                            $unitId = Auth::user()?->unit_kerja_id;
                                            $tipeSurat = $get('tipe_surat') ?? 'INTERNAL';
                                            $formatId = $get('format_id_input');

                                            $format = $formatId
                                                ? \App\Models\FormatNomorSurat::find($formatId)
                                                : app(\App\Services\NomorSuratService::class)->resolveFormat($unitId, $tipeSurat);

                                            if (!$format) return [];

                                            $customTags = app(\App\Services\NomorSuratService::class)->extractCustomTags($format->format_penomoran);
                                            if (empty($customTags)) return [];

                                            $inputs = [];
                                            foreach ($customTags as $tag) {
                                                $cleanLabel = ucwords(str_replace('_', ' ', strtolower($tag)));
                                                $inputs[] = TextInput::make("custom_nomor_tags.{$tag}")
                                                    ->label("Atribut Format: {$cleanLabel}")
                                                    ->placeholder("Nilai untuk {{$tag}}")
                                                    ->helperText("Menggantikan token {{$tag}} pada format penomoran.")
                                                    ->live(onBlur: true)
                                                    ->afterStateHydrated(function ($component, ?\Illuminate\Database\Eloquent\Model $record) use ($tag) {
                                                        if ($record && isset($record->content['nomor_surat_tags'][$tag])) {
                                                            $component->state($record->content['nomor_surat_tags'][$tag]);
                                                        }
                                                    })
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        static::updateFormNomorPreview($set, $get);
                                                    });
                                            }

                                            return [
                                                Grid::make(count($customTags) > 1 ? 2 : 1)
                                                    ->schema($inputs)
                                                    ->columnSpanFull(),
                                            ];
                                        })
                                        ->columnSpanFull(),

                                    TextInput::make('nomor_surat')
                                        ->label('Nomor Surat Final')
                                        ->placeholder('Nomor surat akan terisi otomatis...')
                                        ->helperText(fn(Get $get) => $get('is_manual_sisipan')
                                            ? 'Anda dapat mengedit bebas seluruh teks nomor surat final di atas.'
                                            : 'Nomor di-generate otomatis berdasarkan template.')
                                        ->disabled(fn(Get $get) => ! $get('is_manual_sisipan'))
                                        ->dehydrated(true)
                                        ->required(fn(Get $get) => $get('mode_penomoran') === 'manual')
                                        ->visible(fn(Get $get) => $get('mode_penomoran') === 'manual')
                                        ->columnSpanFull(),

                                    Textarea::make('alasan_backdate_input')
                                        ->label('Alasan Backdate')
                                        ->placeholder('Contoh: Surat fisik telah diputuskan tanggal lampau dan baru diadministrasikan sekarang.')
                                        ->helperText('Wajib diisi karena tanggal surat merupakan tanggal lampau.')
                                        ->dehydrated(false)
                                        ->required(fn(Get $get) => $get('mode_penomoran') === 'manual' && app(\App\Services\NomorSuratService::class)->isDateBackdate($get('tanggal_surat_input')))
                                        ->visible(fn(Get $get) => $get('mode_penomoran') === 'manual' && app(\App\Services\NomorSuratService::class)->isDateBackdate($get('tanggal_surat_input')))
                                        ->columnSpanFull(),
                                ])
                                ->collapsible(),

                            // Dynamic Content / Rich Editor
                            Group::make()->schema(function (Get $get) {
                                if ($get('metode_pembuatan') === 'scratch') {
                                    return [
                                        TinyEditor::make('content.isi_surat')
                                            ->profile('full')
                                            ->label('Isi Surat')
                                            ->setCustomConfigs([
                                                'font_family_formats' => 'Arial=arial,helvetica,sans-serif; Times New Roman=times new roman,times; Verdana=verdana,geneva',
                                            ])
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
                                $fieldVariables = $template ? ($template->field_variables ?? []) : [];

                                if (empty($fieldVariables)) {
                                    return [
                                        TextEntry::make('info')
                                            ->label('')
                                            ->state('Template ini tidak memiliki formulir dinamis yang perlu diisi.')
                                    ];
                                }

                                $service = app(FormSchemaService::class);
                                $schema = $service->generateFilamentSchema($fieldVariables);

                                $schema[] = TextEntry::make('preview')
                                    ->label('Pratinjau Surat')
                                    ->state(function (Get $get) use ($template, $service, $fieldVariables) {
                                        $data = $get('content') ?? [];

                                        // Force dependency tracking for all nested content keys & numbering
                                        $get('nomor_surat');
                                        $get('tanggal_surat_input');
                                        $get('custom_nomor_tags');

                                        foreach ($fieldVariables as $field) {
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

                                        $placeholderService = app(\App\Services\PlaceholderService::class);

                                        $previewData = $data;
                                        if (!empty($get('nomor_surat'))) {
                                            $previewData['nomor_surat'] = $get('nomor_surat');
                                        } else {
                                            $unitId = Auth::user()?->unit_kerja_id;
                                            $tipeSurat = $get('tipe_surat') ?? 'INTERNAL';
                                            $fmt = app(\App\Services\NomorSuratService::class)->resolveFormat($unitId, $tipeSurat);
                                            if ($fmt) {
                                                $customTags = array_merge(
                                                    $get('content.nomor_surat_tags') ?? [],
                                                    $get('custom_nomor_tags') ?? [],
                                                    $get('content') ?? []
                                                );
                                                $previewData['nomor_surat'] = app(\App\Services\NomorSuratService::class)->previewNomor(
                                                    $fmt,
                                                    now(),
                                                    null,
                                                    Auth::user()?->unitKerja,
                                                    $tipeSurat,
                                                    $customTags
                                                );
                                            }
                                        }

                                        $customTags = $get('custom_nomor_tags') ?? [];
                                        if (!empty($customTags)) {
                                            $previewData['nomor_surat_tags'] = $customTags;
                                            foreach ($customTags as $k => $v) {
                                                $previewData[$k] = $v;
                                                $previewData[strtolower($k)] = $v;
                                            }
                                        }

                                        if (!empty($get('tanggal_surat_input'))) {
                                            $previewData['tanggal_surat'] = \Carbon\Carbon::parse($get('tanggal_surat_input'))->translatedFormat('d F Y');
                                            $previewData['tanggal_terbit'] = $previewData['tanggal_surat'];
                                        }

                                        return new HtmlString(
                                            view('filament.forms.components.template-preview', [
                                                'html' => $placeholderService->renderHtml($template, $previewData)
                                            ])->render()
                                        );
                                    })
                                    ->columnSpanFull();

                                return $schema;
                            })->columnSpanFull(),

                            // Dynamic Path Builder
                            Section::make('Jalur Persetujuan Khusus')
                                ->description('Atur jalur persetujuan secara manual. Jika dikosongkan, sistem akan menggunakan jalur default dari Template atau sepenuhnya bergantung ke staf.')
                                ->schema([
                                    \Filament\Forms\Components\Repeater::make('approval_path')
                                        ->label('Alur Persetujuan & Tanda Tangan')
                                        ->schema([
                                            \Filament\Forms\Components\Select::make('jabatan_id')
                                                ->label('Jabatan')
                                                ->options(\App\Models\Jabatan::pluck('nama_jabatan', 'id'))
                                                ->required()
                                                ->searchable(),
                                            \Filament\Forms\Components\Toggle::make('is_signer')
                                                ->label('Penandatangan?')
                                                ->default(false)
                                                ->reactive(),
                                            \Filament\Forms\Components\TextInput::make('placeholder_key')
                                                ->label('Placeholder TTD')
                                                ->visible(fn(Get $get) => $get('is_signer') === true),
                                        ])
                                        ->columns(3)
                                ])
                                ->collapsible(),
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
                                    ->panelLayout('grid')
                                    ->imagePreviewHeight('250') // Optional: limits preview size
                                    ->columnSpanFull(),

                            ]),
                    ])->columnSpan(['lg' => 3]),

                    // RIGHT COLUMN
                    Section::make('RINGKASAN PENGIRIMAN')
                        ->schema([
                            TextEntry::make('pengirim_label')
                                ->label('Pengirim')
                                ->state(fn() => Auth::user()->nama_lengkap . (Auth::user()->tipe_entitas === 'ADMIN' ? ' (Admin User)' : '')),

                            TextEntry::make('peran_label')
                                ->label('Peran')
                                ->state(function (Get $get) {
                                    $upjId = $get('user_pegawai_jabatan_id') ?? Auth::user()?->getActiveJabatan()?->id;
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
                                ->state(fn(Get $get) => $get('tipe_surat') ?? 'INTERNAL'),
                        ])->columnSpan(['lg' => 1]),

                ])->columns(4)->columnSpan(4),

                // Hidden Field
                Hidden::make('status_surat')
                    ->disabled()
                    ->default('DRAFT')
                    ->dehydrated(),

                Hidden::make('unit_pengirim_id')
                    ->default(function () {
                        $activeJabatan = Auth::user()?->getActiveJabatan();
                        if ($activeJabatan) {
                            return $activeJabatan->unit_kerja_id;
                        }
                        return Auth::user()?->unit_kerja_id;
                    })
                    ->dehydrated(),

                Hidden::make('user_pembuat_id')
                    ->default(fn() => Auth::user()->id)
                    ->dehydrated(),


                // Hidden::make('tanggal_kirim')
                //     ->default(null)
                //     ->dehydrated(),


            ]);
    }

    public static function updateFormNomorPreview(Set $set, Get $get): void
    {
        $formatId = $get('format_id_input');
        $unitId = Auth::user()?->unit_kerja_id;
        $tipeSurat = $get('tipe_surat') ?? 'INTERNAL';

        $format = $formatId
            ? \App\Models\FormatNomorSurat::find($formatId)
            : app(\App\Services\NomorSuratService::class)->resolveFormat($unitId, $tipeSurat);

        if (!$format) {
            $set('nomor_surat', null);
            return;
        }

        $service = app(\App\Services\NomorSuratService::class);
        $tgl = $get('tanggal_surat_input') ? \Carbon\Carbon::parse($get('tanggal_surat_input')) : now();
        $isManual = (bool) $get('is_manual_sisipan');
        $customPart = $isManual ? $get('nomor_sisipan_input') : null;

        $customTags = array_merge(
            $get('content.nomor_surat_tags') ?? [],
            $get('custom_nomor_tags') ?? [],
            $get('content') ?? []
        );

        $preview = $service->previewNomor(
            $format,
            $tgl,
            $customPart,
            Auth::user()?->unitKerja,
            $tipeSurat,
            $customTags
        );

        $set('nomor_surat', $preview);
    }
}
