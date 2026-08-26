<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

trait HasApprovalActions
{
    protected function getActionPersetujuan(): array
    {
        return [
            'group_proses' => \Filament\Actions\ActionGroup::make([
                Action::make('approve_finish')
                    ->label('Setujui & Selesai')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->schema([
                        Textarea::make('catatan')
                            ->label('Catatan Persetujuan Akhir (Opsional)'),

                        //  Opsi Tanda Tangan
                        Fieldset::make('Tanda Tangan Digital')
                            ->schema([
                                Radio::make('qr_code_type')
                                    ->label('Sumber QR Code TTD')
                                    ->options([
                                        'generate' => 'Generate Otomatis dari Sistem (Verifikasi Internal)',
                                        'upload'   => 'Unggah QR Code Eksternal (BSrE, Privy, dll)',
                                        'none'     => 'Tanpa QR Code (Hanya Nama Terang)',
                                    ])
                                    ->default('generate')
                                    ->reactive()
                                    ->columnSpanFull(),

                                FileUpload::make('custom_qr_code')
                                    ->label('File QR Code')
                                    ->image()
                                    ->directory('signatures')
                                    ->visible(fn(Get $get) => $get('qr_code_type') === 'upload')
                                    ->required(fn(Get $get) => $get('qr_code_type') === 'upload')
                                    ->columnSpanFull(),
                                SignaturePad::make('drawn_signature')
                                    ->label('Gambar Tanda Tangan')
                                    ->downloadable()                    // Allow download of the signature (defaults to false)
                                    ->downloadableFormats([             // Available formats for download (defaults to all)
                                        DownloadableFormat::PNG,
                                        DownloadableFormat::JPG,
                                        DownloadableFormat::SVG,
                                    ])
                                    ->exportBackgroundColor('rgba(0,0,0,0)')
                                    ->exportPenColor('#000000')
                                    ->backgroundColor('#ffffff')       // White background on light mode
                                    ->backgroundColorOnDark('#111111') // Transparent background to let Tailwind classes show
                                    ->penColor('#000000')              // Black pen on light mode
                                    ->penColorOnDark('#ffffff')        // White pen on dark mode
                                    ->visible(fn(Get $get) => $get('qr_code_type') === 'draw')
                                    ->required(fn(Get $get) => $get('qr_code_type') === 'draw')
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->surat->riwayats()->where('status', 'MENUNGGU')->where('unit_tujuan_id', Auth::user()->unit_kerja_id)->latest()->first();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->approveStep($activeRiwayat, Auth::user(), null, null, true, true, 'UTAMA', $data['catatan'] ?? null, $data);
                        $this->refreshPage('Berhasil', 'Surat berhasil disetujui & ditandatangani.');
                    }),

                Action::make('approve_forward')
                    ->label('Setujui & Teruskan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->schema([
                        \Filament\Forms\Components\Select::make('next_unit_tujuan_id')
                            ->label('Teruskan Ke Unit')
                            ->options(\App\Models\UnitKerja::where('id', '!=', Auth::user()->unit_kerja_id)->pluck('nama_unit', 'id'))
                            ->searchable()->required(),
                        \Filament\Forms\Components\Toggle::make('tambah_ttd')->label('Tambahkan Tanda Tangan (Tertanda)')->default(false),
                        Textarea::make('catatan')->label('Catatan Penerusan (Opsional)'),
                    ])
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->surat->riwayats()->where('status', 'MENUNGGU')->where('unit_tujuan_id', Auth::user()->unit_kerja_id)->latest()->first();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->approveStep($activeRiwayat, Auth::user(), $data['next_unit_tujuan_id'], null, false, $data['tambah_ttd'] ?? false, 'UTAMA', $data['catatan'] ?? null);
                        $this->refreshPage('Berhasil', 'Surat disetujui dan diteruskan.');
                    }),

                Action::make('pure_forward')
                    ->label('Teruskan Saja (Tanpa Setuju)')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('gray')
                    ->schema([
                        \Filament\Forms\Components\Select::make('next_unit_tujuan_id')
                            ->label('Teruskan Ke Unit')
                            ->options(\App\Models\UnitKerja::where('id', '!=', Auth::user()->unit_kerja_id)->pluck('nama_unit', 'id'))
                            ->searchable()->required(),
                        Textarea::make('catatan')->label('Catatan (Opsional)'),
                    ])
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->surat->riwayats()->where('status', 'MENUNGGU')->where('unit_tujuan_id', Auth::user()->unit_kerja_id)->latest()->first();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->forwardStep($activeRiwayat, Auth::user(), $data['next_unit_tujuan_id'], $data['catatan'] ?? null); // add $data as 9th param if it required to also signing while forwarding - Seta
                        $this->refreshPage('Berhasil', 'Surat diteruskan tanpa persetujuan.');
                    }),
            ])
                ->label('Proses Surat')
                ->icon('heroicon-m-check-circle')
                ->button()
                ->color('success')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES'),

            'group_kembalikan' => \Filament\Actions\ActionGroup::make([
                Action::make('step_back')
                    ->label('Kembalikan ke Langkah Sebelumnya')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->schema([
                        Textarea::make('catatan')->label('Alasan Dikembalikan')->required(),
                    ])
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->surat->riwayats()->where('status', 'MENUNGGU')->where('unit_tujuan_id', Auth::user()->unit_kerja_id)->latest()->first();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->returnStep($activeRiwayat, Auth::user(), $data['catatan']);
                        $this->refreshPage('Berhasil', 'Surat dikembalikan ke unit sebelumnya.');
                    }),

                Action::make('reject')
                    ->label('Kembalikan ke Pembuat Awal (Reset)')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->schema([
                        Textarea::make('catatan')->label('Alasan Revisi Total')->required(),
                    ])
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->surat->riwayats()->where('status', 'MENUNGGU')->where('unit_tujuan_id', Auth::user()->unit_kerja_id)->latest()->first();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep($activeRiwayat, Auth::user(), 'REVISI', $data['catatan']);
                        $this->refreshPage('Berhasil', 'Surat dikembalikan secara total ke pembuat.');
                    }),

                Action::make('tolak_persetujuan')
                    ->label('Tolak Surat Sepenuhnya')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->schema([
                        Textarea::make('catatan')->label('Alasan Penolakan')->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->surat->riwayats()->where('status', 'MENUNGGU')->where('unit_tujuan_id', Auth::user()->unit_kerja_id)->latest()->first();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep($activeRiwayat, Auth::user(), 'DITOLAK', $data['catatan']);
                        $this->refreshPage('Berhasil', 'Surat pengajuan ditolak permanen.');
                    }),
            ])
                ->label('Kembalikan / Tolak')
                ->icon('heroicon-m-x-circle')
                ->button()
                ->color('danger')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES'),

            'terbitan' => Action::make('buat_terbitan')
                ->label('Terbitkan Surat Balasan')
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->visible(
                    fn() => $this->surat->tipe_surat === 'PENGAJUAN' &&
                        in_array($this->surat->status_surat, ['DIPROSES', 'SELESAI']) &&
                        Auth::user()->unit_kerja_id !== $this->surat->unit_pengirim_id
                )
                ->url(fn() => \App\Filament\Resources\Surats\Pages\CreateSurat::getUrl(['terbitan_for_surat_id' => $this->surat->id, 'tipe_surat' => 'TERBITAN']))
                ->openUrlInNewTab(),
        ];
    }
}
