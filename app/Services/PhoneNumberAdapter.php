<?php

namespace App\Services;

class PhoneNumberAdapter
{
    /**
     * Normalisasi nomor telepon ke format standar WhatsApp internasional Indonesia (628xxxxxxxxxx).
     *
     * Menangani format:
     * - "081234567890"       -> "6281234567890"
     * - "+6281234567890"      -> "6281234567890"
     * - "6281234567890"       -> "6281234567890"
     * - "81234567890"         -> "6281234567890"
     * - "+62 812-3456-7890"   -> "6281234567890"
     * - "+62081234567890"     -> "6281234567890"
     * - "(0812) 3456 7890"    -> "6281234567890"
     *
     * @param string|null $phone
     * @return string|null Mengembalikan nomor dalam format 628... atau null jika tidak valid/kosong
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone) || trim($phone) === '') {
            return null;
        }

        // Hapus semua karakter non-numerik (spasi, tanda +, -, titik, tanda kurung)
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (empty($digits)) {
            return null;
        }

        // 1. Tangani kesalahan ketik umum: +6208... atau 6208...
        if (str_starts_with($digits, '6208')) {
            $digits = '628' . substr($digits, 4);
        }
        // 2. Format lokal diawali 08...
        elseif (str_starts_with($digits, '08')) {
            $digits = '628' . substr($digits, 2);
        }
        // 3. Format tanpa awalan 0 atau 62: 8...
        elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }
        // 4. Jika diawali 0 lain (selain 08)
        elseif (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        // Validasi nomor WhatsApp seluler Indonesia:
        // Harus diawali '628' dan memiliki panjang antara 10 sampai 16 digit
        if (!str_starts_with($digits, '628')) {
            return null;
        }

        if (strlen($digits) < 10 || strlen($digits) > 16) {
            return null;
        }

        return $digits;
    }

    /**
     * Periksa apakah nomor telepon valid untuk pengiriman WhatsApp.
     */
    public static function isValid(?string $phone): bool
    {
        return self::normalize($phone) !== null;
    }

    /**
     * Format tampilan nomor telepon ramah pengguna (contoh: +62 812-3456-7890).
     */
    public static function formatDisplay(?string $phone): ?string
    {
        $normalized = self::normalize($phone);
        if (!$normalized) {
            return $phone;
        }

        $cc = substr($normalized, 0, 2);       // 62
        $prefix = substr($normalized, 2, 3);   // 812
        $mid = substr($normalized, 5, 4);      // 3456
        $end = substr($normalized, 9);         // 7890...

        return "+{$cc} {$prefix}-{$mid}-{$end}";
    }
}
