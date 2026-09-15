<?php

namespace App\Livewire;

use App\Filament\Pages\StafUnit\SuratMasuk\Concerns\HasSuratTimeline;
use App\Models\Surat;
use App\Services\SuratExportService;
use Livewire\Component;

class GuestLacak extends Component
{
    use HasSuratTimeline;

    public string $trackingCode = '';
    public ?Surat $surat = null;
    public bool $searched = false;
    public string $errorMsg = '';

    protected $rules = [
        'trackingCode' => 'required|string|min:4',
    ];

    protected $messages = [
        'trackingCode.required' => 'Kode pelacakan wajib diisi.',
    ];

    public function mount(): void
    {
        // Otomatis mencari jika ada query parameter '?code=...' atau '?tracking_code=...'
        $code = request()->query('code') ?? request()->query('tracking_code');
        if (! empty($code)) {
            $this->trackingCode = trim($code);
            $this->search();
        }
    }

    public function search(): void
    {
        $this->validate();
        $this->searched = true;
        $this->errorMsg = '';

        $surat = Surat::with([
            'riwayats.unitTujuan',
            'riwayats.unitAsal',
            'riwayats.aktor',
            'disposisis.unitTujuan',
            'disposisis.unitPembuat',
            'terbitans',
            'media',
        ])
            ->where('tracking_code', trim($this->trackingCode))
            ->where('tipe_surat', 'PENGAJUAN')
            ->first();

        if (! $surat) {
            $this->errorMsg = 'Pengajuan dengan kode pelacakan tersebut tidak ditemukan. Mohon periksa kembali kode Anda.';
            $this->surat = null;
            return;
        }

        $this->surat = $surat;
    }

    /**
     * Unduh hasil surat terbitan resmi secara aman tanpa proteksi auth internal.
     */
    public function downloadTerbitan(int $terbitanId)
    {
        if (! $this->surat) {
            return;
        }

        // Verifikasi bahwa terbitan ini valid dan terhubung dengan pengajuan yang sedang dilacak
        $terbitan = $this->surat->terbitans()->where('id', $terbitanId)->first();
        if (! $terbitan && $this->surat->terbitan_for_surat_id == $terbitanId) {
            $terbitan = Surat::find($terbitanId);
        }

        if (! $terbitan) {
            $this->errorMsg = 'Dokumen terbitan tidak ditemukan.';
            return;
        }

        // 1. Ambil berkas dari koleksi media jika sudah tersedia
        $media = $terbitan->getFirstMedia('dokumen-final')
            ?? $terbitan->getFirstMedia('lampiran-surat')
            ?? $this->surat->getFirstMedia('dokumen-final');

        if ($media && file_exists($media->getPath())) {
            return response()->download($media->getPath(), $media->file_name);
        }

        // 2. Jika belum ada berkas fisik, buat dokumen PDF/ZIP on-the-fly via SuratExportService
        try {
            $exportService = app(SuratExportService::class);
            $zipPath = $exportService->export($terbitan);

            return response()->download($zipPath)->deleteFileAfterSend();
        } catch (\Throwable $e) {
            $this->errorMsg = 'Gagal mengunduh berkas: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.guest-lacak')
            ->layout('components.layouts.app', [
                'title'      => 'Lacak Pengajuan Surat - SIMAS',
                'showHeader' => true,
            ]);
    }
}
