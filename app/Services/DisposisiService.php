<?php

namespace App\Services;
use App\Models\User;
use App\Models\Surat;
use App\Models\Disposisi;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

class DisposisiService
{
    public function createMany(
        Surat $surat,
        User $user,
        array $unitTujuanIds,
        array $payload
    ): void {
        $parent = $this->latestDisposisiForUnit($surat, $user->unit_kerja_id);

        foreach ($unitTujuanIds as $unitId) {
            if ($this->exists($surat, $unitId)) {
                throw new DomainException("Unit sudah menerima disposisi");
            }

            Disposisi::create([
                'surat_id' => $surat->id,
                'unit_tujuan_id' => $unitId,
                'user_pembuat_id' => $user->id,
                'jenis_instruksi' => $payload['jenis_instruksi'],
                'sifat' => $payload['sifat'],
                'catatan' => $payload['catatan'],
                'status_disposisi' => 'BARU',
                'tanggal_disposisi' => now(),
                'parent_disposisi_id' => $parent?->id,
            ]);
        }
    }

    public function respond(Surat $surat, int $unitId, array $payload): Disposisi
    {
        $disposisi = $this->latestDisposisiForUnit($surat, $unitId);

        if (! $disposisi) {
            throw new AuthorizationException();
        }

        $disposisi->update([
            'status_disposisi' => $payload['status'],
            'catatan' => $disposisi->catatan . "\n\nTindak lanjut: " . ($payload['catatan'] ?? '-'),
        ]);

        return $disposisi;
    }

    protected function exists(Surat $surat, int $unitId): bool
    {
        return Disposisi::where('surat_id', $surat->id)
            ->where('unit_tujuan_id', $unitId)
            ->exists();
    }

    protected function latestDisposisiForUnit(Surat $surat, int $unitId): ?Disposisi
    {
        return $surat->disposisis
            ->where('unit_tujuan_id', $unitId)
            ->sortByDesc('tanggal_disposisi')
            ->first();
    }
}
