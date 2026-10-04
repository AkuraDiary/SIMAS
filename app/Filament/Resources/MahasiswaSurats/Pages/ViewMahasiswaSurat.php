<?php

namespace App\Filament\Resources\MahasiswaSurats\Pages;

use App\Filament\Pages\StafUnit\SuratMasuk\Concerns\HasSuratTimeline;
use App\Filament\Resources\MahasiswaSurats\MahasiswaSuratResource;
use App\Models\Surat;
use App\Models\SuratRiwayat;
use App\Models\User;
use App\Services\PlaceholderService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ViewMahasiswaSurat extends ViewRecord
{
    use HasSuratTimeline;
    use WithFileUploads;

    protected static string $resource = MahasiswaSuratResource::class;
    protected string $view = 'filament.resources.mahasiswa-surats.view';

    public ?Surat $surat = null;
    public string $catatanPerbaikan = '';
    public $lampiranBaru = [];
    public ?string $previewUrl = null;
    public ?string $downloadUrl = null;
    public bool $previewIsImage = false;


    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->surat = $this->record;
    }

    public function getTitle(): string
    {
        return ($this->record->perihal ?? 'Surat');
    }

    /**
     * Membuka modal pratinjau berkas (PDF / Gambar) seperti di halaman pegawai
     */
    public function openPreview(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);
        $this->previewIsImage = str_starts_with($media->mime_type, 'image/');

        if (str_starts_with($media->mime_type, 'image/') || $media->mime_type === 'application/pdf') {
            $this->previewUrl = route('media.file', $media->id);
            $this->downloadUrl = route('media.download', $media->id);
        } else {
            $this->previewUrl = null;
            $this->downloadUrl = route('media.download', $media->id);
        }

        $this->dispatch('open-modal', id: 'preview-modal');
    }

    public function getRenderedHtmlProperty(): string
    {
        $surat = $this->record;
        if ($surat->template_id && $surat->template) {
            return app(PlaceholderService::class)->renderHtml($surat->template, $surat->content ?? [], $surat);
        }
        return app(PlaceholderService::class)->renderScratchHtml($surat);
    }

    /**
     * Kirim perbaikan jika status surat adalah REVISI
     */
    public function submitRevisi(): void
    {
        $surat = $this->record;
        if ($surat->status_surat !== 'REVISI') {
            return;
        }

        $this->validate([
            'catatanPerbaikan' => 'required|string|min:5',
            'lampiranBaru.*'   => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ], [
            'catatanPerbaikan.required' => 'Mohon berikan penjelasan perbaikan Anda.',
            'catatanPerbaikan.min'      => 'Catatan perbaikan minimal 5 karakter.',
            'lampiranBaru.*.max'        => 'Ukuran setiap berkas maksimal 10MB.',
        ]);

        // 1. Simpan lampiran baru jika ada
        if (!empty($this->lampiranBaru)) {
            foreach ($this->lampiranBaru as $file) {
                $surat->addMedia($file->getRealPath())
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection('lampiran-surat');
            }
        }

        // 2. Ambil riwayat revisi terakhir
        $lastRevisi = $surat->riwayats->where('status', 'REVISI')->last();
        $targetUnitId = $lastRevisi?->unit_tujuan_id
            ?? $surat->unitTujuan->first()?->id
            ?? $surat->unit_pengirim_id;
        $unitAsalId = $surat->pengirim_metadata['prodi_id']
            ?? $surat->pengirim_metadata['fakultas_id']
            ?? $targetUnitId;

        // 3. Catat Riwayat DIPERBARUI
        SuratRiwayat::create([
            'surat_id'       => $surat->id,
            'parent_id'      => $lastRevisi?->id,
            'unit_asal_id'   => $unitAsalId,
            'unit_tujuan_id' => $targetUnitId,
            'user_aktor_id'  => auth()->id(),
            'status'         => 'DIPERBARUI',
            'catatan'        => $this->catatanPerbaikan,
            'actioned_at'    => now(),
        ]);

        // 4. Catat antrean MENUNGGU kembali ke unit pemeriksa
        SuratRiwayat::create([
            'surat_id'       => $surat->id,
            'parent_id'      => null,
            'unit_asal_id'   => $unitAsalId,
            'unit_tujuan_id' => $targetUnitId,
            'user_aktor_id'  => null,
            'status'         => 'MENUNGGU',
            'catatan'        => '',
            'actioned_at'    => null,
        ]);

        // 5. Kembalikan status surat ke DIPROSES
        $surat->update(['status_surat' => 'DIPROSES']);

        // 6. Notifikasi sistem ke staf unit pemeriksa
        if ($targetUnitId) {
            $targetUsers = User::ofUnitKerja($targetUnitId)->get();
            if ($targetUsers->isNotEmpty()) {
                Notification::make()
                    ->title('Berkas Pengajuan Mahasiswa Diperbarui')
                    ->body('Mahasiswa ' . ($surat->pengirim_nama ?? 'Mahasiswa') . ' telah memperbarui berkas untuk: ' . $surat->perihal)
                    ->info()
                    ->viewData([
                        'unit_kerja_id' => (int) $targetUnitId,
                        'surat_id'      => $surat->id,
                    ])
                    ->sendToDatabase($targetUsers);
            }
        }

        $this->reset(['catatanPerbaikan', 'lampiranBaru']);
        $this->record->refresh();
        $this->surat = $this->record;

        Notification::make()
            ->title('Berkas Perbaikan Terkirim')
            ->body('Dokumen Anda telah diperbarui dan dikirim kembali untuk diverifikasi.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
          $actions = [];
        // Tombol Perbaiki jika surat berstatus REVISI (sama seperti di staf)
        if ($this->record->status_surat === 'REVISI') {
            $actions[] = Action::make('perbaiki')
                ->label('Perbaiki Surat')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url(MahasiswaSuratResource::getUrl('edit', ['record' => $this->record]));
        }
        // $actions[] = Action::make('lacak')
        //     ->label('Halaman Pelacak Publik')
        //     ->icon('heroicon-o-arrow-top-right-on-square')
        //     ->color('gray')
        //     ->url(fn () => url('/lacak?code=' . ($this->record->tracking_code ?: 'REQ-' . $this->record->id)))
        //     ->openUrlInNewTab();
        return $actions;
    }
}
