<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\SuratTtd;
use App\Models\User;

class TtdVerificationController extends Controller
{

    /**
     * Verifikasi keabsahan dokumen dan tanda tangan digital via QR Code publik.
     */
    public function verify(string $suratId, string $userId)
    {
        // Decode jika ID menggunakan Hashids atau angka asli
        $realSuratId = is_numeric($suratId) ? (int) $suratId : (\Vinkla\Hashids\Facades\Hashids::decode($suratId)[0] ?? null);
        $realUserId  = is_numeric($userId) ? (int) $userId : (\Vinkla\Hashids\Facades\Hashids::decode($userId)[0] ?? null);
        $surat = Surat::with(['unitPengirim', 'template', 'terbitans'])->find($realSuratId);
        $user  = User::with(['pegawai'])->find($realUserId);
        // Cari riwayat tanda tangan yang cocok
        $ttd = null;
        if ($surat && $user) {
            $ttd = SuratTtd::where('surat_id', $surat->id)
                ->where('user_id', $user->id)
                ->latest('signed_at')
                ->first();
        }
        $isValid = ($surat !== null && $user !== null && $ttd !== null && in_array($surat->status_surat, ['SELESAI', 'TERKIRIM', 'DIPROSES']));
        return view('verify-ttd', [
            'isValid' => $isValid,
            'surat'   => $surat,
            'user'    => $user,
            'ttd'     => $ttd,
        ]);
    }
    /**
     * Download dokumen final dari halaman verifikasi (jika dokumen sudah SELESAI).
     */
    public function downloadDokumen(Surat $surat)
    {
        $media = $surat->getFirstMedia('dokumen-final');
        if ($media && file_exists($media->getPath())) {
            return response()->download($media->getPath(), $media->file_name);
        }
        abort(404, 'Dokumen resmi belum tersedia untuk diunduh.');
    }
}
