<?php

namespace App\Services;

class PlaceholderService
{
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

                $existingIndex = null;
                foreach ($fields as $idx => $f) {
                    if ($f['key'] === $parentKey) {
                        $existingIndex = $idx;
                        break;
                    }
                }

                if ($existingIndex !== null) {
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
     * Merge newly extracted fields with the existing fields from state.
     * Keeps user labels/types, adds new ones, removes deleted ones.
     */
    public function syncExtractedToVariables(array $extractedFields, array $currentVars): array
    {
        $existingKeys = array_column($currentVars, 'key');
        
        // Add new fields
        foreach ($extractedFields as $field) {
            if (!in_array($field['key'], $existingKeys)) {
                $currentVars[] = $field;
            }
        }

        // Clean up deleted fields
        $extractedKeys = array_column($extractedFields, 'key');
        foreach ($currentVars as $index => $var) {
            $key = $var['key'] ?? '';
            if (!in_array($key, $extractedKeys)) {
                unset($currentVars[$index]);
            }
        }
        
        return array_values($currentVars);
    }

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
     * Ensure hydrated variables are correctly structured for Filament.
     * Prevent flat arrays with UUID strings from replacing data structures.
     */
    public function formatHydratedVariables(?array $state): array
    {
        if (empty($state)) return [];

        $isOldFormat = false;
        foreach ($state as $key => $value) {
            if (is_string($key) && !is_array($value)) {
                $isOldFormat = true;
                break;
            }
        }

        if ($isOldFormat) {
            $newFormat = [];
            foreach ($state as $key => $value) {
                $newFormat[] = [
                    'key' => $key,
                    'label' => is_string($value) ? $value : 'Unknown',
                    'type' => 'text',
                ];
            }
            return $newFormat;
        }

        return $state;
    }
}
