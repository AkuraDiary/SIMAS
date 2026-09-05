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
                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->color('gray')
                ->visible(fn() => $this->surat->status_surat === 'DIPROSES')
                ->schema([
                    Textarea::make('catatan')
                        ->label('Catatan Penyelesaian / Balasan')
                        ->required()
                        ->placeholder('Tulis balasan atau laporan penyelesaian Anda di sini...'),
                ])
                ->action(function (array $data): void {
                    $user = Auth::user();
                    $activeRiwayat = $this->getActiveInternalRiwayat();

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
                            ->viewData([
                                'unit_kerja_id' => (int) $this->surat->unit_pengirim_id,
                                'surat_id'      => $this->surat->id,
                            ])
                            ->sendToDatabase($this->surat->pembuat);

                        app(\App\Services\WhatsAppNotificationService::class)->notifySuratSelesai(
                            $this->surat,
                            $this->surat->pembuat,
                            "Balasan: " . $data['catatan']
                        );
                    }

                    $this->refreshPage('Berhasil', 'Surat internal berhasil diselesaikan dan dibalas.');
                }),
        ];
    }

    /**
     * Get the currently active routing step (Riwayat) for Internal letters.
     */
    protected function getActiveInternalRiwayat()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $unitId = $user->getActiveUnitId();

        return $this->surat->riwayats()
            ->where('status', 'MENUNGGU')
            ->where('unit_tujuan_id', $unitId)
            ->latest()
            ->first();
    }
}
