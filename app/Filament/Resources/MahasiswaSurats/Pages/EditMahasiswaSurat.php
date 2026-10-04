<?php

namespace App\Filament\Resources\MahasiswaSurats\Pages;

use App\Filament\Resources\MahasiswaSurats\MahasiswaSuratResource;
use App\Models\Surat;
use App\Models\SuratRiwayat;
use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMahasiswaSurat extends EditRecord
{
    protected static string $resource = MahasiswaSuratResource::class;

    protected function getFormActions(): array
    {
        // Tombol aksi ditangani langsung oleh Wizard submitAction di SuratForm
        return [];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);
    
        // 1. Validasi hanya surat REVISI yang dapat diedit
        if ($this->record->status_surat !== 'REVISI') {
            abort(403, 'Hanya permohonan dengan status REVISI yang dapat diedit.');
        }
        // 2. Hydrate pilihan template sebelumnya
        if ($this->record->template_id === null) {
            // Mode Scratch (Bebas)
            $this->data['template_id'] = 'scratch';
            if (isset($this->record->content['isi_surat'])) {
                $this->data['content_scratch'] = $this->record->content['isi_surat'];
            }
        } else {
            // Mode Template Resmi (casting ke string agar sinkron dengan Alpine.js selector)
            $this->data['template_id'] = (string) $this->record->template_id;
        }
        // 3. Hydrate unit tujuan (jika mode scratch)
        if (empty($this->data['unit_tujuan'])) {
            $this->data['unit_tujuan'] = $this->record->unitTujuan->first()?->id;
        }
        // 4. Hydrate kontak telepon mahasiswa jika belum ada di form
        if (empty($this->data['pengirim_telp'])) {
            $this->data['pengirim_telp'] = auth()->user()->phone;
        }
    }

    public function getTitle(): string
    {
        return 'Perbaiki Pengajuan: ' . $this->record->perihal;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['content_scratch'])) {
            $content = $data['content'] ?? [];
            $content['isi_surat'] = $data['content_scratch'];
            $data['content'] = $content;
        }

        // Simpan catatan perbaikan ke properti sementara sebelum di-unset
        $this->catatanPerbaikan = $data['catatan_perbaikan'] ?? 'Mahasiswa telah memperbarui dokumen permohonan.';

        // Bersihkan atribut form yang bukan kolom tabel fisik
        unset(
            $data['pengirim_telp'],
            $data['content_scratch'],
            $data['unit_tujuan'],
            $data['konfirmasi'],
            $data['catatan_perbaikan']
        );

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Surat $surat */
        $surat = $this->record;

        $lastRevisi = $surat->riwayats->where('status', 'REVISI')->last();
        $targetUnitId = $lastRevisi?->unit_tujuan_id
            ?? $surat->unitTujuan->first()?->id
            ?? $surat->unit_pengirim_id;
        $unitAsalId = $surat->pengirim_metadata['prodi_id']
            ?? $surat->pengirim_metadata['fakultas_id']
            ?? $targetUnitId;

        $catatan = $this->catatanPerbaikan ?? 'Mahasiswa telah memperbarui dokumen permohonan.';

        // 1. Catat Linimasa DIPERBARUI oleh mahasiswa
        SuratRiwayat::create([
            'surat_id'       => $surat->id,
            'parent_id'      => $lastRevisi?->id,
            'unit_asal_id'   => $unitAsalId,
            'unit_tujuan_id' => $targetUnitId,
            'user_aktor_id'  => auth()->id(),
            'status'         => 'DIPERBARUI',
            'catatan'        => $catatan,
            'actioned_at'    => now(),
        ]);

        // 2. Kembalikan antrean status MENUNGGU ke unit pemeriksa
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

        // 3. Kembalikan status surat ke DIPROSES
        $surat->update(['status_surat' => 'DIPROSES']);

        // 4. Kirim notifikasi sistem & WhatsApp ke petugas unit
        if ($targetUnitId) {
            $targetUsers = User::ofUnitKerja($targetUnitId)
                ->get()
                ->filter(fn(User $u) => $u->canViewAllSuratMasukUnit($targetUnitId));

            if ($targetUsers->isNotEmpty()) {
                Notification::make()
                    ->title('Pengajuan Mahasiswa Telah Diperbaiki')
                    ->body('Mahasiswa ' . ($surat->pengirim_nama ?? 'Mahasiswa') . ' telah memperbarui berkas untuk: ' . $surat->perihal)
                    ->info()
                    ->viewData([
                        'unit_kerja_id' => (int) $targetUnitId,
                        'surat_id'      => $surat->id,
                    ])
                    ->sendToDatabase($targetUsers);

                app(WhatsAppNotificationService::class)->notifySuratMasuk($surat, $targetUsers);
            }
        }

        Notification::make()
            ->title('Perbaikan Berhasil Dikirim')
            ->body('Dokumen permohonan Anda telah berhasil diperbarui dan diserahkan kembali ke pemeriksa.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return MahasiswaSuratResource::getUrl('view', ['record' => $this->record]);
    }
}
