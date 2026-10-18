<?php

namespace App\Services;

use App\Models\Surat;
use App\Models\SuratTtd;
use App\Models\User;
use App\Models\UserPegawaiJabatan;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SignatureService
{
    /**
     * Process and record a digital signature or QR Code for a letter.
     */
    public function processDigitalSignature(
        Surat $surat,
        User $actor,
        array $signatureData,
        ?string $placeholderKey,
        ?string $signatureType
    ): void {
        $pegawaiJabatan = UserPegawaiJabatan::with(['jabatan', 'unitKerja'])
            ->whereHas('pegawai', fn($q) => $q->where('user_id', $actor->id))
            ->where('status_jabatan', 'AKTIF')
            ->first();

        $isVisible = array_key_exists('is_visible', $signatureData) ? (bool) $signatureData['is_visible'] : true;
        $showName = array_key_exists('tampilkan_nama_ttd', $signatureData) ? (bool) $signatureData['tampilkan_nama_ttd'] : true;
        $qrCodeType = $signatureData['qr_code_type'] ?? 'generate';
        $finalQrCodePath = null;
        $extraDrawnPath = null;

        if ($isVisible) {
            // 1. Handle Uploaded External QR Code / TTD
            if ($qrCodeType === 'upload' && !empty($signatureData['custom_qr_code'])) {
                $finalQrCodePath = is_array($signatureData['custom_qr_code'])
                    ? array_values($signatureData['custom_qr_code'])[0]
                    : $signatureData['custom_qr_code'];
            }
            // 2. Handle Drawn Signature (for 'draw' or 'both')
            if (in_array($qrCodeType, ['draw', 'both'], true) && !empty($signatureData['drawn_signature'])) {
                $base64_image = $signatureData['drawn_signature'];
                if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
                    $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
                    $type = strtolower($type[1]);

                    if (in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                        $decoded_image = base64_decode($base64_image);
                        $fileName = 'signatures/drawn_' . $surat->id . '_' . $actor->id . '_' . time() . '.' . $type;

                        Storage::disk('private')->put($fileName, $decoded_image);
                        if ($qrCodeType === 'draw') {
                            $finalQrCodePath = $fileName;
                        } else {
                            $extraDrawnPath = $fileName;
                        }
                    }
                }
            }
            // 3. Handle Internal Verification QR Code Generation (for 'generate' or 'both')
            if (in_array($qrCodeType, ['generate', 'both'], true)) {
                $verifyUrl = url('/verify/ttd/' . $surat->id . '/' . $actor->id);
                $qrCodeFileName = 'qr_' . $surat->id . '_' . $actor->id . '_' . time() . '.png';
                $qrPathAbsolute = storage_path('app/private/signatures/' . $qrCodeFileName);

                if (!file_exists(dirname($qrPathAbsolute))) {
                    mkdir(dirname($qrPathAbsolute), 0755, true);
                }

                QrCode::format('png')->size(200)->margin(1)->generate($verifyUrl, $qrPathAbsolute);
                $finalQrCodePath = 'signatures/' . $qrCodeFileName;
            }
        }

        // Ambil koordinat yang mungkin sudah digeser oleh penyetuju di preview DetailSurat
        $content = $surat->content ?? [];
        $posX = $placeholderKey ? ($content[$placeholderKey . '_posisi_x'] ?? 0) : 0;
        $posY = $placeholderKey ? ($content[$placeholderKey . '_posisi_y'] ?? 0) : 0;

        if ($placeholderKey) {
            $content[$placeholderKey . '_show_name'] = $showName;
            $content[$placeholderKey . '_mode'] = $qrCodeType;
            if ($extraDrawnPath) {
                $content[$placeholderKey . '_ttd_path'] = $extraDrawnPath;
            }
            $surat->update(['content' => $content]);
        }

        // 4. Record to Database (Update jika sudah ada untuk placeholder_key yang sama)
        SuratTtd::updateOrCreate(
            [
                'surat_id'        => $surat->id,
                'placeholder_key' => $placeholderKey,
                'user_id'         => $actor->id,
            ],
            [
                'tipe'             => $signatureType ?? 'UTAMA',
                'is_visible'       => $isVisible,
                'jabatan_saat_ttd' => $pegawaiJabatan?->jabatan?->nama_jabatan ?? 'Pejabat Berwenang',
                'unit_saat_ttd'    => $pegawaiJabatan?->unitKerja?->nama_unit ?? $surat->unitPengirim?->nama_unit ?? 'Unit Kerja',
                'qr_code_path'     => $finalQrCodePath,
                'posisi_x'         => (float) $posX,
                'posisi_y'         => (float) $posY,
                'signed_at'        => now(),
            ]
        );
    }
}
