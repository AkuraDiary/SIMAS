<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Supposedly substitute (Haven't verified)
 *  // for downloading final letter
        if ($this->surat->tipe_surat === 'TERBITAN' && $this->surat->status_surat === 'SELESAI') {

            $primaryActions[] =
                \Filament\Actions\Action::make('download_pdf')
                ->label('Unduh PDF Resmi')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn() => in_array($this->surat->status_surat, ['SELESAI', 'TERBIT']) && $this->surat->hasMedia('dokumen-final'))
                ->action(function () {
                    // Ambil PDF yang sudah dikunci secara permanen di storage
                    $media = $this->surat->getFirstMedia('dokumen-final');
                    if ($media) {
                        return response()->download($media->getPath(), $media->file_name);
                    }

                    Notification::make()->title('File PDF belum tergenerate!')->danger()->send();
                });
        }

        // [NEW] Generate Nomor Action
        if ($this->surat->tipe_surat === 'TERBITAN' && empty($this->surat->nomor_surat)) {
            $primaryActions[] = Action::make('generate_nomor')
                ->label('Generate Nomor Surat')
                ->icon('heroicon-o-hashtag')
                ->color('primary')
                ->schema([
                    \Filament\Forms\Components\DatePicker::make('tanggal_surat')
                        ->label('Tanggal Surat (Bisa Backdate)')
                        ->default(now())
                        ->required()
                        ->reactive(),

                    \Filament\Forms\Components\Select::make('format_id')
                        ->label('Pilih Format Penomoran')
                        ->options(function () use ($unitId) {
                            return \App\Models\FormatNomorSurat::where('is_active', true)
                                ->where(function ($q) use ($unitId) {
                                    $q->whereNull('unit_kerja_id')
                                        ->orWhere('unit_kerja_id', $unitId);
                                })
                                ->get()
                                ->pluck('nama_format', 'id');
                        })
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state) return;
                            $format = \App\Models\FormatNomorSurat::find($state);
                            if ($format) {
                                $nextNumber = $format->nomor_urut_terakhir + 1;
                                $nextStr = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                                $tgl = $get('tanggal_surat') ? \Carbon\Carbon::parse($get('tanggal_surat')) : now();

                                $preview = $format->format_penomoran;
                                $preview = str_replace('{NOMOR}', $nextStr, $preview);
                                $preview = str_replace('{KODE_UNIT}', Auth::user()->unitKerja?->kode_unit ?? 'UNIT', $preview);
                                $preview = str_replace('{TAHUN}', $tgl->format('Y'), $preview);
                                // Simple romanizer helper
                                $romans = ['01' => 'I', '02' => 'II', '03' => 'III', '04' => 'IV', '05' => 'V', '06' => 'VI', '07' => 'VII', '08' => 'VIII', '09' => 'IX', '10' => 'X', '11' => 'XI', '12' => 'XII'];
                                $preview = str_replace('{BULAN_ROMAWI}', $romans[$tgl->format('m')] ?? '', $preview);

                                $set('nomor_surat_preview', $preview);
                            }
                        }),

                    \Filament\Forms\Components\Toggle::make('is_manual')
                        ->label('Kustomisasi Nomor / Sisipan Manual')
                        ->reactive()
                        ->helperText('Aktifkan jika Anda perlu menyisipkan nomor backdate secara manual (Cth: 151.A/UN/2026).'),

                    \Filament\Forms\Components\TextInput::make('nomor_surat_preview')
                        ->label('Preview / Nomor Akhir')
                        ->disabled(fn(callable $get) => ! $get('is_manual'))
                        ->dehydrated(true)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $isManual = $data['is_manual'] ?? false;
                    $nomorAkhir = $data['nomor_surat_preview'];

                    if (! $isManual) {
                        // Increment the real counter in database!
                        $format = \App\Models\FormatNomorSurat::find($data['format_id']);
                        $format->increment('nomor_urut_terakhir');
                    }

                    $this->surat->update([
                        'nomor_surat' => $nomorAkhir,
                        'tanggal_kirim' => $data['tanggal_surat'],
                        // Jika sudah ada nomornya, otomatis menjadi SELESAI
                        'status_surat' => 'SELESAI'
                    ]);

                    $this->refreshPage('Nomor Surat Berhasil Digenerate!', 'Surat kini resmi berstatus SELESAI dan siap diunduh.');
                });
        }

 */


trait HasFinalisasiActions
{
    protected function getFinalisasiActions(): array
    {
        $actions = [];
        $unitId = Auth::user()->unit_kerja_id;

        // 1. Download PDF Resmi dari Arsip
        if ($this->surat->tipe_surat === 'TERBITAN') {
            $actions[] = Action::make('download_pdf')
                ->label('Unduh PDF Resmi')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn() => in_array($this->surat->status_surat, ['SELESAI', 'TERBIT']) && $this->surat->hasMedia('dokumen-final'))
                ->action(function () {
                    $media = $this->surat->getFirstMedia('dokumen-final');
                    if ($media) {
                        return response()->download($media->getPath(), $media->file_name);
                    }
                    Notification::make()->title('File PDF belum tergenerate!')->danger()->send();
                });
        }

        // 2. Generate Nomor Action
        if ($this->surat->tipe_surat === 'TERBITAN' && empty($this->surat->nomor_surat)) {
            $actions[] = Action::make('generate_nomor')
                ->label('Generate Nomor Surat')
                ->icon('heroicon-o-hashtag')
                ->color('primary')
                ->schema([
                    DatePicker::make('tanggal_surat')
                        ->label('Tanggal Surat (Bisa Backdate)')
                        ->default(now())
                        ->required()
                        ->reactive(),

                    Select::make('format_id')
                        ->label('Pilih Format Penomoran')
                        ->options(function () use ($unitId) {
                            return \App\Models\FormatNomorSurat::where('is_active', true)
                                ->where(function ($q) use ($unitId) {
                                    $q->whereNull('unit_kerja_id')
                                        ->orWhere('unit_kerja_id', $unitId);
                                })
                                ->get()
                                ->pluck('nama_format', 'id');
                        })
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state) return;
                            $format = \App\Models\FormatNomorSurat::find($state);
                            if ($format) {
                                $nextNumber = $format->nomor_urut_terakhir + 1;
                                $nextStr = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                                $tgl = $get('tanggal_surat') ? \Carbon\Carbon::parse($get('tanggal_surat')) : now();
                                $preview = $format->format_penomoran;
                                $preview = str_replace('{NOMOR}', $nextStr, $preview);
                                $preview = str_replace('{KODE_UNIT}', Auth::user()->unitKerja?->kode_unit ?? 'UNIT', $preview);
                                $preview = str_replace('{TAHUN}', $tgl->format('Y'), $preview);
                                $romans = ['01'=>'I','02'=>'II','03'=>'III','04'=>'IV','05'=>'V','06'=>'VI','07'=>'VII','08'=>'VIII','09'=>'IX','10'=>'X','11'=>'XI','12'=>'XII'];
                                $preview = str_replace('{BULAN_ROMAWI}', $romans[$tgl->format('m')] ?? '', $preview);
                                $set('nomor_surat_preview', $preview);
                            }
                        }),

                    Toggle::make('is_manual')
                        ->label('Kustomisasi Nomor / Sisipan Manual')
                        ->reactive()
                        ->helperText('Aktifkan jika Anda perlu menyisipkan nomor backdate secara manual (Cth: 151.A/UN/2026).'),

                    TextInput::make('nomor_surat_preview')
                        ->label('Preview / Nomor Akhir')
                        ->disabled(fn(callable $get) => ! $get('is_manual'))
                        ->dehydrated(true)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $isManual = $data['is_manual'] ?? false;
                    $nomorAkhir = $data['nomor_surat_preview'];

                    if (! $isManual) {
                        $format = \App\Models\FormatNomorSurat::find($data['format_id']);
                        $format->increment('nomor_urut_terakhir');
                    }

                    $this->surat->update([
                        'nomor_surat' => $nomorAkhir,
                        'tanggal_kirim' => $data['tanggal_surat'],
                        'status_surat' => 'SELESAI'
                    ]);

                    $this->refreshPage('Nomor Surat Berhasil Digenerate!', 'Surat kini resmi berstatus SELESAI dan siap diunduh.');
                });
        }

        return $actions;
    }
}