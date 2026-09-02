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

        $qrCodeType = $signatureData['qr_code_type'] ?? 'generate';
        $finalQrCodePath = null;

        // 1. Handle Uploaded External QR Code
        if ($qrCodeType === 'upload' && !empty($signatureData['custom_qr_code'])) {
            $finalQrCodePath = is_array($signatureData['custom_qr_code'])
                ? array_values($signatureData['custom_qr_code'])[0]
                : $signatureData['custom_qr_code'];
        }
        // 2. Handle Drawn Signature Base64 Conversion
        elseif ($qrCodeType === 'draw' && !empty($signatureData['drawn_signature'])) {
            $base64_image = $signatureData['drawn_signature'];
            if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $type)) {
                $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
                $type = strtolower($type[1]);

                if (in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                    $decoded_image = base64_decode($base64_image);
                    $fileName = 'signatures/drawn_' . $surat->id . '_' . $actor->id . '_' . time() . '.' . $type;

                    Storage::disk('public')->put($fileName, $decoded_image);
                    $finalQrCodePath = $fileName;
                }
            }
        }
        // 3. Handle Internal Verification QR Code Generation
        elseif ($qrCodeType === 'generate') {
            $verifyUrl = url('/verify/ttd/' . $surat->id . '/' . $actor->id);
            $qrCodeFileName = 'qr_' . $surat->id . '_' . $actor->id . '_' . time() . '.png';
            $qrPathAbsolute = storage_path('app/public/signatures/' . $qrCodeFileName);

            if (!file_exists(dirname($qrPathAbsolute))) {
                mkdir(dirname($qrPathAbsolute), 0755, true);
            }

            QrCode::format('png')->size(200)->margin(1)->generate($verifyUrl, $qrPathAbsolute);
            $finalQrCodePath = 'signatures/' . $qrCodeFileName;
        }

        // 4. Record to Database
        SuratTtd::create([
            'surat_id'         => $surat->id,
            'user_id'          => $actor->id,
            'tipe'             => $signatureType ?? 'UTAMA',
            'is_visible'       => true,
            'jabatan_saat_ttd' => $pegawaiJabatan?->jabatan?->nama_jabatan ?? 'Pejabat Berwenang',
            'unit_saat_ttd'    => $pegawaiJabatan?->unitKerja?->nama_unit ?? $surat->unitPengirim?->nama_unit ?? 'Unit Kerja',
            'placeholder_key'  => $placeholderKey,
            'qr_code_path'     => $finalQrCodePath,
            'signed_at'        => now(),
        ]);
    }
}