<?php

namespace App\Services;
use App\Models\Surat;

class SuratStatusService
{
    public function sync(Surat $surat): void
    {
        if ($surat->disposisis->isEmpty()) {
            return;
        }

        $allDone = $surat->disposisis
            ->every(fn ($d) => $d->status_disposisi === 'SELESAI');

        $surat->update([
            'status_surat' => $allDone ? 'SELESAI' : 'DIPROSES',
        ]);
    }
}
