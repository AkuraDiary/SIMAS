<?php

namespace App\Policies;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SuratPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Surat $surat): bool
    {
        $unitId = $user->unit_kerja_id;

        // 1. Surat keluar unit sendiri
        if ($surat->unit_pengirim_id === $unitId) {
            return true;
        }

        // 2. Surat masuk langsung
        if ($surat->suratUnits()
            ->where('unit_kerja_id', $unitId)
            ->exists()
        ) {
            return true;
        }

        // 3. Surat via disposisi
        if ($surat->disposisis()
            ->where('unit_tujuan_id', $unitId)
            ->exists()
        ) {
            return true;
        }

        return false;
    }


    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Surat $surat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Surat $surat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Surat $surat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Surat $surat): bool
    {
        return false;
    }
}
