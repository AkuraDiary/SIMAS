<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OllamaService
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

    /**
     * Generate an official bureaucratic letter draft with Indonesian Tata Naskah Dinas standard.
     *
     * @param string $instruction User's prompt/instructions
     * @param array $context Contextual data (perihal, unit, kategori, dll)
     * @return array{perihal: string, isi_surat: string, penjelasan: string, raw: string}
     */
    public function generateDraft(string $instruction, array $context = []): array
    {
        $systemPrompt = $this->buildSystemPrompt($context);

        $userContent = "Instruksi Pembuatan Surat:\n" . $instruction;
        if (!empty($context['kategori'])) {
            $userContent .= "\nKategori Surat: " . $context['kategori'];
        }
        if (!empty($context['perihal_saat_ini'])) {
            $userContent .= "\nPerihal Saat Ini: " . $context['perihal_saat_ini'];
        }
        if (!empty($context['unit_pengirim'])) {
            $userContent .= "\nUnit Pengirim: " . $context['unit_pengirim'];
        }
        if (!empty($context['tone'])) {
            $userContent .= "\nGaya Bahasa: " . $context['tone'];
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userContent],
        ];

        $rawResponse = $this->chat($messages);

        return $this->parseDraftResponse($rawResponse, $context['perihal_saat_ini'] ?? '');
    }

    /**
     * Refine an existing draft based on follow-up user feedback.
     *
     * @param string $currentDraft
     * @param string $feedback
     * @param array $context
     * @return array{perihal: string, isi_surat: string, penjelasan: string, raw: string}
     */
    public function refineDraft(string $currentDraft, string $feedback, array $context = []): array
    {
        $systemPrompt = $this->buildSystemPrompt($context);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            [
                'role' => 'user',
                'content' => "Berikut draft surat yang telah dibuat sebelumnya:\n{$currentDraft}\n\nInstruksi Perbaikan / Revisi Tambahan:\n{$feedback}\n\nMohon perbaiki draft di atas dan berikan output lengkap sesuai format pembatas yang ditentukan."
            ],
        ];

        $rawResponse = $this->chat($messages);

        return $this->parseDraftResponse($rawResponse, $context['perihal_saat_ini'] ?? '');
    }

    /**
     * Quick fallback letter generator using standard Indonesian bureaucratic templates
     * when the external AI service is unreachable or offline.
     */
    public function generateFallbackDraft(string $instruction, array $context = []): array
    {
        $kategori = $context['kategori'] ?? 'Undangan Rapat / Kegiatan';
        $cleanPrompt = trim($instruction);

        if (str_contains($kategori, 'Undangan')) {
            $perihal = 'Undangan ' . ($cleanPrompt ? ucwords($cleanPrompt) : 'Rapat Koordinasi');
            $isiSurat = "<p>Sehubungan dengan pelaksanaan kegiatan {$cleanPrompt}, bersama ini kami mengundang Bapak/Ibu untuk dapat menghadiri rapat koordinasi yang akan dilaksanakan pada:</p><ul><li><strong>Hari / Tanggal:</strong> [Hari, DD Bulan YYYY]</li><li><strong>Waktu:</strong> 09.00 WIB s.d. Selesai</li><li><strong>Tempat:</strong> [Ruang Rapat / Lokasi Acara]</li><li><strong>Agenda:</strong> {$cleanPrompt}</li></ul><p>Mengingat pentingnya agenda pembahasan tersebut, kami mengharapkan kehadiran Bapak/Ibu tepat pada waktunya. Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.</p>";
        } elseif (str_contains($kategori, 'Permohonan')) {
            $perihal = 'Permohonan ' . ($cleanPrompt ? ucwords($cleanPrompt) : 'Bantuan / Izin Kegiatan');
            $isiSurat = "<p>Dengan hormat,</p><p>Dalam rangka pelaksanaan agenda {$cleanPrompt}, bersama surat ini kami mengajukan permohonan kepada Bapak/Ibu terkait perihal dimaksud.</p><p>Adapun rincian permohonan adalah sebagai berikut:</p><ul><li><strong>Kegiatan:</strong> {$cleanPrompt}</li><li><strong>Waktu / Periode:</strong> [DD Bulan YYYY]</li><li><strong>Kebutuhan:</strong> [Sebutkan fasilitas / perizinan / bantuan yang diajukan]</li></ul><p>Demikian permohonan ini kami sampaikan. Besar harapan kami permohonan ini dapat dipertimbangkan dan disetujui. Atas perhatian dan perkenan Bapak/Ibu, kami ucapkan terima kasih.</p>";
        } elseif (str_contains($kategori, 'Pemberitahuan') || str_contains($kategori, 'Edaran')) {
            $perihal = 'Pemberitahuan Terkait ' . ($cleanPrompt ? ucwords($cleanPrompt) : 'Pelaksanaan Kegiatan');
            $isiSurat = "<p>Dengan hormat,</p><p>Menindaklanjuti program kerja dan ketentuan yang berlaku, bersama surat ini kami sampaikan pemberitahuan mengenai {$cleanPrompt}.</p><p>Sehubungan dengan hal tersebut, kami mengimbau seluruh pihak terkait untuk memperhatikan hal-hal sebagai berikut:</p><ol><li>[Poin pemberitahuan pertama terkait pelaksanaan]</li><li>[Poin batas waktu / tenggat pelaporan atau konfirmasi]</li><li>[Poin narahubung atau prosedur teknis lanjutan]</li></ol><p>Demikian surat pemberitahuan ini disampaikan untuk diketahui dan dilaksanakan sebagaimana mestinya. Atas perhatian dan kerja sama yang diberikan, kami ucapkan terima kasih.</p>";
        } elseif (str_contains($kategori, 'Tugas')) {
            $perihal = 'Surat Tugas ' . ($cleanPrompt ? ucwords($cleanPrompt) : 'Dinas / Kegiatan');
            $isiSurat = "<p>Yang bertanda tangan di bawah ini memberikan tugas kepada:</p><ul><li><strong>Nama / NIP:</strong> [Nama Pegawai / Tim Pelaksana]</li><li><strong>Jabatan / Unit:</strong> [Jabatan / Unit Kerja]</li></ul><p>Untuk melaksanakan tugas kedinasan sebagai berikut:</p><ul><li><strong>Tugas:</strong> {$cleanPrompt}</li><li><strong>Waktu Pelaksanaan:</strong> [DD Bulan YYYY]</li><li><strong>Tempat / Tujuan:</strong> [Lokasi Pelaksanaan Tugas]</li></ul><p>Demikian surat tugas ini diberikan untuk dilaksanakan dengan penuh rasa tanggung jawab dan menyampaikan laporan setelah kegiatan selesai.</p>";
        } else {
            $perihal = ($cleanPrompt ? ucwords($cleanPrompt) : 'Penyampaian Informasi Kedinasan');
            $isiSurat = "<p>Dengan hormat,</p><p>Sehubungan dengan {$cleanPrompt}, bersama ini kami sampaikan hal-hal sebagai berikut:</p><p>[Tuliskan penjelasan dan rincian informasi di sini secara runtut dan formal.]</p><p>Demikian surat ini kami sampaikan, atas perhatian dan kerja sama Saudara kami ucapkan terima kasih.</p>";
        }

        return [
            'perihal' => $perihal,
            'isi_surat' => $isiSurat,
            'penjelasan' => 'Format standar Tata Naskah Dinas otomatis.',
            'raw' => '',
        ];
    }

    /**
     * System prompt establishing Indonesian Tata Naskah Dinas official letter standards.
     */
    protected function buildSystemPrompt(array $context = []): string
    {
        return <<<PROMPT
Anda adalah asisten AI profesional spesialis Tata Naskah Dinas dan korespondensi birokrasi pemerintahan/perguruan tinggi di Indonesia.
Tugas Anda adalah membuat atau menyempurnakan draft isi surat resmi dalam Bahasa Indonesia yang baku, santun, lugas, dan sesuai kaidah PUEBI (Pedoman Umum Ejaan Bahasa Indonesia).

PEDOMAN PENTING STRUKTUR SURAT:
1. Kop surat (kepala surat), nomor surat, tanggal surat, dan blok tanda tangan (TTD) sudah ditangani otomatis oleh sistem aplikasi SIMAS.
2. Anda HANYA bertugas menghasilkan:
   - "PERIHAL": Judul/perihal surat yang ringkas, padat, dan jelas.
   - "ISI_SURAT": Badan surat resmi lengkap yang terdiri dari:
     a. Alinea Pembuka (contoh: "Sehubungan dengan...", "Dalam rangka pelaksanaan kegiatan...", "Menindaklanjuti surat permohonan...")
     b. Alinea Isi/Substansi (rincian acara/agenda, hari/tanggal, waktu, tempat, peserta, atau poin-poin permohonan/instruksi secara terstruktur)
     c. Alinea Penutup (contoh: "Demikian surat ini kami sampaikan, atas perhatian dan kerja sama Saudara kami ucapkan terima kasih.")
3. Format ISI_SURAT harus berupa HTML bersih yang siap dipakai langsung oleh Rich Text Editor (TinyEditor):
   - Gunakan tag <p>...</p> untuk setiap paragraf.
   - Gunakan tag <ul><li>...</li></ul> atau <ol><li>...</li></ol> untuk rincian atau daftar poin.
   - Gunakan <strong>...</strong> untuk penekanan penting.
   - JANGAN menyertakan blok kode markdown seperti ```html atau ```.
   - JANGAN menyertakan tag <html>, <head>, atau <body>.

FORMAT OUTPUT WAJIB:
Tuliskan jawaban Anda persis dalam format pembatas berikut agar dapat diproses oleh sistem aplikasi:

---PERIHAL---
[Tuliskan saran perihal surat di sini]
---ISI_SURAT---
[Tuliskan HTML isi surat di sini]
---PENJELASAN---
[Catatan singkat mengenai draft yang dibuat atau bagian yang perlu disesuaikan oleh staf]
PROMPT;
    }

    /**
     * Parse structured response from AI.
     */
    public function parseDraftResponse(string $raw, string $fallbackPerihal = ''): array
    {
        $perihal = $fallbackPerihal;
        $isiSurat = '';
        $penjelasan = '';

        if (preg_match('/---PERIHAL---\s*(.*?)\s*---ISI_SURAT---\s*(.*?)(?:\s*---PENJELASAN---\s*(.*))?$/s', $raw, $matches)) {
            $perihal = trim($matches[1]);
            $isiSurat = trim($matches[2]);
            $penjelasan = isset($matches[3]) ? trim($matches[3]) : '';
        } elseif (preg_match('/---ISI_SURAT---\s*(.*?)(?:\s*---PENJELASAN---\s*(.*))?$/s', $raw, $matches)) {
            $isiSurat = trim($matches[1]);
            $penjelasan = isset($matches[2]) ? trim($matches[2]) : '';
        } else {
            // Fallback if delimiters were omitted by model
            $isiSurat = $raw;
        }

        // Clean up any remaining markdown code fences
        $isiSurat = preg_replace('/^```(?:html)?\s*/i', '', $isiSurat);
        $isiSurat = preg_replace('/\s*```$/', '', $isiSurat);
        $isiSurat = trim($isiSurat);

        // Ensure proper HTML paragraphs if model returned plain text without tags
        if (!str_contains($isiSurat, '<p>') && !str_contains($isiSurat, '<br') && !empty($isiSurat)) {
            $paragraphs = array_filter(array_map('trim', explode("\n\n", $isiSurat)));
            $isiSurat = '<p>' . implode("</p>\n<p>", $paragraphs) . '</p>';
        }

        return [
            'perihal' => $perihal,
            'isi_surat' => $isiSurat,
            'penjelasan' => $penjelasan,
            'raw' => $raw,
        ];
    }
}
