<?php

namespace App\Services;

use App\Models\Template;

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

    /**
     * Generate dynamic Filament Form Schema based on the field_variables structure.
     */
    public function generateFilamentSchema(array $fieldVariables): array
    {
        $schema = [];

        foreach ($fieldVariables as $field) {
            $key = $field['key'] ?? null;
            $label = $field['label'] ?? 'Unknown';
            $type = $field['type'] ?? 'text';
            
            if (!$key) continue;

            $contentKey = "content.{$key}";

            switch ($type) {
                case 'long_text':
                    $schema[] = \Filament\Forms\Components\Textarea::make($contentKey)
                        ->label($label)
                        ->required();
                    break;
                case 'number':
                    $schema[] = \Filament\Forms\Components\TextInput::make($contentKey)
                        ->label($label)
                        ->numeric()
                        ->required();
                    break;
                case 'date':
                    $schema[] = \Filament\Forms\Components\DatePicker::make($contentKey)
                        ->label($label)
                        ->required();
                    break;
                case 'repeater':
                    $subSchema = [];
                    $subFields = $field['repeater_fields'] ?? [];
                    foreach ($subFields as $subField) {
                        $subKey = $subField['key'] ?? null;
                        $subLabel = $subField['label'] ?? 'Unknown';
                        if ($subKey) {
                            $subSchema[] = \Filament\Forms\Components\TextInput::make($subKey)
                                ->label($subLabel)
                                ->required();
                        }
                    }
                    $schema[] = \Filament\Forms\Components\Repeater::make($contentKey)
                        ->label($label)
                        ->schema($subSchema)
                        ->defaultItems(1)
                        ->addActionLabel('Tambah ' . $label);
                    break;
                case 'signature':
                    $isOptional = $field['is_optional_signature'] ?? false;
                    $signatureType = $field['signature_type'] ?? 'primary';
                    $optionsQuery = \App\Models\UserPegawaiJabatan::query()
                        ->where('status_jabatan', 'AKTIF')
                        ->with(['pegawai.user', 'jabatan', 'unitKerja'])
                        ->get()
                        ->mapWithKeys(function($jabatan) {
                            $nama = $jabatan->pegawai->nama_lengkap ?? 'Unknown';
                            $jabatanName = $jabatan->jabatan->nama_jabatan ?? '';
                            $unit = $jabatan->unitKerja->nama_unit ?? '';
                            return [$jabatan->id => "{$nama} ({$jabatanName} {$unit})"];
                        });

                    $schema[] = \Filament\Forms\Components\Select::make($contentKey)
                        ->label($label . ' (' . ($signatureType === 'primary' ? 'Utama' : 'Mengetahui') . ')')
                        ->options($optionsQuery)
                        ->searchable()
                        ->required(!$isOptional);
                    break;
                case 'text':
                default:
                    $schema[] = \Filament\Forms\Components\TextInput::make($contentKey)
                        ->label($label)
                        ->required();
                    break;
            }
        }

        return $schema;
    }

    /**
     * Render the template's HTML by injecting the provided data.
     */
    public function renderHtml(Template $template, array $data): string
    {
        $html = $template->content_html ?? '';
        if (empty($html)) return '';

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
                                $rowContent = str_replace('{{ ' . $childKey . ' }}', (string) $childValue, $rowContent);
                                $rowContent = str_replace('{{' . $childKey . '}}', (string) $childValue, $rowContent);
                            }
                        }
                        $renderedRows .= $rowContent;
                    }
                }

                $html = str_replace($match[0], $renderedRows, $html);
            }
        }

        // Handle flat vars
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $html = str_replace('{{ ' . $key . ' }}', (string) $value, $html);
                $html = str_replace('{{' . $key . '}}', (string) $value, $html);
            }
        }

        // Clean up remaining un-filled placeholders to make it obvious they are missing
        $html = preg_replace('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', '<span style="color:#ef4444; font-weight:bold;">[$1]</span>', $html);

        return $html;
    }
}
