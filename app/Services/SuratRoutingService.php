<?php

namespace App\Services;

use App\Models\Surat;
use App\Models\SuratRiwayat;
use App\Models\SuratTtd;
use App\Models\User;
use App\Models\UserPegawaiJabatan;
use Illuminate\Support\Facades\DB;

class SuratRoutingService
{
    /**
     * Submit a draft letter into the approval workflow.
     * Creates the initial step in `surat_riwayats` and updates letter status to `DIPROSES`.
     */
    public function submitForApproval(Surat $surat, int $unitTujuanId, ?int $targetUserAktorId = null, ?string $catatan = null): SuratRiwayat
    {
        return DB::transaction(function () use ($surat, $unitTujuanId, $targetUserAktorId, $catatan) {
            $surat->update([
                'status_surat' => 'DIPROSES',
            ]);

            // [NEW] Automated Routing Engine
            $finalUnitTujuanId = $unitTujuanId;
            $finalUserAktorId = $targetUserAktorId;

            // Jika ada approval_path, paksa rute pertama ke Jabatan pertama di list!
            if (!empty($surat->approval_path) && is_array($surat->approval_path) && count($surat->approval_path) > 0) {
                $firstStep = $surat->approval_path[0];
                $jabatanId = $firstStep['jabatan_id'];

                // Cari user aktif yang sedang memegang jabatan ini
                $upj = \App\Models\UserPegawaiJabatan::with('pegawai.user')
                    ->where('jabatan_id', $jabatanId)
                    ->where('status_jabatan', 'AKTIF')
                    ->first();

                $automatedUnitId = $upj?->unit_kerja_id ?? \App\Models\Jabatan::find($jabatanId)?->unit_kerja_id;
                if ($automatedUnitId) {
                    $finalUnitTujuanId = $automatedUnitId;
                }
            }

            $firstRiwayat = SuratRiwayat::create([
                'surat_id'       => $surat->id,
                'parent_id'      => null,
                'unit_asal_id'   => $surat->unit_pengirim_id,
                'unit_tujuan_id' => $finalUnitTujuanId,
                'user_aktor_id'  => $finalUserAktorId,
                'status'         => 'MENUNGGU',
                'catatan'        => $catatan ?? '',
                'actioned_at'    => null,
            ]);

            // Kirim notifikasi ke penerima di unit langkah pertama
            $targetUsers = \App\Models\User::ofUnitKerja($finalUnitTujuanId)->get();
            if ($targetUsers->isNotEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->title('Permohonan Persetujuan Surat Masuk')
                    ->body("Surat '{$surat->perihal}' menunggu persetujuan / verifikasi Anda.")
                    ->info()
                    ->viewData([
                        'unit_kerja_id' => (int) $finalUnitTujuanId,
                        'surat_id'      => $surat->id,
                    ])
                    ->sendToDatabase($targetUsers);

                app(\App\Services\WhatsAppNotificationService::class)->notifySuratMasuk($surat, $targetUsers);
            }

            return $firstRiwayat;
        });
    }

    /**
     * Approve the current step, record signature (if applicable),
     * and either advance to next unit or finalize the letter (`SELESAI` / `TERBIT`).
     */
    public function approveStep(
        SuratRiwayat $currentRiwayat,
        User $actor,
        ?int $nextUnitTujuanId = null,
        ?int $nextUserAktorId = null,
        bool $isFinalStep = false,
        bool $isSignatureRequired = false,
        ?string $signatureType = 'UTAMA',
        ?string $catatan = null,
        ?array $signatureData = null
    ): Surat {
        return DB::transaction(function () use (
            $currentRiwayat,
            $actor,
            $nextUnitTujuanId,
            $nextUserAktorId,
            $isFinalStep,
            $isSignatureRequired,
            $signatureType,
            $catatan
        ) {
            $surat = $currentRiwayat->surat;

            // 1. Mark current step as DISETUJUI
            $currentRiwayat->update([
                'user_aktor_id' => $actor->id,
                'status'        => 'DISETUJUI',
                'catatan'       => $catatan ?? $currentRiwayat->catatan,
                'actioned_at'   => now(),
            ]);

            // 2. Record signature if required
            if ($isSignatureRequired) {
                // 2a. Cari placeholder_key dari approval_path
                $placeholderKey = null;
                $pegawaiJabatan = UserPegawaiJabatan::whereHas('pegawai', fn($q) => $q->where('user_id', $actor->id))
                    ->where('status_jabatan', 'AKTIF')
                    ->first();

                if (!empty($surat->approval_path) && is_array($surat->approval_path)) {
                    foreach ($surat->approval_path as $step) {
                        if (isset($step['jabatan_id']) && $step['jabatan_id'] == $pegawaiJabatan?->jabatan_id) {
                            $placeholderKey = $step['placeholder_key'] ?? null;
                            break;
                        }
                    }
                }
                // 2b. Serahkan urusan image processing & QR ke SignatureService!
                app(\App\Services\SignatureService::class)->processDigitalSignature(
                    $surat,
                    $actor,
                    $signatureData ?? [],
                    $placeholderKey,
                    $signatureType
                );
            }

            // 3. Advance to next step or mark as final
            // 3. Advance to next step or mark as final
            $automatedNextUnitId = null;
            $currentIndex = -1;

            if (!empty($surat->approval_path) && is_array($surat->approval_path)) {
                $totalSteps = count($surat->approval_path);

                // 3a. Cari index jabatan actor saat ini
                $userJabatanIds = \App\Models\UserPegawaiJabatan::whereHas('pegawai', fn($q) => $q->where('user_id', $actor->id))
                    ->where('status_jabatan', 'AKTIF')
                    ->pluck('jabatan_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();

                foreach ($surat->approval_path as $index => $step) {
                    $stepJabId = (int) ($step['jabatan_id'] ?? 0);
                    if (in_array($stepJabId, $userJabatanIds, true)) {
                        $currentIndex = $index;
                        break;
                    }
                }
                // Fallback jika jabatan tidak match langsung: hitung riwayat persetujuan SEBELUM langkah ini
                if ($currentIndex === -1) {
                    $approvedBefore = $surat->riwayats()
                        ->where('status', 'DISETUJUI')
                        ->where('id', '!=', $currentRiwayat->id)
                        ->count();
                    $currentIndex = min($approvedBefore, $totalSteps - 1);
                }
                // 3b. Jika masih ada langkah berikutnya, cari unit tujuan berikutnya
                if ($currentIndex < ($totalSteps - 1) && isset($surat->approval_path[$currentIndex + 1])) {
                    $nextStep = $surat->approval_path[$currentIndex + 1];
                    $nextJabId = (int) ($nextStep['jabatan_id'] ?? 0);
                    // Cari via UserPegawaiJabatan aktif, ATAU fallback langsung ke unit_kerja_id milik Jabatan
                    $upj = \App\Models\UserPegawaiJabatan::where('jabatan_id', $nextJabId)
                        ->where('status_jabatan', 'AKTIF')
                        ->first();
                    $automatedNextUnitId = $upj?->unit_kerja_id
                        ?? \App\Models\Jabatan::find($nextJabId)?->unit_kerja_id;
                }

                // Tentukan tujuan akhir: utamakan rute otomatis dari approval_path
                $finalNextUnitId = $automatedNextUnitId ?? $nextUnitTujuanId;
                // Surat HANYA final jika:
                // 1. Parameter $isFinalStep bernilai true (klik Setujui & Selesai), ATAU
                // 2. Alur terstruktur sudah berada di langkah terakhir ($currentIndex >= total - 1)
                // KECUALI jika caller secara eksplisit meneruskan ($isFinalStep === false) dan masih ada unit berikutnya!
                if (!empty($surat->approval_path) && is_array($surat->approval_path)) {
                    $isLastPathStep = ($currentIndex >= ($totalSteps - 1));
                    if ($isFinalStep) {
                        $finalIsFinalStep = true;
                    } else {
                        // Jika user klik "Lanjutkan", hanya final jika benar-benar langkah terakhir DAN tidak ada unit berikutnya
                        $finalIsFinalStep = $isLastPathStep && (!$finalNextUnitId);
                    }
                } else {
                    $finalIsFinalStep = $isFinalStep || (!$finalNextUnitId);
                }


                if ($finalIsFinalStep) {
                    if (!empty($surat->nomor_surat)) {
                        $newStatus = 'SELESAI';
                        $surat->status_surat = $newStatus;
                        $surat->save();

                        // Finalisasi: Render HTML ke PDF dan lampirkan ke Surat
                        if ($surat->template_id) {
                            $html = app(\App\Services\PlaceholderService::class)->renderHtml($surat->template, $surat->content ?? [], $surat);
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'portrait');
                            $pdfContent = $pdf->output();

                            $safeNomor = str_replace(['/', '\\'], '_', $surat->nomor_surat);
                            $fileName = 'Surat_Utama_' . $safeNomor . '.pdf';

                            $surat->addMediaFromString($pdfContent)
                                ->usingName('Dokumen Final Resmi')
                                ->usingFileName($fileName)
                                ->toMediaCollection('dokumen-final');
                        }

                        // Jika ini balasan untuk Pengajuan, tutup Pengajuan dan Notifikasi pemohon!
                        if ($surat->terbitan_for_surat_id) {
                            $pengajuan = \App\Models\Surat::find($surat->terbitan_for_surat_id);
                            if ($pengajuan) {
                                $pengajuan->update(['status_surat' => 'SELESAI']);

                                if ($pengajuan->user_pembuat_id) {
                                    $targetUser = \App\Models\User::find($pengajuan->user_pembuat_id);
                                    if ($targetUser) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Surat Terbitan Selesai')
                                            ->body('Pengajuan Anda telah diproses dan Surat Balasan/Rekomendasi telah diterbitkan.')
                                            ->success()
                                            ->viewData([
                                                'unit_kerja_id' => (int) ($pengajuan->unit_pengirim_id ?? $surat->unit_pengirim_id),
                                                'surat_id'      => $surat->id,
                                            ])
                                            ->sendToDatabase($targetUser);

                                        app(\App\Services\WhatsAppNotificationService::class)->notifySuratSelesai(
                                            $surat,
                                            $targetUser,
                                            'Pengajuan Anda telah diproses dan Surat Balasan/Rekomendasi telah diterbitkan.'
                                        );
                                    }
                                }
                            }
                        } else {
                            if ($surat->pembuat) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Surat Selesai & Disetujui')
                                    ->body("Surat '{$surat->perihal}' telah selesai disetujui.")
                                    ->success()
                                    ->viewData([
                                        'unit_kerja_id' => (int) $surat->unit_pengirim_id,
                                        'surat_id'      => $surat->id,
                                    ])
                                    ->sendToDatabase($surat->pembuat);

                                app(\App\Services\WhatsAppNotificationService::class)->notifySuratSelesai(
                                    $surat,
                                    $surat->pembuat,
                                    $catatan
                                );
                            }
                        }
                    } else {
                        // Persetujuan pimpinan tuntas, menunggu penomoran oleh Staf TU
                        if ($surat->pembuat) {
                            \Filament\Notifications\Notification::make()
                                ->title('Persetujuan Tuntas - Menunggu Penomoran')
                                ->body("Surat '{$surat->perihal}' telah selesai disetujui dan menunggu penetapan nomor surat resmi oleh Staf TU.")
                                ->info()
                                ->viewData([
                                    'unit_kerja_id' => (int) $surat->unit_pengirim_id,
                                    'surat_id'      => $surat->id,
                                ])
                                ->sendToDatabase($surat->pembuat);
                        }
                    }
                } else {
                    SuratRiwayat::create([
                        'surat_id'       => $surat->id,
                        'parent_id'      => $currentRiwayat->id,
                        'unit_asal_id'   => $currentRiwayat->unit_tujuan_id,
                        'unit_tujuan_id' => $finalNextUnitId,
                        'user_aktor_id'  => $nextUserAktorId, // biarkan null jika tak dikunci
                        'status'         => 'MENUNGGU',
                        'catatan'        => 'Diteruskan untuk proses persetujuan (Otomatis).',
                        'actioned_at'    => null,
                    ]);

                    // Notifikasi Surat Masuk ke Unit Selanjutnya
                    $nextUnitUsers = \App\Models\User::ofUnitKerja($finalNextUnitId)->get();
                    if ($nextUnitUsers->isNotEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Surat Masuk Baru')
                            ->body("Ada surat masuk baru dari " . ($surat->unitPengirim?->nama_unit ?? 'Unit Sebelumnya') . ": " . $surat->perihal)
                            ->info()
                            ->viewData([
                                'unit_kerja_id' => (int) $finalNextUnitId,
                                'surat_id'      => $surat->id,
                            ])
                            ->sendToDatabase($nextUnitUsers);

                        app(\App\Services\WhatsAppNotificationService::class)->notifySuratMasuk($surat, $nextUnitUsers, $catatan);
                    }
                }
            }

            return $surat->fresh();
        });
    }

    /**
     * Reject or request revision for the current step.
     */
    public function rejectOrReviseStep(
        SuratRiwayat $currentRiwayat,
        User $actor,
        string $newStatus, // 'REVISI' or 'DITOLAK'
        string $catatan
    ): Surat {
        return DB::transaction(function () use ($currentRiwayat, $actor, $newStatus, $catatan) {
            $surat = $currentRiwayat->surat;

            $currentRiwayat->update([
                'user_aktor_id' => $actor->id,
                'status'        => $newStatus,
                'catatan'       => $catatan,
                'actioned_at'   => now(),
            ]);

            $surat->update([
                'status_surat' => $newStatus,
            ]);

            if ($newStatus === 'REVISI') {
                if ($surat->pembuat) {
                    \Filament\Notifications\Notification::make()
                        ->title('Surat Perlu Revisi')
                        ->body("Surat '{$surat->perihal}' dikembalikan untuk direvisi: {$catatan}")
                        ->warning()
                        ->viewData([
                            'unit_kerja_id' => (int) $surat->unit_pengirim_id,
                            'surat_id'      => $surat->id,
                        ])
                        ->sendToDatabase($surat->pembuat);

                    app(\App\Services\WhatsAppNotificationService::class)->notifySuratRevisi($surat, $actor, $catatan);
                }
            } elseif ($newStatus === 'DITOLAK') {
                if ($surat->pembuat) {
                    \Filament\Notifications\Notification::make()
                        ->title('Surat Ditolak')
                        ->body("Surat '{$surat->perihal}' telah ditolak: {$catatan}")
                        ->danger()
                        ->viewData([
                            'unit_kerja_id' => (int) $surat->unit_pengirim_id,
                            'surat_id'      => $surat->id,
                        ])
                        ->sendToDatabase($surat->pembuat);

                    app(\App\Services\WhatsAppNotificationService::class)->notifySuratDitolak($surat, $actor, $catatan);
                }
            }

            return $surat->fresh();
        });
    }

    /**
     * Meneruskan surat tanpa memberikan persetujuan / TTD.
     */
    public function forwardStep(
        SuratRiwayat $currentRiwayat,
        User $actor,
        int $nextUnitTujuanId,
        ?string $catatan = null
    ): Surat {
        return DB::transaction(function () use ($currentRiwayat, $actor, $nextUnitTujuanId, $catatan) {
            $surat = $currentRiwayat->surat;

            // Mark current step as DITERUSKAN
            $currentRiwayat->update([
                'user_aktor_id' => $actor->id,
                'status'        => 'DITERUSKAN',
                'catatan'       => $catatan ?? 'Diteruskan ke unit selanjutnya',
                'actioned_at'   => now(),
            ]);

            // Create next step in chain
            SuratRiwayat::create([
                'surat_id'       => $surat->id,
                'parent_id'      => $currentRiwayat->id,
                'unit_asal_id'   => $currentRiwayat->unit_tujuan_id,
                'unit_tujuan_id' => $nextUnitTujuanId,
                'user_aktor_id'  => null,
                'status'         => 'MENUNGGU',
                'catatan'        => 'Diteruskan untuk diproses.',
                'actioned_at'    => null,
            ]);

            // Notifikasi Surat Masuk ke Unit Tujuan Selanjutnya
            $nextUnitUsers = \App\Models\User::ofUnitKerja($nextUnitTujuanId)->get();
            if ($nextUnitUsers->isNotEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->title('Surat Masuk Baru')
                    ->body("Ada surat diteruskan dari " . ($surat->unitPengirim?->nama_unit ?? 'Unit Sebelumnya') . ": " . $surat->perihal)
                    ->info()
                    ->viewData([
                        'unit_kerja_id' => (int) $nextUnitTujuanId,
                        'surat_id'      => $surat->id,
                    ])
                    ->sendToDatabase($nextUnitUsers);

                app(\App\Services\WhatsAppNotificationService::class)->notifySuratMasuk($surat, $nextUnitUsers, $catatan);
            }

            return $surat->fresh();
        });
    }

    /**
     * Kembalikan ke Langkah Sebelumnya (Step-back).
     */
    public function returnStep(
        SuratRiwayat $currentRiwayat,
        User $actor,
        string $catatan
    ): Surat {
        return DB::transaction(function () use ($currentRiwayat, $actor, $catatan) {
            $surat = $currentRiwayat->surat;

            $currentRiwayat->update([
                'user_aktor_id' => $actor->id,
                'status'        => 'DIKEMBALIKAN',
                'catatan'       => $catatan,
                'actioned_at'   => now(),
            ]);

            // Create a new step routing it BACK to the unit that sent it to us
            SuratRiwayat::create([
                'surat_id'       => $surat->id,
                'parent_id'      => $currentRiwayat->id,
                'unit_asal_id'   => $currentRiwayat->unit_tujuan_id,
                'unit_tujuan_id' => $currentRiwayat->unit_asal_id, // Pantulkan kembali ke pengirim sebelumnya
                'user_aktor_id'  => null,
                'status'         => 'MENUNGGU',
                'catatan'        => 'Dikembalikan dengan catatan: ' . $catatan,
                'actioned_at'    => null,
            ]);

            // Notifikasi ke unit sebelumnya bahwa surat dikembalikan
            $prevUnitUsers = \App\Models\User::ofUnitKerja($currentRiwayat->unit_asal_id)->get();
            if ($prevUnitUsers->isNotEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->title('Surat Dikembalikan')
                    ->body("Surat '{$surat->perihal}' dikembalikan ke unit Anda dengan catatan: {$catatan}")
                    ->warning()
                    ->viewData([
                        'unit_kerja_id' => (int) $currentRiwayat->unit_asal_id,
                        'surat_id'      => $surat->id,
                    ])
                    ->sendToDatabase($prevUnitUsers);

                app(\App\Services\WhatsAppNotificationService::class)->notifySuratMasuk($surat, $prevUnitUsers, "Surat dikembalikan: {$catatan}");
            }

            // Notifikasi ke pembuat bahwa surat perlu revisi
            if ($surat->pembuat) {
                app(\App\Services\WhatsAppNotificationService::class)->notifySuratRevisi($surat, $actor, $catatan);
            }

            // Status surat tetap DIPROSES, karena belum mati/ditolak sepenuhnya
            return $surat->fresh();
        });
    }
}
