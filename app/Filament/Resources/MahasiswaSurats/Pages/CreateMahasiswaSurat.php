<?php

namespace App\Filament\Resources\MahasiswaSurats\Pages;

use App\Filament\Pages\SimasDashboard;
use App\Filament\Resources\MahasiswaSurats\MahasiswaSuratResource;
use App\Filament\Resources\MahasiswaSurats\SuratResource;
use App\Models\Surat;
use App\Models\SuratRiwayat;
use App\Models\Template;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\FonnteService;
use App\Services\WhatsAppNotificationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Vinkla\Hashids\Facades\Hashids;

class CreateMahasiswaSurat extends CreateRecord
{
    protected static string $resource = MahasiswaSuratResource::class;
    protected static ?string $title = 'Buat Pengajuan Surat Baru';
    protected function getFormActions(): array
    {
        return [];
    }


    public function downloadDraft()
    {
        return app(\App\Services\SuratExportService::class)->downloadDraftPdf($this->form->getRawState());
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $mhs = $user?->mahasiswa;
        $data['user_pembuat_id'] = $user->id;
        $data['tipe_surat']      = 'PENGAJUAN';
        $data['status_surat']    = 'TERKIRIM';
        $data['tanggal_kirim']   = now();
        $metadata = [
            'tipe_pengirim' => 'mahasiswa',
            'telp'          => $data['pengirim_telp'] ?? $user->phone,
            'fakultas_id'   => $mhs?->fakultas_id ?? null,
            'prodi_id'      => $mhs?->prodi_id ?? null,
        ];
        $data['pengirim_metadata'] = $metadata;
        $data['pengirim_nim']      = $mhs?->nim ?? ($data['pengirim_nim'] ?? null);
        $data['pengirim_nama']     = $mhs?->nama_lengkap ?? $user->nama_lengkap;
        $data['pengirim_email']    = $user->email;
        // Penanganan Scratch vs Template
        if (!empty($data['template_id']) && $data['template_id'] !== 'scratch') {
            $template = Template::find($data['template_id']);
            if ($template) {
                if (empty($data['perihal'])) {
                    $data['perihal'] = 'Pengajuan ' . $template->nama_template;
                }
                if (!empty($template->approval_path)) {
                    $data['approval_path'] = $template->approval_path;
                }
            }
        } else {
            $data['template_id'] = null;
            if (empty($data['perihal'])) {
                $data['perihal'] = 'Pengajuan Mahasiswa';
            }
            $content = $data['content'] ?? [];
            if (!empty($data['content_scratch'])) {
                $content['isi_surat'] = $data['content_scratch'];
            }

            $data['content'] = $content;
        }

        // Hapus field form yang bukan kolom fisik tabel surats
        unset(
            $data['pengirim_telp'],
            $data['content_scratch'],
            $data['unit_tujuan']
        );
        return $data;
    }
    protected function afterCreate(): void
    {
        /** @var Surat $surat */
        $surat = $this->record;
        // 1. Generate Tracking Code Resmi (REQ-...)
        $surat->tracking_code = 'REQ-' . strtoupper(Hashids::encode($surat->id));
        $surat->save();
        // 2. Hubungkan Unit Tujuan & Inisialisasi Riwayat Workflow
        $targetUnitId = null;
        if ($surat->template && $surat->template->entry_point_unit_id) {
            $targetUnitId = (int) $surat->template->entry_point_unit_id;
        } elseif (!empty($this->data['unit_tujuan'])) {
            $targetUnitId = (int) $this->data['unit_tujuan'];
        }
        if ($targetUnitId) {
            $surat->unitTujuan()->syncWithoutDetaching([
                $targetUnitId => [
                    'jenis_tujuan'   => 'UTAMA',
                    'tanggal_terima' => now(),
                    'status_baca'    => 'BELUM',
                ],
            ]);
            $unitAsalId = $surat->pengirim_metadata['prodi_id']
                ?? $surat->pengirim_metadata['fakultas_id']
                ?? $targetUnitId;
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
            // 3. Notifikasi Sistem & WhatsApp ke Petugas Unit yang Berhak
            $targetUsers = User::ofUnitKerja($targetUnitId)
                ->get()
                ->filter(fn(User $u) => $u->canViewAllSuratMasukUnit($targetUnitId));
            if ($targetUsers->isEmpty()) {
                $kepala = UnitKerja::find($targetUnitId)?->getKepalaUnit()?->pegawai?->user;
                if ($kepala) {
                    $targetUsers = collect([$kepala]);
                }
            }
            if ($targetUsers->isNotEmpty()) {
                Notification::make()
                    ->title('Pengajuan Surat Baru Masuk')
                    ->body('Terdapat permohonan surat baru dari mahasiswa: ' . ($surat->pengirim_nama ?? 'Mahasiswa') . ' (' . $surat->perihal . ')')
                    ->info()
                    ->viewData([
                        'unit_kerja_id' => (int) $targetUnitId,
                        'surat_id'      => $surat->id,
                    ])
                    ->sendToDatabase($targetUsers);
                app(WhatsAppNotificationService::class)->notifySuratMasuk($surat, $targetUsers);
            }
        }
        // 4. Kirim Konfirmasi WhatsApp Langsung ke Mahasiswa (jika no telepon ada)
        $phone = $surat->pengirim_metadata['telp'] ?? null;
        if (!empty($phone)) {
            $appUrl = rtrim(config('app.url', config('app.asset_url', url('/'))), '/');
            $namaMhs = $surat->pengirim_nama ?? 'Mahasiswa';
            $pesan = "*SIMAS: Konfirmasi Pengajuan Surat*\n"
                . "Halo, *{$namaMhs}*!\n\n"
                . "Permohonan surat Anda telah berhasil kami terima dengan rincian:\n"
                . "• *Kode Lacak*: *{$surat->tracking_code}*\n"
                . "• *Perihal*: {$surat->perihal}\n"
                . "• *Waktu Pengajuan*: " . now()->translatedFormat('d F Y H:i') . "\n\n"
                . "Anda dapat memantau status perkembangan permohonan melalui Dashboard SIMAS Anda atau melalui tautan:\n"
                . "🔗 {$appUrl}/lacak?code={$surat->tracking_code}\n\n"
                . "_Pesan otomatis dikirim oleh Sistem Informasi Manajemen Arsip dan Surat (SIMAS)._";
            app(FonnteService::class)->send($phone, $pesan);
        }
    }
    protected function getRedirectUrl(): string
    {
        return SimasDashboard::getUrl();
    }
}
