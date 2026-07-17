<?php

namespace App\Filament\Resources\TemplateResource;

use App\Filament\Resources\TemplateResource\Pages;
use App\Models\Template;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Layout\View;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Stack;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Template Surat';
    protected static ?string $modelLabel = 'Template';
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
                        Radio::make('aksesibilitas')
                            ->label('Visibilitas')
                            ->options([
                                'GLOBAL' => 'Global (Tersedia untuk semua unit kerja)',
                                'SPECIFIC' => 'Unit Spesifik (Hanya unit tertentu yang dapat menggunakan)',
                            ])
                            ->default('GLOBAL')
                            ->reactive()
                            ->required(),
                        Select::make('unitAkses')
                            ->label('Pilih Unit Kerja')
                            ->multiple()
                            ->relationship('unitAkses', 'nama_unit')
                            ->preload()
                            ->visible(fn(Get $get) => $get('aksesibilitas') === 'SPECIFIC')
                            ->required(fn(Get $get) => $get('aksesibilitas') === 'SPECIFIC'),
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
                        RichEditor::make('content_html')
                            ->label('')
                            ->placeholder('Mulai mengetik template Anda di sini...')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('template-attachments')
                            ->columnSpanFull(),
                    ]),

                Section::make('Upload Dokumen (Word)')
                    ->visible(fn(Get $get) => $get('render_engine') === 'DOCX')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('template_file')
                            ->label('File Template Dokumen')
                            ->collection('template_file')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'])
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ]),
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
                        'GLOBAL' => 'Global',
                        'SPECIFIC' => 'Unit Spesifik',
                    ])
                    ->label('Visibilitas'),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Draft',
                    ])
                    ->label('Status'),
                // ])
                // ->actions([
                //     EditAction::make()->hiddenLabel()->tooltip('Edit Template'),
                //     DeleteAction::make()->hiddenLabel()->tooltip('Hapus Template'),
                // ])
                // ->bulkActions([
                //     Tables\Actions\BulkActionGroup::make([
                //         Tables\Actions\DeleteBulkAction::make(),
                //     ]),
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
