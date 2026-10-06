<?php

namespace App\Services;

use App\Models\Surat;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\UserPegawaiJabatan;
use Illuminate\Database\Eloquent\Builder;

class UnitAksesService
{
    /**
     * Apply letter visibility filters for a user viewing Surat Masuk in a specific unit.
     */
    public function applySuratMasukFilter(Builder $query, ?User $user, ?int $unitId): Builder
    {
        if (!$user || !$unitId) {
            return $query->whereRaw('1 = 0');
        }

        // If user can view all letters in this unit (Kepala Unit, Admin, or granted full access)
        if ($user->canViewAllSuratMasukUnit($unitId)) {
            return $query->untukUnit($unitId);
        }

        $activeJabatanId = $user->getActiveJabatan()?->id;

        // Restricted Mode (Disposisi & Workflow yang ditugaskan ke user/unit):
        return $query
            ->where('status_surat', '<>', 'DRAFT')
            ->where(function (Builder $q) use ($unitId, $user, $activeJabatanId) {
                // 1. Letters dispositioned to this unit (khususnya yang ditujukan ke jabatan user atau dibuat user)
                $q->whereHas('disposisis', function (Builder $dq) use ($unitId, $user, $activeJabatanId) {
                    $dq->where('unit_tujuan_id', $unitId)
                        ->where(function (Builder $sub) use ($user, $activeJabatanId) {
                            $sub->whereNull('user_pegawai_jabatan_id')
                                ->orWhere('user_pegawai_jabatan_id', $activeJabatanId)
                                ->orWhere('user_pembuat_id', $user->id);
                        });
                })
                    // 2. Letters in workflow for this unit (actively waiting or approved)
                    ->orWhereHas('riwayats', function (Builder $rw) use ($unitId, $user) {
                        $rw->where('unit_tujuan_id', $unitId)
                            ->whereIn('status', ['MENUNGGU', 'DISETUJUI'])
                            ->where(function ($s) use ($user) {
                                $s->whereNull('user_aktor_id')
                                    ->orWhere('user_aktor_id', $user->id);
                            });
                    });
            });
    }

    /**
     * Apply letter visibility filters for a user viewing Surat Keluar in a specific unit.
     */
    public function applySuratKeluarFilter(Builder $query, ?User $user, ?int $unitId): Builder
    {
        if (!$user || !$unitId) {
            return $query->whereRaw('1 = 0');
        }

        // Pimpinan Unit, Admin, atau Staf dengan izin akses penuh dapat melihat seluruh surat keluar unit
        if ($user->tipe_entitas === 'ADMIN' || $user->canViewAllSuratMasukUnit($unitId)) {
            return $query->where(function (Builder $q) use ($unitId) {
                $q->where('unit_pengirim_id', $unitId)
                    ->orWhereHas('disposisis', function ($dq) use ($unitId) {
                        $dq->whereHas('userPegawaiJabatan', function ($qJabatan) use ($unitId) {
                            $qJabatan->where('unit_kerja_id', $unitId);
                        });
                    });
            });
        }

        $activeJabatanId = $user->getActiveJabatan()?->id;

        // Staf biasa: dibatasi hanya melihat surat keluar yang dibuat dirinya sendiri atau yang melibatkan dirinya
        return $query->where(function (Builder $q) use ($unitId, $user, $activeJabatanId) {
            $q->where(function (Builder $sub) use ($unitId, $user) {
                $sub->where('unit_pengirim_id', $unitId)
                    ->where(function (Builder $inner) use ($user) {
                        $inner->where('user_pembuat_id', $user->id)
                            ->orWhereHas('suratTtds', fn($t) => $t->where('user_id', $user->id))
                            ->orWhereHas('riwayats', fn($r) => $r->where('user_aktor_id', $user->id));
                    });
            })
                ->orWhereHas('disposisis', function (Builder $dq) use ($unitId, $user, $activeJabatanId) {
                    $dq->where('unit_tujuan_id', $unitId)
                        ->where(function (Builder $sub) use ($user, $activeJabatanId) {
                            $sub->where('user_pegawai_jabatan_id', $activeJabatanId)
                                ->orWhere('user_pembuat_id', $user->id);
                        });
                });
        });
    }

    /**
     * Apply letter visibility filters for a user viewing Arsip Surat in a specific unit.
     */
    public function applyArsipFilter(Builder $query, ?User $user, ?int $unitId): Builder
    {
        if (!$user || !$unitId) {
            return $query->whereRaw('1 = 0');
        }

        // Must be archived by this unit
        $query->whereHas('arsipSurats', fn($q) => $q->where('unit_kerja_id', $unitId));

        // If user can view all letters in this unit (Kepala Unit, Admin, or staff granted full access)
        if ($user->canViewAllSuratMasukUnit($unitId)) {
            return $query;
        }

        $activeJabatanId = $user->getActiveJabatan()?->id;

        // Restricted staff: only view archived letters they were authorized to see
        return $query->where(function (Builder $q) use ($unitId, $user, $activeJabatanId) {
            $q->where('unit_pengirim_id', $unitId)
                ->orWhereHas('disposisis', function (Builder $dq) use ($unitId, $user, $activeJabatanId) {
                    $dq->where('unit_tujuan_id', $unitId)
                        ->where(function (Builder $sub) use ($user, $activeJabatanId) {
                            $sub->whereNull('user_pegawai_jabatan_id')
                                ->orWhere('user_pegawai_jabatan_id', $activeJabatanId)
                                ->orWhere('user_pembuat_id', $user->id);
                        });
                })
                ->orWhere(function (Builder $sq) use ($unitId, $user) {
                    $sq->where('unit_pengirim_id', $unitId)
                        ->where('user_pembuat_id', $user->id);
                })
                ->orWhereHas('riwayats', function (Builder $rq) use ($unitId, $user) {
                    $rq->where('unit_tujuan_id', $unitId)
                        ->where('user_aktor_id', $user->id);
                });
        });
    }

    /**
     * Check if a specific user has permission to open and view a particular Surat.
     */
    public function canUserAccessSurat(?User $user, Surat $surat, ?int $unitId): bool
    {
        if (!$user || !$unitId) {
            return false;
        }

        if ($user->tipe_entitas === 'ADMIN') {
            return true;
        }

        if ($user->canViewAllSuratMasukUnit($unitId)) {
            return true;
        }
        // Izinkan jika unit user adalah unit pemohon dari pengajuan yang dibalas oleh surat terbitan ini
        if ($surat->terbitan_for_surat_id) {
            $pengajuan = $surat->terbitanForSurat;
            if ($pengajuan && ($pengajuan->unit_asal_id === $unitId || $pengajuan->unit_pengirim_id === $unitId)) {
                return true;
            }
        }
        // If this unit is the sender unit:
        if ($surat->unit_pengirim_id === $unitId) {
            if ($surat->user_pembuat_id === $user->id) {
                return true;
            }
            if ($surat->suratTtds()->where('user_id', $user->id)->exists()) {
                return true;
            }
            if ($surat->riwayats()->where('user_aktor_id', $user->id)->exists()) {
                return true;
            }
            return false;
        }

        $activeJabatanId = $user->getActiveJabatan()?->id;

        // Check if there is a disposisi for this unit that covers this user
        $hasDisposisi = $surat->disposisis()
            ->where('unit_tujuan_id', $unitId)
            ->where(function ($q) use ($user, $activeJabatanId) {
                $q->whereNull('user_pegawai_jabatan_id')
                    ->orWhere('user_pegawai_jabatan_id', $activeJabatanId)
                    ->orWhere('user_pembuat_id', $user->id);
            })
            ->exists();

        if ($hasDisposisi) {
            return true;
        }

        // Check if user is in the riwayat approval chain
        $hasRiwayat = $surat->riwayats()
            ->where('unit_tujuan_id', $unitId)
            ->where('user_aktor_id', $user->id)
            ->exists();

        return $hasRiwayat;
    }

    /**
     * Update unit-level inbox policy settings.
     */
    public function updateUnitSettings(UnitKerja $unit, array $settings): void
    {
        $current = $unit->pengaturan_akses ?? [];
        $unit->pengaturan_akses = array_merge($current, $settings);
        $unit->save();
    }

    /**
     * Update staff member permissions within the unit.
     */
    public function updateStaffPermissions(int $userPegawaiJabatanId, array $permissions): void
    {
        UserPegawaiJabatan::where('id', $userPegawaiJabatanId)->update([
            'akses_surat_masuk' => $permissions['akses_surat_masuk'] ?? 'DEFAULT',
            'can_disposisi'     => (bool) ($permissions['can_disposisi'] ?? false),
        ]);
    }
}
