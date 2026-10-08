<?php

namespace App\Policies;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SuratPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->tipe_entitas === 'ADMIN') {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->tipe_entitas, ['ADMIN', 'STAF', 'MAHASISWA']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Surat $surat): bool
    {
        $unitId = $user->unit_kerja_id;

        // 1. Surat keluar unit sendiri
        if ($unitId && $surat->unit_pengirim_id === $unitId) {
            return true;
        }

        // 2. Surat masuk langsung
        if (
            $unitId && $surat->suratUnits()
            ->where('unit_kerja_id', $unitId)
            ->exists()
        ) {
            return true;
        }

        // 3. Surat via disposisi
        if (
            $unitId && $surat->disposisis()
            ->where('unit_tujuan_id', $unitId)
            ->exists()
        ) {
            return true;
        }

        // 4. Surat dalam riwayat approval / workflow unit ini
        if (
            $unitId && $surat->riwayats()
            ->where(function ($q) use ($unitId) {
                $q->where('unit_tujuan_id', $unitId)
                    ->orWhere('unit_asal_id', $unitId);
            })
            ->exists()
        ) {
            return true;
        }

        // 5. Cek izin akses unit melalui UnitAksesService
        if ($unitId && app(\App\Services\UnitAksesService::class)->canUserAccessSurat($user, $surat, $unitId)) {
            return true;
        }

        // 6. Mahasiswa melihat surat mereka sendiri
        if ($user->tipe_entitas === 'MAHASISWA' && $surat->user_pembuat_id === $user->id) {
            return true;
        }

        return false;
    }


    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->tipe_entitas === 'STAF') {
            return !empty($user->unit_kerja_id);
        }

        return in_array($user->tipe_entitas, ['STAF', 'MAHASISWA']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Surat $surat): bool
    {
        if ($user->tipe_entitas === 'MAHASISWA') {
            return $surat->user_pembuat_id === $user->id && in_array($surat->status_surat, ['DRAFT', 'REVISI']);
        }
        if ($user->tipe_entitas === 'STAF') {
            // Surat yang sudah selesai atau ditolak permanen tidak boleh diubah lagi
            if (in_array($surat->status_surat, ['SELESAI', 'DITOLAK', 'DIARSIPKAN'])) {
                return false;
            }
            $unitId = $user->unit_kerja_id;
            // 1. Pembuat naskah atau unit pengirim saat DRAFT atau REVISI
            if (($surat->unit_pengirim_id === $unitId || $surat->user_pembuat_id === $user->id)
                && in_array($surat->status_surat, ['DRAFT', 'REVISI'])
            ) {
                return true;
            }
            // 2. Pejabat / Pihak yang saat ini memegang giliran review aktif saat mode revisi atau dikembalikan
            $hasActiveReview = $surat->riwayats()
                ->where('status', 'MENUNGGU')
                ->where('unit_tujuan_id', $unitId)
                ->exists();
            if ($hasActiveReview) {
                $isRevisiOrDikembalikan = $surat->status_surat === 'REVISI'
                    || $surat->riwayats()->where('unit_tujuan_id', $unitId)->whereIn('status', ['DIKEMBALIKAN', 'REVISI'])->exists();
                if ($isRevisiOrDikembalikan) {
                    return true;
                }
            }

            // 3. Fallback jika masih draft bagi unit pengirim
            if ($surat->status_surat === 'DRAFT') {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Surat $surat): bool
    {
        if ($user->tipe_entitas === 'MAHASISWA' && $surat->user_pembuat_id === $user->id) {
            return true;
        }
        return $user->tipe_entitas === 'STAF';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Surat $surat): bool
    {

        return $user->tipe_entitas === 'STAF';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Surat $surat): bool
    {

        return $user->tipe_entitas === 'STAF';
    }
}
