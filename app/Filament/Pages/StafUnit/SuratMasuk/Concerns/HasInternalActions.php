<?php

namespace App\Filament\Pages\StafUnit\SuratMasuk\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Services\SuratRoutingService;

trait HasInternalActions
{
    protected function getActionSelesaiInternal(): array
    {
        return [
            Action::make('selesai_internal')
                ->label('Tandai Selesai & Balas')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES')
                ->schema([
                    Textarea::make('catatan')
                        ->label('Catatan Penyelesaian / Balasan')
                        ->required()
                        ->placeholder('Tulis balasan atau laporan penyelesaian Anda di sini...'),
                ])
                ->action(function (array $data): void {
                    $user = Auth::user();
                    $activeJabatan = $user->pegawai?->jabatanAktif()->first();
                    $unitId = $activeJabatan ? $activeJabatan->unit_kerja_id : null;

                    $activeRiwayat = $this->surat->riwayats()
                        ->where('status', 'MENUNGGU')
                        ->where('unit_tujuan_id', $unitId)
                        ->latest()
                        ->first();

                    if (!$activeRiwayat) {
                        Notification::make()->title('Gagal: Surat sudah diproses')->danger()->send();
                        return;
                    }

                    // Gunakan approveStep tanpa tanda tangan digital
                    app(SuratRoutingService::class)->approveStep(
                        currentRiwayat: $activeRiwayat,
                        actor: $user,
                        isFinalStep: true, // Ini akan mengubah status_surat menjadi SELESAI
                        isSignatureRequired: false,
                        catatan: $data['catatan']
                    );

                    // Notifikasi ke pengirim asli!
                    if ($this->surat->pembuat) {
                        Notification::make()
                            ->title('Surat Internal Selesai')
                            ->body("Unit Anda telah menyelesaikan surat '{$this->surat->perihal}'. Balasan: {$data['catatan']}")
                            ->success()
                            ->sendToDatabase($this->surat->pembuat);
                    }

                    $this->refreshPage('Berhasil', 'Surat internal berhasil diselesaikan dan dibalas.');
                }),
        ];
    }
}