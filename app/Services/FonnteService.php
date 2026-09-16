<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected ?string $token;
    protected string $baseUrl = 'https://api.fonnte.com';
    protected int $timeout;

    public function __construct()
    {
        $this->token = config('services.fonnte.token');
        $this->timeout = (int) config('services.fonnte.timeout', 15);
    }

    /**
     * Cek apakah konfigurasi token Fonnte sudah diisi.
     */
    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    /**
     * Kirim pesan WhatsApp ke satu nomor telepon.
     *
     * @param string $phone Nomor tujuan (format bebas: 08..., +62..., 628..., dll.)
     * @param string $message Isi pesan WhatsApp
     * @param array $options Opsi tambahan (seperti delay, schedule, typing)
     * @return array Respon JSON dari Fonnte atau informasi error
     */
    public function send(string $phone, string $message, array $options = []): array
    {
        $normalized = PhoneNumberAdapter::normalize($phone);

        if (!$normalized) {
            Log::warning("[FonnteService] Nomor telepon tidak valid: '{$phone}'");
            return [
                'status' => false,
                'reason' => 'Nomor telepon tidak valid',
                'target' => $phone,
            ];
        }

        return $this->dispatchToFonnte($normalized, $message, $options);
    }

    /**
     * Kirim pesan WhatsApp ke beberapa nomor sekaligus dalam satu panggilan API.
     *
     * @param array $phones Daftar nomor tujuan
     * @param string $message Isi pesan WhatsApp
     * @param array $options Opsi tambahan
     * @return array Respon JSON dari Fonnte atau informasi error
     */
    public function sendBulk(array $phones, string $message, array $options = []): array
    {
        $normalizedPhones = [];

        foreach ($phones as $phone) {
            $norm = PhoneNumberAdapter::normalize($phone);
            if ($norm) {
                $normalizedPhones[] = $norm;
            }
        }

        $normalizedPhones = array_values(array_unique($normalizedPhones));

        if (empty($normalizedPhones)) {
            Log::warning('[FonnteService] Tidak ada nomor telepon valid dalam daftar sendBulk.');
            return [
                'status' => false,
                'reason' => 'Tidak ada nomor telepon valid',
            ];
        }

        // Fonnte mendukung pengiriman multi-nomor yang dipisahkan koma
        $targets = implode(',', $normalizedPhones);

        return $this->dispatchToFonnte($targets, $message, $options);
    }

    /**
     * Periksa status koneksi perangkat (device) di Fonnte.
     */
    public function getDeviceStatus(): array
    {
        if (!$this->isConfigured()) {
            return [
                'status' => false,
                'reason' => 'Token Fonnte belum dikonfigurasi',
            ];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $this->token,
                ])
                ->post("{$this->baseUrl}/device");

            return $response->json() ?? ['status' => false, 'reason' => 'Format respon tidak valid'];
        } catch (\Throwable $e) {
            Log::error('[FonnteService] Gagal memeriksa status device: ' . $e->getMessage());
            return [
                'status' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Eksekusi HTTP POST ke endpoint https://api.fonnte.com/send secara aman & resilient.
     */
    protected function dispatchToFonnte(string $target, string $message, array $options = []): array
    {
        if (!$this->isConfigured()) {
            Log::warning('[FonnteService] Token Fonnte belum dikonfigurasi di config/services.php atau .env');
            return [
                'status' => false,
                'reason' => 'Token Fonnte belum dikonfigurasi',
            ];
        }

        try {
            $payload = array_merge([
                'target'      => $target,
                'message'     => $message,
                'countryCode' => '62',
            ], $options);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $this->token,
                ])
                ->asForm()
                ->post("{$this->baseUrl}/send", $payload);

            $json = $response->json();

            if ($response->successful() && ($json['status'] ?? false) === true) {
                Log::info('[FonnteService] Notifikasi WhatsApp berhasil dikirim', [
                    'target'    => $target,
                    'fonnte_id' => $json['id'] ?? null,
                ]);

                return $json;
            }

            $reason = $json['reason'] ?? $json['message'] ?? 'Gagal mengirim pesan via Fonnte API';
            Log::error('[FonnteService] Gagal mengirim pesan WhatsApp via Fonnte', [
                'status'   => $response->status(),
                'target'   => $target,
                'response' => $json,
            ]);

            return is_array($json) ? $json : [
                'status' => false,
                'reason' => $reason,
            ];
        } catch (\Throwable $e) {
            Log::error('[FonnteService] Terjadi exception saat mengirim WhatsApp: ' . $e->getMessage(), [
                'target' => $target,
            ]);

            return [
                'status' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }
}
