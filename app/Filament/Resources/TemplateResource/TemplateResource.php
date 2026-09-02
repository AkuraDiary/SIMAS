<?php

namespace App\Filament\Resources\TemplateResource;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use App\Filament\Resources\TemplateResource\Pages;
use App\Models\Template;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    public static function canAccess(): bool
    {
        return Auth::user()?->tipe_entitas === 'ADMIN';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Template Surat';
    protected static ?string $modelLabel = 'Template Surat';
    protected static ?string $pluralModelLabel = 'Template Surat';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Template')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextInput::make('nama_template')
                            ->label('Nama Template')
                            ->placeholder('Contoh: Nota Dinas Standar 2024')
                            ->required(),
                        Select::make('kategori_id')
                            ->label('Kategori')
                            ->relationship('kategori', 'nama_kategori')
                            ->required()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('nama_kategori')
                                    ->required(),
                                Textarea::make('deskripsi')
                            ]),
                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->placeholder('Jelaskan penggunaan template ini...')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Template Aktif')
                            ->default(false)
                            ->helperText('Jika nonaktif, template akan berstatus DRAFT dan hanya admin yang bisa melihatnya.')
                            ->columnSpanFull(),

                    ])->columns(2),

                Section::make('Pengaturan Visibilitas')
                    ->icon('heroicon-o-eye')
                    ->schema([
                        Select::make('aksesibilitas')
                            ->label('Aksesibilitas Target')
                            ->options([
                                'PUBLIK' => 'Publik',
                                'MAHASISWA' => 'Mahasiswa',
                                'INTERNAL' => 'Internal (Pegawai/Unit)',
                            ])
                            ->default('PUBLIK')
                            ->required()
                            ->reactive(),
                        Radio::make('visibility_type')
                            ->dehydrated(false)
                            ->label('Visibilitas Penggunaan')
                            ->options([
                                'GLOBAL' => 'Global (Tersedia untuk semua unit kerja)',
                                'SPECIFIC' => 'Unit Spesifik (Hanya unit tertentu yang dapat menggunakan)',
                            ])
                            ->default('GLOBAL')
                            ->formatStateUsing(function (?Template $record) {
                                if (! $record) return 'GLOBAL';
                                return $record->unitAkses()->exists() ? 'SPECIFIC' : 'GLOBAL';
                            })

                            ->visible(fn(Get $get) => $get('aksesibilitas') === 'INTERNAL')
                            ->required(fn(Get $get) => $get('aksesibilitas') === 'INTERNAL')
                            ->reactive(),
                        Select::make('unitAkses')
                            ->label('Pilih Unit Kerja')
                            ->multiple()
                            ->relationship('unitAkses', 'nama_unit')
                            ->preload()
                            ->visible(fn(Get $get) => $get('visibility_type') === 'SPECIFIC')
                            ->required(fn(Get $get) => $get('visibility_type') === 'SPECIFIC'),
                    ]),

                Section::make('Mode Template')
                    ->icon('heroicon-o-document-duplicate')
                    ->schema([
                        Select::make('render_engine')
                            ->label('Metode Pembuatan')
                            ->options([
                                'HTML' => 'Buat dari Awal (Rich Text)',
                                'DOCX' => 'Upload Dokumen (Word)',
                            ])
                            ->default('HTML')
                            ->reactive()
                            ->required(),

                        Select::make('tipe_surat')
                            ->options([
                                'INTERNAL' => 'Internal',
                                'PENGAJUAN' => 'Pengajuan',
                                'TERBITAN' => 'Terbitan',
                                'EKSTERNAL' => 'Eksternal',
                            ])
                            ->default('INTERNAL')
                            ->required(),
                    ])->columns(2)
                    ->columnSpanFull(),

                Section::make('Placeholders (Variabel Isian)')
                    ->columnSpanFull()
                    ->description('Definisikan placeholder yang akan digunakan di dalam template. Pengguna akan diminta mengisi nilai ini saat membuat surat.')
                    ->schema([
                        // [NEW] Helper Component
                        TextEntry::make('reserved_keywords_helper')
                            ->label('Daftar Reserved Keywords (Dihasilkan Otomatis)')
                            ->state(new \Illuminate\Support\HtmlString('
                                <div style="background-color: #f3f4f6; padding: 10px; border-radius: 8px; font-size: 0.9em; margin-bottom: 15px;">
                                    <strong>Keyword di bawah ini tidak akan meminta input dari user:</strong><br>
                                    <ul style="list-style-type: disc; margin-left: 20px;">
                                        <li><code>{{ nomor_surat }}</code> : Nomor surat (di-generate di akhir)</li>
                                        <li><code>{{ tanggal_surat }}</code> : Tanggal dikirimnya surat</li>
                                        <li><code>{{ tanggal_terbit }}</code> : Sama dengan tanggal surat</li>
                                        <li><code>{{ qr_code }}</code> : QR Code pelacakan</li>
                                        <li><code>{{ ttd_approver_... }}</code> : Tanda tangan untuk approver/penandatangan legal</li>
                                    </ul>
                                </div>
                            '))
                            ->columnSpanFull(),

                        Repeater::make('field_variables')
                            ->label('Daftar Placeholder')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('key')
                                            ->label('Kode Placeholder')
                                            ->placeholder('contoh: nama')
                                            ->required()
                                            ->alphaDash(),
                                        TextInput::make('label')
                                            ->label('Label Input (Untuk User)')
                                            ->placeholder('contoh: Nama Lengkap')
                                            ->required(),
                                        Select::make('type')
                                            ->label('Tipe Input')
                                            ->options([
                                                'text' => 'Teks Pendek',
                                                'long_text' => 'Teks Panjang',
                                                'number' => 'Angka',
                                                'date' => 'Tanggal',
                                                'repeater' => 'Daftar Berulang (Tabel/List)',
                                                'signature' => 'Tanda Tangan',
                                            ])
                                            ->default('text')
                                            ->required()
                                            ->live(),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        Select::make('signature_type')
                                            ->label('Peran Tanda Tangan')
                                            ->options([
                                                'primary' => 'Utama (Primary)',
                                                'secondary' => 'Mengetahui (Secondary/Tertiary)',
                                            ])
                                            ->default('primary')
                                            ->required(fn(Get $get) => $get('type') === 'signature'),
                                        Toggle::make('is_optional_signature')
                                            ->label('Sifat Opsional (Bisa dikosongkan)')
                                            ->default(false)
                                            ->inline(false),
                                    ])
                                    ->visible(fn(Get $get) => $get('type') === 'signature')
                                    ->columnSpanFull(),
                                Repeater::make('repeater_fields')
                                    ->label('Sub-placeholder untuk Daftar Berulang')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('key')
                                                    ->label('Sub-kode')
                                                    ->placeholder('contoh: nama')
                                                    ->required()
                                                    ->alphaDash(),
                                                TextInput::make('label')
                                                    ->label('Sub-label')
                                                    ->placeholder('contoh: Nama')
                                                    ->required(),
                                            ])
                                    ])
                                    ->visible(fn(Get $get) => $get('type') === 'repeater')
                                    ->columnSpanFull()
                                    ->grid(2)
                            ])
                            ->collapsible()
                            ->itemLabel(function ($state) {
                                if (!is_array($state)) return null;

                                $label = $state['label'] ?? null;
                                if (!is_string($label) && !is_numeric($label)) {
                                    $label = null;
                                }

                                $key = $state['key'] ?? null;
                                if (!is_string($key) && !is_numeric($key)) {
                                    $key = null;
                                }

                                return $label ? ($label . ' ({{ ' . $key . ' }})') : null;
                            })
                            ->afterStateHydrated(function ($component, $state) {
                                $service = app(\App\Services\FormSchemaService::class);
                                $component->state($service->formatHydratedVariables($state));
                            })
                            ->hintAction(
                                Action::make('syncToEditor')
                                    ->label('Sync ke Editor')
                                    ->icon('heroicon-m-arrow-right-on-rectangle')
                                    ->visible(fn(Get $get) => $get('render_engine') === 'HTML')
                                    ->action(function (Set $set, Get $get) {
                                        $currentVars = $get('field_variables');
                                        if (empty($currentVars)) {
                                            \Filament\Notifications\Notification::make()->title('Daftar placeholder kosong!')->warning()->send();
                                            return;
                                        }

                                        $html = $get('content_html') ?? '';

                                        $service = app(\App\Services\PlaceholderService::class);
                                        $result = $service->syncVariablesToHtml($html, $currentVars);

                                        if ($result['addedCount'] > 0) {
                                            $set('content_html', $result['html']);
                                            \Filament\Notifications\Notification::make()->title($result['addedCount'] . ' Placeholder/Loop ditambahkan ke akhir editor!')->success()->send();
                                        } else {
                                            \Filament\Notifications\Notification::make()->title('Semua placeholder sudah ada di editor.')->info()->send();
                                        }
                                    })
                            )
                            ->columnSpanFull(),
                    ]),



                Section::make('Isi Template')
                    ->visible(fn(Get $get) => $get('render_engine') === 'HTML')
                    ->schema([
                        TinyEditor::make('content_html')
                            ->profile('full')
                            ->label('')
                            ->placeholder('Mulai mengetik template Anda di sini...')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('template-attachments')
                            ->hintAction(
                                Action::make('scanHtmlPlaceholders')
                                    ->label('Scan Placeholders')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(function (Set $set, Get $get) {
                                        $state = $get('content_html');
                                        if (!$state) return;

                                        $service = app(\App\Services\FormSchemaService::class);
                                        $fields = $service->extractPlaceholders($state);

                                        $current = $get('field_variables') ?? [];
                                        $current = $service->syncExtractedToVariables($fields, $current);

                                        $set('field_variables', $current);
                                        \Filament\Notifications\Notification::make()->title('Placeholders disinkronkan!')->success()->send();
                                    })
                            )
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                Section::make('Upload Dokumen (Word)')
                    ->visible(fn(Get $get) => $get('render_engine') === 'DOCX')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('template_file')
                            ->label('File Template Dokumen')
                            ->collection('template_file')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'])
                            ->maxSize(10240)
                            ->columnSpanFull()
                            ->hintActions([
                                Action::make('scanPlaceholders')
                                    ->label('Scan Placeholders & Preview')
                                    ->icon('heroicon-m-magnifying-glass')
                                    ->action(function (\Filament\Forms\Components\SpatieMediaLibraryFileUpload $component, Set $set, Get $get) {
                                        $files = $component->getState();
                                        if (empty($files)) {
                                            \Filament\Notifications\Notification::make()->title('Upload file DOCX terlebih dahulu')->warning()->send();
                                            return;
                                        }

                                        $file = is_array($files) ? array_values($files)[0] : $files;
                                        $path = null;

                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                            $path = $file->getRealPath();
                                        } elseif (is_string($file)) {
                                            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findByUuid($file);
                                            if ($media) {
                                                $path = $media->getPath();
                                            }
                                        }

                                        if (! $path || ! file_exists($path)) {
                                            \Filament\Notifications\Notification::make()->title('File DOCX tidak ditemukan. Pastikan file sudah selesai di-upload.')->danger()->send();
                                            return;
                                        }

                                        try {
                                            $docxService = app(\App\Services\DocxTemplateService::class);
                                            $placeholderService = app(\App\Services\FormSchemaService::class);

                                            $html = $docxService->convertToHtml($path);
                                            $fields = $placeholderService->extractPlaceholders($html);

                                            $set('content_html', $html);

                                            $currentFields = $get('field_variables') ?? [];
                                            $currentFields = $placeholderService->syncExtractedToVariables($fields, $currentFields);

                                            $set('field_variables', $currentFields);

                                            \Filament\Notifications\Notification::make()->title('Placeholders berhasil dipindai & preview dibuat!')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal memproses file DOCX: ' . $e->getMessage())->danger()->send();
                                        }
                                    }),
                                Action::make('convertToHtml')
                                    ->label('Convert to HTML')
                                    ->icon('heroicon-m-exclamation-triangle')
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->modalHeading('Peringatan Konversi Format')
                                    ->modalDescription('Mengonversi file DOCX ke HTML (Rich Text Editor) memungkinkan Anda untuk mengeditnya secara langsung di browser, namun berisiko merusak layout asli (seperti margin, gambar latar, dan penomoran halaman). Apakah Anda yakin ingin melanjutkan?')
                                    ->action(function (\Filament\Forms\Components\SpatieMediaLibraryFileUpload $component, Set $set, Get $get) {
                                        $files = $component->getState();
                                        if (empty($files)) {
                                            \Filament\Notifications\Notification::make()->title('Upload file DOCX terlebih dahulu')->warning()->send();
                                            return;
                                        }

                                        $file = is_array($files) ? array_values($files)[0] : $files;
                                        $path = null;

                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                            $path = $file->getRealPath();
                                        } elseif (is_string($file)) {
                                            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findByUuid($file);
                                            if ($media) {
                                                $path = $media->getPath();
                                            }
                                        }

                                        if (! $path || ! file_exists($path)) {
                                            \Filament\Notifications\Notification::make()->title('File DOCX tidak ditemukan. Pastikan file sudah selesai di-upload.')->danger()->send();
                                            return;
                                        }

                                        try {
                                            $docxService = app(\App\Services\DocxTemplateService::class);
                                            $formSchemaService = app(\App\Services\FormSchemaService::class);

                                            $html = $docxService->convertToHtml($path);
                                            $fields = $formSchemaService->extractPlaceholders($html);

                                            $set('content_html', $html);
                                            $set('render_engine', 'HTML');

                                            $currentFields = $get('field_variables') ?? [];
                                            $currentFields = $formSchemaService->syncExtractedToVariables($fields, $currentFields);
                                            $set('field_variables', $currentFields);

                                            \Filament\Notifications\Notification::make()->title('Berhasil dikonversi ke HTML. Mode diubah menjadi Buat dari Awal.')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal memproses file DOCX: ' . $e->getMessage())->danger()->send();
                                        }
                                    })
                            ]),

                        TextEntry::make('docx_preview')
                            ->label('Preview Dokumen')
                            ->state(function (Get $get) {
                                $content = $get('content_html');
                                if (! $content) {
                                    $content = '<p style="color: #666; text-align: center;">Klik "Scan Placeholders & Preview" di bagian Upload File untuk melihat preview.</p>';
                                } else {
                                    $content = app(\App\Services\DocxTemplateService::class)->highlightPlaceholders($content);
                                }
                                return new HtmlString('<div style="border:1px solid #ccc; padding: 2rem; background: #fff; color: #000; max-height: 500px; overflow-y: auto;">' . $content . '</div>');
                            })
                            ->html() // Explicitly tells Filament to render the HtmlString wrapper as raw HTML
                            ->columnSpanFull()
                        // Placeholder::make('docx_preview')
                        //     ->label('Preview Dokumen')
                        //     ->content(fn(Get $get) => new HtmlString('<div style="border:1px solid #ccc; padding: 2rem; background: #fff; color: #000; max-height: 500px; overflow-y: auto;">' . ($get('preview_html') ?: '<p style="color: #666; text-align: center;">Klik "Scan Placeholders & Preview" di bagian Upload File untuk melihat preview.</p>') . '</div>'))
                        //     ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                // [NEW] Jalur Persetujuan Section
                Section::make('Jalur Persetujuan & Penandatangan')
                    ->icon('heroicon-o-users')
                    ->description('Tentukan urutan jabatan yang harus menyetujui surat ini. Jika ada penandatangan, tentukan mapping ttd-nya.')
                    ->schema([
                        Repeater::make('approval_path')
                            ->label('Jalur Approval (Urutkan dari awal hingga akhir)')
                            ->addActionLabel('Tambah Jabatan Approver')
                            ->reorderable(true)
                            ->schema([
                                Select::make('jabatan_id')
                                    ->label('Jabatan')
                                    ->options(\App\Models\Jabatan::pluck('nama_jabatan', 'id'))
                                    ->required()
                                    ->searchable(),
                                Toggle::make('is_signer')
                                    ->label('Bertindak sbg Penandatangan?')
                                    ->default(false)
                                    ->reactive(),
                                TextInput::make('placeholder_key')
                                    ->label('Tujuan Placeholder TTD')
                                    ->placeholder('Contoh: ttd_approver_rektor')
                                    ->visible(fn(Get $get) => $get('is_signer') === true)
                                    ->required(fn(Get $get) => $get('is_signer') === true)
                                    ->helperText('Samakan dengan kode {{ ttd_approver_... }} di template dokumen Anda.'),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([

                View::make('filament.tables.columns.template-card')


            ])
            ->filters([
                SelectFilter::make('kategori_id')
                    ->relationship('kategori', 'nama_kategori')
                    ->label('Kategori'),
                \Filament\Tables\Filters\Filter::make('visibility_type')
                    ->form([
                        Select::make('visibilitas')
                            ->options([
                                'GLOBAL' => 'Global',
                                'SPECIFIC' => 'Unit Spesifik',
                            ])
                            ->label('Visibilitas Penggunaan')
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        if (empty($data['visibilitas'])) return $query;
                        if ($data['visibilitas'] === 'GLOBAL') {
                            return $query->doesntHave('unitAkses');
                        }
                        if ($data['visibilitas'] === 'SPECIFIC') {
                            return $query->has('unitAkses');
                        }
                        return $query;
                    }),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Draft',
                    ])
                    ->label('Status'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->recordAction(null)
            ->recordUrl(null)
            ->recordActions([
                Action::make('preview')
                    ->visible()
                    ->extraAttributes([
                        'class' => 'hidden', // Hides the default button from the UI
                    ])
                    ->icon(Heroicon::Eye)
                    ->modalHeading(fn($record) => 'Preview: ' . $record->nama_template)
                    ->modalContent(function ($record) {
                        $html = '';
                        if ($record->render_engine === 'HTML') {
                            $html = $record->content_html;
                        } else {
                            $media = $record->getFirstMedia('template_file');
                            if ($media && file_exists($media->getPath())) {
                                try {
                                    $service = app(\App\Services\DocxTemplateService::class);
                                    $rawHtml = $service->convertToHtml($media->getPath());
                                    $html = $service->highlightPlaceholders($rawHtml);
                                } catch (\Exception $e) {
                                    $html = '<p style="color: red; text-align: center;">Gagal merender DOCX: ' . $e->getMessage() . '</p>';
                                }
                            } else {
                                $html = '<p style="color: #666; text-align: center;">Belum ada file template DOCX yang diupload.</p>';
                            }
                        }
                        return new \Illuminate\Support\HtmlString(
                            '<div style="padding: 2rem; background: #fff; color: #000; max-height: 500px; overflow-y: auto;">' .
                                ($html ?: '<p style="color: #666; text-align: center;">Template kosong.</p>') .
                                '</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                // EditAction::make("Edit")->icon(Heroicon::Pencil),
            ])
            ->recordActionsAlignment('end');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplates::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }
}
