<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiService extends BaseAiDraftService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?string $baseUrl = null,
        ?int $timeout = null
    ) {
        $this->apiKey = $apiKey ?? config('services.gemini.api_key');
        $this->model = $model ?? config('services.gemini.model');
        $this->baseUrl = rtrim($baseUrl ?? config('services.gemini.base_url'), '/');
        $this->timeout = $timeout ?? (int) config('services.gemini.timeout', 30);
    }

    /**
     * Check if service has valid configuration.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Send chat conversation to Google Gemini and return assistant message content.
     *
     * @param array<array{role: string, content: string}> $messages
     * @param string|null $model
     * @return string
     * @throws RuntimeException
     */
    public function chat(array $messages, ?string $model = null): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException(
                "GEMINI_API_KEY belum dikonfigurasi pada file .env. Silakan tambahkan kredensial Google AI Studio Anda."
            );
        }

        $model = $model ?: $this->model;
        $systemParts = [];
        $contents = [];

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';

            if (empty(trim($content))) {
                continue;
            }

            if ($role === 'system') {
                $systemParts[] = ['text' => $content];
            } else {
                $geminiRole = ($role === 'assistant' || $role === 'model') ? 'model' : 'user';
                $contents[] = [
                    'role'  => $geminiRole,
                    'parts' => [
                        ['text' => $content],
                    ],
                ];
            }
        }

        // Pastikan ada setidaknya satu pesan user
        if (empty($contents)) {
            $contents[] = [
                'role'  => 'user',
                'parts' => [['text' => 'Buatkan draft surat naskah dinas.']],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 2048,
            ],
        ];

        if (!empty($systemParts)) {
            $payload['system_instruction'] = [
                'parts' => $systemParts,
            ];
        }

        $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $candidate = $data['candidates'][0] ?? null;

                if (!empty($candidate['content']['parts'][0]['text'])) {
                    return trim($candidate['content']['parts'][0]['text']);
                }

                if (($candidate['finishReason'] ?? '') === 'SAFETY') {
                    throw new RuntimeException(
                        "Permintaan pembuatan draf dihentikan oleh filter keamanan Google Gemini (Safety Guardrail)."
                    );
                }

                throw new RuntimeException(
                    "Google Gemini tidak mengembalikan isi naskah (Finish Reason: " . ($candidate['finishReason'] ?? 'UNKNOWN') . ")."
                );
            }

            $status = $response->status();
            $errorMessage = $response->json('error.message') ?? $response->body();

            if ($status === 400 || $status === 403) {
                throw new RuntimeException(
                    "Autentikasi Gemini ditolak (HTTP {$status}). Mohon periksa kembali GEMINI_API_KEY pada file .env. Pesan: {$errorMessage}"
                );
            }

            if ($status === 404) {
                throw new RuntimeException(
                    "Model Gemini '{$model}' tidak ditemukan atau tidak mendukung API generateContent (HTTP 404). Mohon periksa GEMINI_MODEL pada .env."
                );
            }

            if ($status === 429) {
                throw new RuntimeException(
                    "Batas kuota gratis Gemini tercapai (HTTP 429: Resource Exhausted). Mohon tunggu sejenak atau gunakan format standar kedinasan."
                );
            }

            throw new RuntimeException(
                "Layanan Gemini mengalami kendala (HTTP {$status}): {$errorMessage}"
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning("Gemini connection exception: " . $e->getMessage());
            throw new RuntimeException(
                "Tidak dapat terhubung ke server Google Gemini. Periksa koneksi internet server Anda."
            );
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning("Gemini unexpected error: " . $e->getMessage());
            throw new RuntimeException(
                "Gagal memproses draf dengan Gemini: " . $e->getMessage()
            );
        }
    }
}
