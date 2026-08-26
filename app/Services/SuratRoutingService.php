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
                'status_surat' => 'TERKIRIM',
            ]);

            $formatGlobal = \App\Models\FormatNomorSurat::whereNull('unit_kerja_id')->where('is_active', true)->first();
            if ($formatGlobal && empty($surat->nomor_surat)) {
                $surat->update([
                    'nomor_surat' => $formatGlobal->generateNomorSurat($surat)
                ]);
            }

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

                if ($upj && $upj->pegawai && $upj->pegawai->user) {
                    $finalUnitTujuanId = $upj->unit_kerja_id;
                    // Boleh set user_aktor_id jika ingin mengunci hanya orang tersebut yg bisa acc
                    // $finalUserAktorId = $upj->pegawai->user->id;
                }
            }

            return SuratRiwayat::create([
                'surat_id'       => $surat->id,
                'parent_id'      => null,
                'unit_asal_id'   => $surat->unit_pengirim_id,
                'unit_tujuan_id' => $finalUnitTujuanId,
                'user_aktor_id'  => $finalUserAktorId,
                'status'         => 'MENUNGGU',
                'catatan'        => $catatan ?? '',
                'actioned_at'    => null,
            ]);
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
            $automatedNextUnitId = null;

            if (!empty($surat->approval_path) && is_array($surat->approval_path)) {
                // Temukan kita ada di index ke berapa
                $currentIndex = -1;
                $currentJabatanId = \App\Models\UserPegawaiJabatan::whereHas('pegawai', fn($q) => $q->where('user_id', $actor->id))
                    ->where('status_jabatan', 'AKTIF')
                    ->first()?->jabatan_id;

                foreach ($surat->approval_path as $index => $step) {
                    if ($step['jabatan_id'] == $currentJabatanId) {
                        $currentIndex = $index;
                        break;
                    }
                }

                // Jika ada step selanjutnya, arahkan ke sana!
                if ($currentIndex !== -1 && isset($surat->approval_path[$currentIndex + 1])) {
                    $nextStep = $surat->approval_path[$currentIndex + 1];
                    $upj = \App\Models\UserPegawaiJabatan::where('jabatan_id', $nextStep['jabatan_id'])
                        ->where('status_jabatan', 'AKTIF')
                        ->first();

                    if ($upj) {
                        $automatedNextUnitId = $upj->unit_kerja_id;
                    }
                }
            }

            // Tentukan tujuan akhir: override dengan automated route jika ada
            $finalNextUnitId = $automatedNextUnitId ?? $nextUnitTujuanId;
            $finalIsFinalStep = $isFinalStep || (!$finalNextUnitId);

            if ($finalIsFinalStep) {
                $newStatus = 'SELESAI';

                $formatGlobal = \App\Models\FormatNomorSurat::whereNull('unit_kerja_id')->where('is_active', true)->first();
                if ($formatGlobal && empty($surat->nomor_surat)) {
                    $surat->nomor_surat = $formatGlobal->generateNomorSurat($surat);
                }

                $surat->status_surat = $newStatus;
                $surat->save();

                // 4. Finalisasi: Render HTML ke PDF dan lampirkan ke Surat
                if ($surat->template_id) {
                    // Tarik HTML yang sudah di-inject dengan Nomor Surat dan TTD QR Code
                    $html = app(\App\Services\PlaceholderService::class)->renderHtml($surat->template, $surat->content ?? [], $surat);

                    // Render menggunakan DomPDF
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                    $pdf->setPaper('A4', 'portrait');
                    $pdfContent = $pdf->output();

                    $safeNomor = str_replace(['/', '\\'], '_', $surat->nomor_surat ?? 'Terbitan');
                    $fileName = 'Surat_Resmi_' . $safeNomor . '.pdf';

                    // Simpan sebagai media
                    $surat->addMediaFromString($pdfContent)
                        ->usingName('Dokumen Final Resmi')
                        ->usingFileName($fileName)
                        ->toMediaCollection('dokumen-final');
                }

                // 5. Jika ini balasan untuk Pengajuan, tutup Pengajuan dan Notifikasi pemohon!
                if ($surat->terbitan_for_surat_id) {
                    $pengajuan = \App\Models\Surat::find($surat->terbitan_for_surat_id);
                    if ($pengajuan) {
                        $pengajuan->update(['status_surat' => 'SELESAI']);

                        // Opsional: Kirim notifikasi sistem ke pembuat pengajuan awal
                        if ($pengajuan->user_pembuat_id) {
                            $targetUser = \App\Models\User::find($pengajuan->user_pembuat_id);
                            if ($targetUser) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Surat Terbitan Selesai')
                                    ->body('Pengajuan Anda telah diproses dan Surat Balasan/Rekomendasi telah diterbitkan.')
                                    ->success()
                                    ->sendToDatabase($targetUser);
                            }
                        }
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

            // Status surat tetap DIPROSES, karena belum mati/ditolak sepenuhnya
            return $surat->fresh();
        });
    }
}
