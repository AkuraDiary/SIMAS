<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

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
                    ])
                    ->action(function (array $data): void {
                        $activeRiwayat = $this->surat->riwayats()->where('status', 'MENUNGGU')->where('unit_tujuan_id', Auth::user()->unit_kerja_id)->latest()->first();
                        if (!$activeRiwayat) return;

                        app(\App\Services\SuratRoutingService::class)->approveStep($activeRiwayat, Auth::user(), null, null, true, true, 'UTAMA', $data['catatan'] ?? null);
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

                        app(\App\Services\SuratRoutingService::class)->forwardStep($activeRiwayat, Auth::user(), $data['next_unit_tujuan_id'], $data['catatan'] ?? null);
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
