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

            $formatGlobal = \App\Models\FormatNomorSurat::whereNull('unit_kerja_id')->where('is_active', true)->first();
            if ($formatGlobal && empty($surat->nomor_surat)) {
                $surat->update([
                    'nomor_surat' => $formatGlobal->generateNomorSurat($surat)
                ]);
            }

            return SuratRiwayat::create([
                'surat_id'       => $surat->id,
                'parent_id'      => null,
                'unit_asal_id'   => $surat->unit_pengirim_id,
                'unit_tujuan_id' => $unitTujuanId,
                'user_aktor_id'  => $targetUserAktorId,
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
        ?string $catatan = null
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
                // Get active jabatan for snapshot
                $pegawaiJabatan = UserPegawaiJabatan::with(['jabatan', 'unitKerja'])
                    ->whereHas('pegawai', fn($q) => $q->where('user_id', $actor->id))
                    ->where('status_jabatan', 'AKTIF')
                    ->first();

                SuratTtd::create([
                    'surat_id'         => $surat->id,
                    'user_id'          => $actor->id,
                    'tipe'             => $signatureType ?? 'UTAMA',
                    'is_visible'       => true,
                    'jabatan_saat_ttd' => $pegawaiJabatan?->jabatan?->nama_jabatan ?? 'Pejabat Berwenang',
                    'unit_saat_ttd'    => $pegawaiJabatan?->unitKerja?->nama_unit ?? $surat->unitPengirim?->nama_unit ?? 'Unit Kerja',
                    'signed_at'        => now(),
                ]);
            }

            // 3. Advance to next step or mark as final
            if ($isFinalStep || !$nextUnitTujuanId) {
                $newStatus = 'SELESAI';//($surat->tipe_surat === 'PENGAJUAN') ? 'TERBIT' : 'SELESAI';

                $formatGlobal = \App\Models\FormatNomorSurat::whereNull('unit_kerja_id')->where('is_active', true)->first();
                if ($formatGlobal && empty($surat->nomor_surat)) {
                    $surat->nomor_surat = $formatGlobal->generateNomorSurat($surat);
                }

                $surat->status_surat = $newStatus;
                $surat->save();
            } else {
                // Create next step in approval chain
                SuratRiwayat::create([
                    'surat_id'       => $surat->id,
                    'parent_id'      => $currentRiwayat->id,
                    'unit_asal_id'   => $currentRiwayat->unit_tujuan_id,
                    'unit_tujuan_id' => $nextUnitTujuanId,
                    'user_aktor_id'  => $nextUserAktorId,
                    'status'         => 'MENUNGGU',
                    'catatan'        => 'Diteruskan untuk proses persetujuan selanjutnya.',
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
}
