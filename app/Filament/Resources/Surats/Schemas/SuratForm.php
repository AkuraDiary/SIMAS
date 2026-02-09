<?php

namespace App\Filament\Resources\Surats\Schemas;

use App\Models\UnitKerja;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class SuratForm
{
    public static function configure(Schema $schema): Schema
    {


        return $schema
            ->components([
                Section::make('Tujuan Surat')
                    ->collapsible()
                    ->columnSpanFull()
                    ->description('Dapat diisi sekarang atau nanti sebelum surat dikirim')
                    ->schema([
                        Select::make('unitTujuan')
                            ->helperText('Unit pertama dianggap sebagai tujuan utama, sisanya sebagai tembusan')
                            ->label('Tujuan')
                            ->multiple()
                            ->relationship(
                                'unitTujuan',
                                'nama_unit',
                                modifyQueryUsing: fn($query) => $query->where('unit_kerjas.id', '<>', Auth::user()->unit_kerja_id)
                            )
                            ->searchable()
                            ->preload()

                    ]),


                Section::make('Isi Surat')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nomor_agenda')
                            ->label('Nomor Agenda')
                            ->required(),

                        TextInput::make('nomor_surat')
                            ->label('Nomor Surat')
                            ->required(),

                        TextInput::make('perihal')
                            ->required(),

                        Textarea::make('isi_surat')
                            ->label('Isi Surat')
                            ->rows(10)
                            ->columnSpanFull()
                            ->required(),
                    ]),


                Section::make('Lampiran')
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('lampirans') // This links to the collection
                            ->label("Lampiran Surat (Max 10MB)")
                            ->multiple()
                            ->collection('lampiran-surat')
                            ->preserveFilenames()
                            ->conversion('thumb')
                            ->maxSize(10240),

                    ]),

                // Hidden Field

                Hidden::make('status_surat')
                    ->default('DRAFT')
                    ->dehydrated(),

                Hidden::make('unit_pengirim_id')
                    ->default(fn() => Auth::user()->unit_kerja_id)
                    ->dehydrated(),

                Hidden::make('user_pembuat_id')
                    ->default(fn() => Auth::user()->id)
                    ->dehydrated(),

                Hidden::make('tanggal_buat')
                    ->default(now())
                    ->dehydrated(),

            ]);
    }
}
