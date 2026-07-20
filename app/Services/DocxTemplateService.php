<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;

class DocxTemplateService
{
    /**
     * Convert a DOCX file to pure HTML (extracting the <body> contents).
     */
    public function convertToHtml(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File template DOCX tidak ditemukan.");
        }

        $phpWord = IOFactory::load($filePath);
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');

        $tmpHtmlFile = tempnam(sys_get_temp_dir(), 'html');
        $htmlWriter->save($tmpHtmlFile);
        $html = file_get_contents($tmpHtmlFile);
        @unlink($tmpHtmlFile);

        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }

    /**
     * Extract placeholders matching the format {{ field_name }}, loop blocks, and dot notation.
     * Returns a structured array of fields with metadata (key, label, type, repeater_fields).
     */
    public function extractPlaceholders(string $html): array
    {
        $fields = [];
        $tempHtml = $html;

        // 1. Scan HTML Loop Blocks: [loop:parent] ... {{ child }} ... [/loop:parent]
        if (preg_match_all('/\[loop:([a-zA-Z0-9_]+)\](.*?)\[\/loop:\1\]/is', $tempHtml, $loopMatches, PREG_SET_ORDER)) {
            foreach ($loopMatches as $match) {
                $parentKey = $match[1];
                $blockContent = $match[2];

                // Scan inside loop for child placeholders
                preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $blockContent, $childMatches);
                $subFields = array_unique($childMatches[1] ?? []);

                $repeaterFields = [];
                foreach ($subFields as $subField) {
                    $repeaterFields[] = [
                        'key' => $subField,
                        'label' => ucwords(str_replace('_', ' ', $subField)),
                    ];
                }

                $fields[] = [
                    'key' => $parentKey,
                    'label' => ucwords(str_replace('_', ' ', $parentKey)),
                    'type' => 'repeater',
                    'repeater_fields' => $repeaterFields,
                ];

                // Remove from tempHtml so we don't scan them as flat variables
                $tempHtml = str_replace($match[0], '', $tempHtml);
            }
        }

        // 2. Scan DOCX Block tags: ${parent} ... {{ child }} ... ${/parent}
        if (preg_match_all('/\$\{([a-zA-Z0-9_]+)\}(.*?)\$\{\/\1\}/is', $tempHtml, $docxMatches, PREG_SET_ORDER)) {
            foreach ($docxMatches as $match) {
                $parentKey = $match[1];
                $blockContent = $match[2];

                preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $blockContent, $childMatches);
                $subFields = array_unique($childMatches[1] ?? []);

                $repeaterFields = [];
                foreach ($subFields as $subField) {
                    $repeaterFields[] = [
                        'key' => $subField,
                        'label' => ucwords(str_replace('_', ' ', $subField)),
                    ];
                }

                $fields[] = [
                    'key' => $parentKey,
                    'label' => ucwords(str_replace('_', ' ', $parentKey)),
                    'type' => 'repeater',
                    'repeater_fields' => $repeaterFields,
                ];

                $tempHtml = str_replace($match[0], '', $tempHtml);
            }
        }

        // 3. Scan DOCX Table Row notation: {{ parent.child }}
        if (preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\s*\}\}/', $tempHtml, $dotMatches, PREG_SET_ORDER)) {
            $groupedDots = [];
            foreach ($dotMatches as $match) {
                $parentKey = $match[1];
                $childKey = $match[2];
                $groupedDots[$parentKey][] = $childKey;

                // Clean match out
                $tempHtml = str_replace($match[0], '', $tempHtml);
            }

            foreach ($groupedDots as $parentKey => $children) {
                $children = array_unique($children);
                $repeaterFields = [];
                foreach ($children as $subField) {
                    $repeaterFields[] = [
                        'key' => $subField,
                        'label' => ucwords(str_replace('_', ' ', $subField)),
                    ];
                }

                // Check if already registered
                $existingIndex = null;
                foreach ($fields as $idx => $f) {
                    if ($f['key'] === $parentKey) {
                        $existingIndex = $idx;
                        break;
                    }
                }

                if ($existingIndex !== null) {
                    // Merge subfields
                    $existingSubKeys = array_column($fields[$existingIndex]['repeater_fields'], 'key');
                    foreach ($repeaterFields as $rf) {
                        if (!in_array($rf['key'], $existingSubKeys)) {
                            $fields[$existingIndex]['repeater_fields'][] = $rf;
                        }
                    }
                } else {
                    $fields[] = [
                        'key' => $parentKey,
                        'label' => ucwords(str_replace('_', ' ', $parentKey)),
                        'type' => 'repeater',
                        'repeater_fields' => $repeaterFields,
                    ];
                }
            }
        }

        // 4. Scan Flat Placeholders: {{ field }}
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $tempHtml, $flatMatches);
        $flatFields = array_unique($flatMatches[1] ?? []);

        foreach ($flatFields as $flatField) {
            $fields[] = [
                'key' => $flatField,
                'label' => ucwords(str_replace('_', ' ', $flatField)),
                'type' => 'text',
            ];
        }

        return $fields;
    }

    /**
     * Highlight placeholders in HTML with a bright background.
     */
    public function highlightPlaceholders(string $html): string
    {
        return preg_replace(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            '<mark style="background-color: #ffeb3b; font-weight: bold;">{{ $1 }}</mark>',
            $html
        );
    }
}
