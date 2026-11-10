<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OllamaService extends BaseAiDraftService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct(
        ?string $baseUrl = null,
        ?string $apiKey = null,
        ?string $model = null,
        ?int $timeout = null
    ) {
        $this->baseUrl = rtrim($baseUrl ?? config('services.ollama.base_url', 'http://localhost:11434'), '/');
        $this->apiKey = $apiKey ?? config('services.ollama.api_key');
        $this->model = $model ?? config('services.ollama.model', 'gpt-oss:20b');
        $this->timeout = $timeout ?? (int) config('services.ollama.timeout', 60);
    }

    /**
     * Check if service has valid configuration.
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Normalize URL for specific endpoints, preventing double /api.
     */
    protected function endpoint(string $path): string
    {
        $base = $this->baseUrl;
        if (str_ends_with($base, '/api') && str_starts_with($path, '/api/')) {
            return substr($base, 0, -4) . $path;
        }
        if (str_ends_with($base, '/api') && str_starts_with($path, '/v1/')) {
            return substr($base, 0, -4) . $path;
        }
        return $base . $path;
    }

    /**
     * Get configured HTTP client.
     */
    protected function client()
    {
        $req = Http::timeout($this->timeout)
            ->acceptJson();

        if (!empty($this->apiKey)) {
            $req = $req->withToken($this->apiKey);
        }

        return $req;
    }

    /**
     * Send chat conversation to Ollama and return assistant message content.
     *
     * @param array<array{role: string, content: string}> $messages
     * @param string|null $model
     * @return string
     * @throws RuntimeException
     */
    public function chat(array $messages, ?string $model = null): string
    {
        $model = $model ?: $this->model;
        $lastException = null;

        // Try 1: Native Ollama /api/chat
        try {
            $response = $this->client()->post($this->endpoint('/api/chat'), [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['message']['content'])) {
                    return trim($data['message']['content']);
                }
            } elseif ($response->status() === 401 || $response->status() === 403) {
                throw new RuntimeException(
                    "Autentikasi Ollama ditolak (HTTP {$response->status()}). Mohon periksa OLLAMA_API_KEY dan OLLAMA_BASE_URL pada file .env."
                );
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $lastException = $e;
            Log::warning("Ollama connection exception on /api/chat: " . $e->getMessage());
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $lastException = $e;
            Log::warning("Ollama /api/chat error: " . $e->getMessage());
        }

        // Try 2: OpenAI-compatible /v1/chat/completions
        try {
            $response = $this->client()->post($this->endpoint('/v1/chat/completions'), [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['choices'][0]['message']['content'])) {
                    return trim($data['choices'][0]['message']['content']);
                }
            } elseif ($response->status() === 401 || $response->status() === 403) {
                throw new RuntimeException(
                    "Autentikasi Ollama ditolak (HTTP {$response->status()}). Mohon periksa OLLAMA_API_KEY dan OLLAMA_BASE_URL pada file .env."
                );
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $lastException = $e;
            Log::warning("Ollama /v1/chat/completions error: " . $e->getMessage());
        }

        // Try 3: Ollama /api/generate
        try {
            $system = '';
            $prompt = '';
            foreach ($messages as $msg) {
                if ($msg['role'] === 'system') {
                    $system .= $msg['content'] . "\n\n";
                } else {
                    $prefix = ($msg['role'] === 'user') ? 'User: ' : 'Assistant: ';
                    $prompt .= $prefix . $msg['content'] . "\n\n";
                }
            }

            $response = $this->client()->post($this->endpoint('/api/generate'), [
                'model' => $model,
                'prompt' => trim($prompt),
                'system' => trim($system),
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['response'])) {
                    return trim($data['response']);
                }
            }
        } catch (\Throwable $e) {
            $lastException = $e;
            Log::warning("Ollama /api/generate error: " . $e->getMessage());
        }

        $errorDetail = $lastException ? " ({$lastException->getMessage()})" : "";
        throw new RuntimeException(
            "Tidak dapat menghubungi layanan Ollama ({$this->baseUrl}){$errorDetail}. Pastikan service Ollama aktif, model '{$model}' tersedia, dan kunci API valid."
        );
    }
}
