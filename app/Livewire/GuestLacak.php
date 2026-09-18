<?php

namespace App\Livewire;

use App\Filament\Pages\StafUnit\SuratMasuk\Concerns\HasSuratTimeline;
use App\Models\Surat;
use App\Models\SuratRiwayat;
use App\Models\User;
use App\Services\SuratExportService;
use Filament\Notifications\Notification;
use Livewire\Component;
use Livewire\WithFileUploads;

class GuestLacak extends Component
{
    use HasSuratTimeline;
    use WithFileUploads;

    public string $trackingCode = '';
    public ?Surat $surat = null;
    public bool $searched = false;
    public string $errorMsg = '';

    public bool $showRevisiModal = false;
    public string $catatanPerbaikan = '';
    public $lampiranBaru = [];

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
            ->whereIn('tipe_surat', ['PENGAJUAN', 'EKSTERNAL'])
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

    public function openRevisiModal(): void
    {
        $this->catatanPerbaikan = '';
        $this->lampiranBaru = [];
        $this->showRevisiModal = true;
    }
    public function closeRevisiModal(): void
    {
        $this->showRevisiModal = false;
    }

     public function submitRevisi(): void
    {
        if (! $this->surat || $this->surat->status_surat !== 'REVISI') {
            return;
        }
        $this->validate([
            'catatanPerbaikan' => 'required|string|min:5',
            'lampiranBaru.*' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ], [
            'catatanPerbaikan.required' => 'Mohon berikan penjelasan mengenai perbaikan yang Anda lakukan.',
            'catatanPerbaikan.min' => 'Catatan perbaikan minimal 5 karakter.',
            'lampiranBaru.*.max' => 'Ukuran setiap file maksimal 5MB.',
        ]);
        // 1. Simpan lampiran baru (jika ada) ke Media Library
        if (!empty($this->lampiranBaru)) {
            foreach ($this->lampiranBaru as $file) {
                $this->surat->addMedia($file->getRealPath())
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection('lampiran-surat');
            }
        }
        // 2. Ambil riwayat revisi terakhir untuk menentukan unit verifikator
        $lastRevisi = $this->surat->riwayats->where('status', 'REVISI')->last();
        $targetUnitId = $lastRevisi?->unit_tujuan_id
            ?? $this->surat->unitTujuan->first()?->id
            ?? $this->surat->unit_pengirim_id;
        $unitAsalId = $this->surat->unit_pengirim_id ?? $targetUnitId;
        // 3. Catat Riwayat DIPERBARUI oleh pemohon
        SuratRiwayat::create([
            'surat_id'       => $this->surat->id,
            'parent_id'      => $lastRevisi?->id,
            'unit_asal_id'   => $unitAsalId,
            'unit_tujuan_id' => $targetUnitId,
            'user_aktor_id'  => null,
            'status'         => 'DIPERBARUI',
            'catatan'        => $this->catatanPerbaikan,
            'actioned_at'    => now(),
        ]);
        // 4. Inisialisasi antrean MENUNGGU kembali ke unit pemeriksa
        SuratRiwayat::create([
            'surat_id'       => $this->surat->id,
            'parent_id'      => null,
            'unit_asal_id'   => $unitAsalId,
            'unit_tujuan_id' => $targetUnitId,
            'user_aktor_id'  => null,
            'status'         => 'MENUNGGU',
            'catatan'        => '',
            'actioned_at'    => null,
        ]);
        // 5. Kembalikan status surat menjadi DIPROSES
        $this->surat->update(['status_surat' => 'DIPROSES']);
        // 6. Kirim notifikasi web ke staf unit pemeriksa
        if ($targetUnitId) {
            $targetUsers = User::ofUnitKerja($targetUnitId)->get();
            if ($targetUsers->isNotEmpty()) {
                Notification::make()
                    ->title('Berkas Pengajuan Diperbarui')
                    ->body('Pemohon ' . ($this->surat->pengirim_nama ?? 'Guest') . ' telah mengirimkan berkas perbaikan untuk surat: ' . $this->surat->perihal)
                    ->info()
                    ->viewData([
                        'unit_kerja_id' => (int) $targetUnitId,
                        'surat_id'      => $this->surat->id,
                    ])
                    ->sendToDatabase($targetUsers);
            }
        }
        // Reset state dan refresh data pelacakan
        $this->reset(['lampiranBaru', 'catatanPerbaikan', 'showRevisiModal']);
        $this->search();
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
