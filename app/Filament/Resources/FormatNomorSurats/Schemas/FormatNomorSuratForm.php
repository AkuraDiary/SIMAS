<?php

namespace App\Filament\Resources\FormatNomorSurats\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FormatNomorSuratForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_format')
                    ->label('Nama Format (mis. Format Pusat)')
                    ->required()
                    ->maxLength(255),

                Actions::make([
                    Action::make('add_nomor')
                        ->label('+ {NOMOR} (Nomor)')
                        ->color('gray')
                        ->action(function (Set $set, Get $get) {
                            $set('format_penomoran', $get('format_penomoran') . '{NOMOR}');
                        }),
                    Action::make('add_kode_unit')
                        ->label('+ {KODE_UNIT} (Kode Unit)')
                        ->color('gray')
                        ->action(function (Set $set, Get $get) {
                            $set('format_penomoran', $get('format_penomoran') . '{KODE_UNIT}');
                        }),
                    Action::make('add_tahun')
                        ->label('+ {TAHUN} (Tahun)')
                        ->color('gray')
                        ->action(function (Set $set, Get $get) {
                            $set('format_penomoran', $get('format_penomoran') . '{TAHUN}');
                        }),
                    Action::make('add_bulan_romawi')
                        ->label('+ {BULAN_ROMAWI} (Bulan Romawi)')
                        ->color('gray')
                        ->action(function (Set $set, Get $get) {
                            $set('format_penomoran', $get('format_penomoran') . '{BULAN_ROMAWI}');
                        }),
                ])->columnSpanFull(),

                \Filament\Forms\Components\TextInput::make('format_penomoran')
                    ->label('')
                    ->placeholder('Contoh: ND/{KODE_UNIT}/{TAHUN}/{NOMOR}')
                    ->helperText('Anda juga bisa mengetik variabel kustom seperti {KODE_KLASIFIKASI}. Pengguna akan diminta mengisi nilainya saat membuat surat.')
                    ->required()
                    ->live()
                    ->columnSpanFull()
                    ->maxLength(255),

                TextEntry::make('live_preview')
                    ->label('LIVE PREVIEW')
                    ->state(function (Get $get) {
                        $format = $get('format_penomoran') ?? '';
                        $format = str_replace('{NOMOR}', '001', $format);
                        $format = str_replace('{KODE_UNIT}', 'REK', $format);
                        $format = str_replace('{BULAN_ROMAWI}', 'VIII', $format);
                        $format = str_replace('{TAHUN}', date('Y'), $format);

                        return new \Illuminate\Support\HtmlString('<div class="p-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm rounded-lg text-lg font-mono flex items-center gap-3 text-gray-950 dark:text-white">
                            <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            ' . htmlspecialchars($format) . '
                        </div>');
                    })
                    ->columnSpanFull(),

                \Filament\Forms\Components\TextInput::make('tahun')
                    ->label('Tahun Berjalan')
                    ->required()
                    ->numeric()
                    ->default(date('Y')),

                \Filament\Forms\Components\TextInput::make('nomor_urut_terakhir')
                    ->label('Nomor Urut Terakhir')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Angka ini akan bertambah otomatis. Atur ke 0 untuk memulai dari 1.'),

                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->helperText('Hanya ada satu format aktif (per unit/global) pada satu waktu.'),
            ]);
    }
}
