<?php

namespace App\Services;

use App\Exceptions\AiDisabledException;
use App\Exceptions\AiNotConfiguredException;
use App\Models\AiToken;
use App\Models\SystemSetting;
use App\Services\Contracts\AiDraftServiceInterface;

class AiDraftManager implements AiDraftServiceInterface
{
    /**
     * Check if global AI drafting assistant is enabled.
     */
    public static function isFeatureEnabled(): bool
    {
        return SystemSetting::isTrue('ai_drafting_enabled', true);
    }

    /**
     * Enable or disable global AI drafting assistant.
     */
    public static function setFeatureEnabled(bool $enabled): void
    {
        SystemSetting::set('ai_drafting_enabled', $enabled ? '1' : '0');
    }

    /**
     * Resolve the active AI service implementation based on DB token or environment fallback.
     *
     * @throws AiDisabledException
     * @throws AiNotConfiguredException
     */
    public function resolveActiveService(): AiDraftServiceInterface
    {
        // 1. Check if feature is globally enabled
        if (! static::isFeatureEnabled()) {
            throw new AiDisabledException('Fitur asisten AI sedang dinonaktifkan oleh Administrator.');
        }

        // 2. Check for active token in database
        try {
            /** @var AiToken|null $token */
            $token = AiToken::query()->active()->first();
            if ($token !== null) {
                if ($token->provider === 'gemini') {
                    return new GeminiService(
                        apiKey: $token->api_key,
                        model: $token->model ?: config('services.gemini.model'),
                        baseUrl: $token->base_url ?: config('services.gemini.base_url'),
                        timeout: config('services.gemini.timeout')
                    );
                }

                if ($token->provider === 'ollama') {
                    return new OllamaService(
                        baseUrl: $token->base_url ?: config('services.ollama.base_url'),
                        apiKey: $token->api_key,
                        model: $token->model ?: config('services.ollama.model'),
                        timeout: config('services.ollama.timeout')
                    );
                }
            }
        } catch (\Throwable $e) {
            // If table doesn't exist or query fails, continue to config fallback
        }

        // 3. Fallback to .env / configuration
        if (! empty(config('services.gemini.api_key'))) {
            return app(GeminiService::class);
        }

        $ollamaBaseUrl = config('services.ollama.base_url') ?: env('OLLAMA_BASE_URL');
        $hasCustomOllamaUrl = ! empty($ollamaBaseUrl) && $ollamaBaseUrl !== 'http://localhost:11434';
        if (! empty(config('services.ollama.api_key')) || $hasCustomOllamaUrl) {
            return app(OllamaService::class);
        }

        // 4. Zero-token condition
        throw new AiNotConfiguredException(
            'Kredensial layanan AI belum dikonfigurasi di sistem maupun berkas konfigurasi. Silakan hubungi Administrator.'
        );
    }

    /**
     * Test connection to an AI service provider with a lightweight prompt.
     *
     * @return array{success: bool, message: string, response: ?string}
     */
    public function testConnection(AiDraftServiceInterface $service): array
    {
        try {
            $response = $service->chat([
                ['role' => 'user', 'content' => 'Ping. Jawab dengan satu kata: OK.'],
            ]);

            return [
                'success' => true,
                'message' => 'Koneksi berhasil terhubung ke penyedia AI.',
                'response' => $response,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'response' => null,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function chat(array $messages, ?string $model = null): string
    {
        return $this->resolveActiveService()->chat($messages, $model);
    }

    /**
     * {@inheritdoc}
     */
    public function generateDraft(string $instruction, array $context = []): array
    {
        return $this->resolveActiveService()->generateDraft($instruction, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function refineDraft(string $currentDraft, string $feedback, array $context = [], array $history = []): array
    {
        return $this->resolveActiveService()->refineDraft($currentDraft, $feedback, $context, $history);
    }

    /**
     * {@inheritdoc}
     */
    public function generateFallbackDraft(string $instruction, array $context = []): array
    {
        return $this->resolveActiveService()->generateFallbackDraft($instruction, $context);
    }
}
