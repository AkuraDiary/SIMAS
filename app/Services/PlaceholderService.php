<?php

namespace App\Services;

use App\Models\Template;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class PlaceholderService
{
    /**
     * Inject missing variables back into the HTML content block.
     * Returns the updated HTML string and the count of items added.
     */
    public function syncVariablesToHtml(string $html, array $currentVars): array
    {
        $addedCount = 0;

        foreach ($currentVars as $var) {
            $key = $var['key'] ?? null;
            if (!$key) continue;

            $type = $var['type'] ?? 'text';

            if ($type === 'repeater') {
                if (strpos($html, '[loop:' . $key . ']') === false) {
                    $subContent = '';
                    $subFields = $var['repeater_fields'] ?? [];
                    foreach ($subFields as $sf) {
                        $sfKey = $sf['key'] ?? null;
                        $sfLabel = $sf['label'] ?? '';
                        if ($sfKey) {
                            $subContent .= $sfLabel . ': {{ ' . $sfKey . ' }}<br>';
                        }
                    }
                    $html .= "<p>[loop:{$key}]<br>{$subContent}[/loop:{$key}]</p>";
                    $addedCount++;
                }
            } else {
                if (strpos($html, '{{ ' . $key . ' }}') === false && strpos($html, '{{' . $key . '}}') === false) {
                    $html .= '<p>{{ ' . $key . ' }}</p>';
                    $addedCount++;
                }
            }
        }

        return [
            'html' => $html,
            'addedCount' => $addedCount
        ];
    }

    /**
     * Render the template's HTML by injecting the provided data.
     */
    public function renderHtml(Template $template, array $data, ?\App\Models\Surat $surat = null): string
    {
        $html = $template->content_html ?? '';

        // Inject Reserved System Variables if a Surat instance is provided
        if ($surat) {
            $data['nomor_surat'] = $surat->nomor_surat ?? '_______________________';
            $data['tanggal_surat'] = $surat->tanggal_kirim
                ? \Carbon\Carbon::parse($surat->tanggal_kirim)->translatedFormat('d F Y')
                : '.......................';
            $data['tanggal_terbit'] = $data['tanggal_surat']; // Alias just in case

            // Inject QR Code Dokumen Utama (Opsional, jika ada kebutuhan QR Global)
            $data['qr_code'] = '<img src="' . asset('images/qr_placeholder.png') . '" style="width: 80px; height: 80px;" />';

            // Inject TTD & QR Code dari Database (surat_ttds)
            foreach ($surat->suratTtds as $ttd) {
                if ($ttd->placeholder_key) {
                    $qrImg = '';
                    if ($ttd->qr_code_path) {
                        $qrImg = '<img src="' . asset('storage/' . $ttd->qr_code_path) . '" style="width: 80px; height: 80px; margin-bottom: 5px;" /><br>';
                    }

                    $namaTerang = $ttd->user->nama_lengkap ?? 'Pejabat Berwenang';

                    // Render Visual TTD (Bisa Custom CSS nanti)
                    $ttdVisual = '<div style="text-align: left; display: inline-block;">' .
                        $qrImg .
                        '<b><u>' . $namaTerang . '</u></b><br>' .
                        '<span style="font-size: 10pt;">' . $ttd->jabatan_saat_ttd . '</span>' .
                        '</div>';

                    $data[$ttd->placeholder_key] = $ttdVisual;
                }
            }
        }

        // Fallback : if content_html is empty but it's a DOCX template, generate it once and save it
        if (empty($html) && $template->render_engine === 'DOCX') {
            $media = $template->getFirstMedia('template_file');
            if ($media && file_exists($media->getPath())) {
                try {
                    $html = app(\App\Services\DocxTemplateService::class)->convertToHtml($media->getPath());
                    $template->updateQuietly(['content_html' => $html]);
                } catch (\Exception $e) {
                    $html = '';
                }
            }
        }

        if (empty($html)) return '';

        $html = $this->normalizeTableLoops($html);

        // Handle Repeaters / loops
        if (preg_match_all('/\[loop:([a-zA-Z0-9_]+)\](.*?)\[\/loop:\1\]/is', $html, $loopMatches, PREG_SET_ORDER)) {
            foreach ($loopMatches as $match) {
                $parentKey = $match[1];
                $blockContent = $match[2];

                $repeaterData = $data[$parentKey] ?? [];
                $renderedRows = '';

                if (is_array($repeaterData)) {
                    foreach ($repeaterData as $row) {
                        $rowContent = $blockContent;
                        // Replace child vars inside the loop
                        if (is_array($row)) {
                            foreach ($row as $childKey => $childValue) {
                                $rowContent = preg_replace('/\{\{\s*' . preg_quote($childKey, '/') . '\s*\}\}/', (string) $childValue, $rowContent);
                            }
                        }
                        $renderedRows .= $rowContent;
                    }
                }

                $html = str_replace($match[0], $renderedRows, $html);
            }
        }

        // Handle flat vars
        // Handle flat vars (text, date, number, etc.)
        foreach ($data as $key => $value) {
            if (!is_array($value) && $value !== null && $value !== '') {
                $html = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', (string) $value, $html);
            }
        }

        // Handle signature vars specifically
        foreach ($template->field_variables ?? [] as $field) {
            $key = $field['key'] ?? '';
            if (!$key || $field['type'] !== 'signature') continue;

            $method = $data[$key . '_method'] ?? 'draw';
            $val = '';
            if ($method === 'draw') {
                $val = $data[$key . '_draw'] ?? '';
                if ($val) {
                    $val = '<img src="' . htmlspecialchars($val) . '" style="max-height: 200px; max-width: 200px;" />';
                }
            } elseif ($method === 'upload') {
                $val = $data[$key . '_upload'] ?? '';
                if ($val) {
                    $val = '<img src="/storage/' . htmlspecialchars($val) . '" style="max-height: 200px; max-width: 200px;" />';
                }
            }

            if ($val) {
                $html = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', str_replace('$', '\$', $val), $html);
            }
        }

        // Clean up remaining un-filled placeholders to make it obvious they are missing
        $html = preg_replace('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', '<span style="color:#ef4444; font-weight:bold;">[$1]</span>', $html);

        // Inject basic CSS to ensure tables and lists render properly within Tailwind's reset environment
        $css = '<style>
            .docx-preview-wrapper table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
            .docx-preview-wrapper th, .docx-preview-wrapper td { border: 1px solid #d1d5db; text-align: left; padding: 0.25rem; }
            .docx-preview-wrapper th { background-color: #f3f4f6; font-weight: bold; }
        </style>';

        return '<div class="docx-preview-wrapper">' . $css . $html . '</div>';
    }

    /**
     * Fixes table row loops in Rich Text Editors by moving [loop] tags outside the <tr>
     * if they were placed inside table cells.
     */
    private function normalizeTableLoops(string $html): string
    {
        if (preg_match_all('/\[loop:([a-zA-Z0-9_]+)\].*?\[\/loop:\1\]/is', $html, $matches, PREG_OFFSET_CAPTURE)) {
            // Process from end to start to avoid offset shifting issues
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $fullMatch = $matches[0][$i][0];
                $startPos = $matches[0][$i][1];
                $loopName = $matches[1][$i][0];

                // Check if it crosses cell boundaries
                if (stripos($fullMatch, '</td>') !== false) {
                    // Find TR start before the loop
                    $trStart = strrpos(substr($html, 0, $startPos), '<tr');

                    // Find TR end after the loop
                    $endPos = $startPos + strlen($fullMatch);
                    $trEndPos = stripos($html, '</tr>', $endPos);

                    if ($trStart !== false && $trEndPos !== false) {
                        $trEnd = $trEndPos + 5; // include </tr>

                        // Extract the whole TR block
                        $trBlock = substr($html, $trStart, $trEnd - $trStart);

                        // Remove paragraph wrappers that ONLY contain the loop tag
                        $trBlockClean = preg_replace('/<p>(?:\s|&nbsp;|<br>)*\[\/?loop:' . $loopName . '\](?:\s|&nbsp;|<br>)*<\/p>/is', '', $trBlock);

                        // Remove the loop tags from INSIDE the TR block and swallow surrounding spaces
                        $trBlockClean = preg_replace('/(?:\s|&nbsp;)*\[\/?loop:' . $loopName . '\](?:\s|&nbsp;)*/is', '', $trBlockClean);

                        // Wrap the clean TR block with the loop tags
                        $newBlock = "[loop:$loopName]\n$trBlockClean\n[/loop:$loopName]";

                        // Replace in original HTML
                        $html = substr_replace($html, $newBlock, $trStart, $trEnd - $trStart);
                    }
                }
            }
        }
        return $html;
    }
}
