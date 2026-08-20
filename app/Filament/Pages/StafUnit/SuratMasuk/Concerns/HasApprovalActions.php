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
            Action::make('approve')
                ->label('Setujui / TTD')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES')
                ->schema([
                    Textarea::make('catatan')
                        ->label('Catatan Persetujuan (Opsional)')
                        ->placeholder('Catatan atau catatan persetujuan...'),
                ])
                ->action(function (array $data): void {
                    $activeRiwayat = $this->surat->riwayats()
                        ->where('status', 'MENUNGGU')
                        ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
                        ->latest()
                        ->first();

                    if (!$activeRiwayat) {
                        Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
                        return;
                    }

                    app(\App\Services\SuratRoutingService::class)->approveStep(
                        currentRiwayat: $activeRiwayat,
                        actor: Auth::user(),
                        isFinalStep: true,
                        isSignatureRequired: true,
                        catatan: $data['catatan'] ?? null
                    );

                    if ($this->surat->pembuat) {
                        Notification::make()
                            ->title('Surat Disetujui')
                            ->body("Surat pengajuan Anda '{$this->surat->perihal}' telah disetujui.")
                            ->success()
                            ->sendToDatabase($this->surat->pembuat);
                    }

                    $this->refreshPage('Berhasil', 'Surat berhasil disetujui & ditandatangani.');
                }),
            Action::make('reject')
                ->label('Minta Revisi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES')
                ->schema([
                    Textarea::make('catatan')
                        ->label('Alasan Revisi')
                        ->required()
                        ->placeholder('Jelaskan bagian yang perlu diperbaiki...'),
                ])
                ->action(function (array $data): void {
                    $activeRiwayat = $this->surat->riwayats()
                        ->where('status', 'MENUNGGU')
                        ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
                        ->latest()
                        ->first();

                    if (!$activeRiwayat) {
                        Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
                        return;
                    }

                    app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep(
                        currentRiwayat: $activeRiwayat,
                        actor: Auth::user(),
                        newStatus: 'REVISI',
                        catatan: $data['catatan']
                    );

                    if ($this->surat->pembuat) {
                        Notification::make()
                            ->title('Surat Perlu Direvisi')
                            ->body("Surat pengajuan Anda '{$this->surat->perihal}' dikembalikan untuk revisi. Catatan: {$data['catatan']}")
                            ->warning()
                            ->sendToDatabase($this->surat->pembuat);
                    }

                    $this->refreshPage('Berhasil', 'Surat dikembalikan untuk revisi.');
                }),
            Action::make('tolak_persetujuan')
                ->label('Tolak Persetujuan')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES')
                ->schema([
                    Textarea::make('catatan')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->placeholder('Jelaskan mengapa surat ini ditolak...'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Tolak Surat Pengajuan')
                ->modalDescription('Apakah Anda yakin ingin menolak surat pengajuan ini? Surat akan dibatalkan.')
                ->action(function (array $data): void {
                    $activeRiwayat = $this->surat->riwayats()
                        ->where('status', 'MENUNGGU')
                        ->where('unit_tujuan_id', Auth::user()->unit_kerja_id)
                        ->latest()
                        ->first();

                    if (!$activeRiwayat) {
                        Notification::make()->title('Langkah persetujuan tidak ditemukan')->danger()->send();
                        return;
                    }

                    app(\App\Services\SuratRoutingService::class)->rejectOrReviseStep(
                        currentRiwayat: $activeRiwayat,
                        actor: Auth::user(),
                        newStatus: 'DITOLAK',
                        catatan: $data['catatan']
                    );

                    if ($this->surat->pembuat) {
                        Notification::make()
                            ->title('Surat Ditolak')
                            ->body("Surat pengajuan Anda '{$this->surat->perihal}' telah ditolak. Alasan: {$data['catatan']}")
                            ->danger()
                            ->sendToDatabase($this->surat->pembuat);
                    }

                    $this->refreshPage('Berhasil', 'Surat pengajuan berhasil ditolak dan dibatalkan.');
                }),
            Action::make('buat_terbitan')
                ->label('Terbitkan Surat Balasan')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->visible(
                    fn() => $this->surat->tipe_surat === 'PENGAJUAN' &&
                        in_array($this->surat->status_surat, ['DIPROSES', 'SELESAI']) &&
                        Auth::user()->unit_kerja_id !== $this->surat->unit_pengirim_id // user BUKAN pengirim surat pengajuan itu sendiri
                )
                ->url(fn() => \App\Filament\Resources\Surats\Pages\CreateSurat::getUrl(['terbitan_for_surat_id' => $this->surat->id, 'tipe_surat' => 'TERBITAN']))
                ->openUrlInNewTab(),
        ];
    }
}
