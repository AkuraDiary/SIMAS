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
                \Filament\Forms\Components\Select::make('unit_kerja_id')
                    ->label('Unit Kerja')
                    ->relationship('unitKerja', 'nama_unit')
                    ->placeholder('Format Global / Pusat (Semua Unit)')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->visible(fn () => auth()->user()?->tipe_entitas === 'ADMIN')
                    ->helperText('Kosongkan jika ingin dijadikan Format Global/Pusat sebagai default untuk semua unit.')
                    ->columnSpanFull(),

                TextInput::make('nama_format')
                    ->label('Nama Format (mis. Nota Dinas FT / Format Pusat)')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\Select::make('tipe_surat')
                    ->label('Tipe Surat')
                    ->options([
                        'ALL' => 'Semua Tipe Surat (Default / Fallback)',
                        'INTERNAL' => 'Surat Internal (Nota Dinas / Memo)',
                        'PENGAJUAN' => 'Surat Pengajuan',
                        'TERBITAN' => 'Surat Terbitan Resmi (SK / ST)',
                        'EKSTERNAL' => 'Surat Eksternal',
                    ])
                    ->default('ALL')
                    ->required()
                    ->live(),

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
                    Action::make('add_bulan_romawi')
                        ->label('+ {BULAN_ROMAWI} (Bulan Romawi)')
                        ->color('gray')
                        ->action(function (Set $set, Get $get) {
                            $set('format_penomoran', $get('format_penomoran') . '{BULAN_ROMAWI}');
                        }),
                    Action::make('add_bulan_angka')
                        ->label('+ {BULAN_ANGKA} (Bulan Angka)')
                        ->color('gray')
                        ->action(function (Set $set, Get $get) {
                            $set('format_penomoran', $get('format_penomoran') . '{BULAN_ANGKA}');
                        }),
                    Action::make('add_tahun')
                        ->label('+ {TAHUN} (Tahun)')
                        ->color('gray')
                        ->action(function (Set $set, Get $get) {
                            $set('format_penomoran', $get('format_penomoran') . '{TAHUN}');
                        }),
                    Action::make('add_tipe')
                        ->label('+ {TIPE} (Kode Tipe)')
                        ->color('gray')
                        ->action(function (Set $set, Get $get) {
                            $set('format_penomoran', $get('format_penomoran') . '{TIPE}');
                        }),
                    Action::make('add_custom_sample')
                        ->label('+ {KODE_KLASIFIKASI} (Tag Kustom)')
                        ->color('gray')
                        ->action(function (Set $set, Get $get) {
                            $set('format_penomoran', $get('format_penomoran') . '{KODE_KLASIFIKASI}');
                        }),
                ])->columnSpanFull(),

                \Filament\Forms\Components\TextInput::make('format_penomoran')
                    ->label('Pola Penomoran')
                    ->placeholder('Contoh: {NOMOR}/{KODE_KLASIFIKASI}/{KODE_UNIT}/{BULAN_ROMAWI}/{TAHUN}')
                    ->helperText('Gunakan token sistem di atas atau ketik variabel kustom seperti {KODE_KLASIFIKASI} / {KODE_HAL}. Staf akan mengisi nilainya saat pembuatan surat / penetapan nomor.')
                    ->required()
                    ->live()
                    ->columnSpanFull()
                    ->maxLength(255),

                TextEntry::make('live_preview')
                    ->label('LIVE PREVIEW')
                    ->state(function (Get $get) {
                        $rawFormat = $get('format_penomoran') ?? '';
                        $format = $rawFormat;
                        $padding = (int) ($get('padding_digit') ?? 3);
                        $sampleNomor = str_pad('1', max(1, $padding), '0', STR_PAD_LEFT);
                        $tipe = $get('tipe_surat') ?? 'ALL';
                        $tipeCode = match ($tipe) {
                            'INTERNAL' => 'ND',
                            'PENGAJUAN' => 'PGN',
                            'TERBITAN' => 'SK',
                            'EKSTERNAL' => 'EKS',
                            default => 'SRT',
                        };

                        $format = str_replace('{NOMOR}', $sampleNomor, $format);
                        $format = str_replace('{KODE_UNIT}', 'REK', $format);
                        $format = str_replace('{BULAN_ROMAWI}', 'IX', $format);
                        $format = str_replace('{BULAN_ANGKA}', '09', $format);
                        $format = str_replace('{TAHUN}', (string) ($get('tahun') ?? date('Y')), $format);
                        $format = str_replace('{TIPE}', $tipeCode, $format);

                        $customTags = app(\App\Services\NomorSuratService::class)->extractCustomTags($rawFormat);
                        $badgeTags = '';
                        if (!empty($customTags)) {
                            $badgeTags = '<div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-1.5"><span>Tag Kustom Dinamis Terdeteksi:</span> ' . implode('', array_map(fn($t) => '<span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300 rounded font-semibold font-mono">{' . $t . '}</span>', $customTags)) . '</div>';
                        }

                        return new \Illuminate\Support\HtmlString('<div><div class="p-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm rounded-lg text-lg font-mono flex items-center gap-3 text-gray-950 dark:text-white">
                            <svg class="w-6 h-6 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span>' . htmlspecialchars($format) . '</span>
                        </div>' . $badgeTags . '</div>');
                    })
                    ->columnSpanFull(),

                \Filament\Forms\Components\TextInput::make('padding_digit')
                    ->label('Jumlah Digit Padding')
                    ->required()
                    ->numeric()
                    ->default(3)
                    ->minValue(1)
                    ->maxValue(6)
                    ->live()
                    ->helperText('Contoh: 3 menghasilkan 001, 4 menghasilkan 0001.'),

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
                    ->helperText('Format aktif akan digunakan sebagai acuan penomoran pada unit dan tipe yang bersangkutan.'),
            ]);
    }
}
