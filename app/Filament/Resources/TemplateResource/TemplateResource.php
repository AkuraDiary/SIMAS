<?php

namespace App\Filament\Resources\TemplateResource;

use App\Filament\Resources\TemplateResource\Pages;
use App\Models\Template;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
// use Filament\Forms\Set;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\HtmlString;
use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Layout\View;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Stack;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;
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

                Section::make('Placeholders (Variabel Isian)')
                    ->icon('heroicon-o-variable')
                    ->description('Definisikan placeholder yang akan digunakan di dalam template. Pengguna akan diminta mengisi nilai ini saat membuat surat.')
                    ->schema([
                        KeyValue::make('field_variables')
                            ->label('Daftar Placeholder')
                            ->keyLabel('Kode (contoh: TANGGAL)')
                            ->valueLabel('Deskripsi / Label Input')
                            ->reorderable()
                            ->columnSpanFull(),
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

                        Toggle::make('is_active')
                            ->label('Template Aktif')
                            ->default(false)
                            ->helperText('Jika nonaktif, template akan berstatus DRAFT dan hanya admin yang bisa melihatnya.'),

                        Select::make('tipe_surat')
                            ->options([
                                'INTERNAL' => 'Internal',
                                'PENGAJUAN' => 'Pengajuan',
                                'TERBITAN' => 'Terbitan',
                                'EKSTERNAL' => 'Eksternal',
                            ])
                            ->default('INTERNAL')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Isi Template (HTML)')
                    ->visible(fn(Get $get) => $get('render_engine') === 'HTML')
                    ->schema([
                        TinyEditor::make('content_html')
                            ->profile('full')
                            ->label('')
                            ->placeholder('Mulai mengetik template Anda di sini...')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('template-attachments')
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
                            ->hintAction(
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
                                            $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                                            $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
                                            
                                            $tmpHtmlFile = tempnam(sys_get_temp_dir(), 'html');
                                            $htmlWriter->save($tmpHtmlFile);
                                            $html = file_get_contents($tmpHtmlFile);
                                            unlink($tmpHtmlFile);
                                            
                                            // Extract body
                                            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
                                                $html = $matches[1];
                                            }
                                            
                                            // Parse {{ fields }}
                                            preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $html, $fieldMatches);
                                            $fields = array_unique($fieldMatches[1] ?? []);
                                            
                                            // Highlight
                                            $html = preg_replace('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', '<mark style="background-color: #ffeb3b; font-weight: bold;">{{ $1 }}</mark>', $html);
                                            
                                            // Save to preview instead of switching to HTML mode
                                            $set('preview_html', $html);
                                            
                                            $currentFields = $get('field_variables') ?? [];
                                            foreach ($fields as $field) {
                                                if (!isset($currentFields[$field])) {
                                                    $currentFields[$field] = ucwords(str_replace('_', ' ', $field));
                                                }
                                            }
                                            $set('field_variables', $currentFields);
                                            
                                            \Filament\Notifications\Notification::make()->title('Placeholders berhasil dipindai & preview dibuat!')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()->title('Gagal memproses file DOCX: ' . $e->getMessage())->danger()->send();
                                        }
                                    })
                            ),
                            
                        Hidden::make('preview_html'),
                        
                        Placeholder::make('docx_preview')
                            ->label('Preview Dokumen')
                            ->content(fn(Get $get) => new HtmlString('<div style="border:1px solid #ccc; padding: 2rem; background: #fff; color: #000; max-height: 500px; overflow-y: auto;">' . ($get('preview_html') ?: '<p style="color: #666; text-align: center;">Klik "Scan Placeholders & Preview" di bagian Upload File untuk melihat preview.</p>') . '</div>'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
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
                Stack::make([
                    View::make('filament.tables.columns.template-card'),
                ])
            ])
            ->filters([
                SelectFilter::make('kategori_id')
                    ->relationship('kategori', 'nama_kategori')
                    ->label('Kategori'),
                SelectFilter::make('aksesibilitas')
                    ->options([
                        'PUBLIK' => 'Publik',
                        'MAHASISWA' => 'Mahasiswa',
                        'INTERNAL' => 'Internal',
                    ])
                    ->label('Aksesibilitas'),
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
                        '1' => 'Active',
                        '0' => 'Draft',
                    ])
                    ->label('Status'),
                ])
                ->recordActions([
                    EditAction::make()->hiddenLabel()->tooltip('Edit Template'),
                    DeleteAction::make()->hiddenLabel()->tooltip('Hapus Template'),
                ])
                ->toolbarActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
            ]);
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
