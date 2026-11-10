<?php

namespace App\Services\Contracts;

interface AiDraftServiceInterface
{
    /**
     * Send chat conversation to the underlying AI model and return assistant message content.
     *
     * @param array<array{role: string, content: string}> $messages
     * @param string|null $model
     * @return string
     */
    public function chat(array $messages, ?string $model = null): string;

    /**
     * Generate an official bureaucratic letter draft with Indonesian Tata Naskah Dinas standard.
     *
     * @param string $instruction User's prompt/instructions
     * @param array $context Contextual data (perihal, unit, kategori, dll)
     * @return array{perihal: string, isi_surat: string, penjelasan: string, raw: string}
     */
    public function generateDraft(string $instruction, array $context = []): array;

    /**
     * Refine an existing draft based on follow-up user feedback and conversation history.
     *
     * @param string $currentDraft
     * @param string $feedback
     * @param array $context
     * @param array $history
     * @return array{perihal: string, isi_surat: string, penjelasan: string, raw: string, history: array}
     */
    public function refineDraft(string $currentDraft, string $feedback, array $context = [], array $history = []): array;

    /**
     * Quick fallback letter generator using standard Indonesian bureaucratic templates.
     *
     * @param string $instruction
     * @param array $context
     * @return array{perihal: string, isi_surat: string, penjelasan: string, raw: string}
     */
    public function generateFallbackDraft(string $instruction, array $context = []): array;
}
