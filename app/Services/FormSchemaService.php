<?php

namespace App\Services;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class FormSchemaService
{
    // We will paste the methods in here!

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
        $uuidVars = [];
        foreach ($currentVars as $index => $var) {
            $key = $var['key'] ?? '';
            if (!in_array($key, $extractedKeys)) {
                continue;
            }
            if (is_numeric($index)) {
                $uuidVars[(string) \Illuminate\Support\Str::uuid()] = $var;
            } else {
                $uuidVars[$index] = $var;
            }
        }

        return $uuidVars;
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
                $newFormat[(string) \Illuminate\Support\Str::uuid()] = [
                    'key' => $key,
                    'label' => is_string($value) ? $value : 'Unknown',
                    'type' => 'text',
                ];
            }
            return $newFormat;
        }

        // Ensure state has UUID keys
        $uuidState = [];
        foreach ($state as $index => $item) {
            if (is_numeric($index)) {
                $uuidState[(string) \Illuminate\Support\Str::uuid()] = $item;
            } else {
                $uuidState[$index] = $item;
            }
        }

        return $uuidState;
    }

    /**
     * Generate dynamic Filament Form Schema based on the field_variables structure.
     */
    public function generateFilamentSchema(array $fieldVariables): array
    {
        $schema = [];
        // Define reserved keywords that should not become form inputs
        $reservedKeys = ['nomor_surat', 'tanggal_surat', 'tanggal_terbit', 'qr_code'];

        foreach ($fieldVariables as $field) {
            $key = $field['key'] ?? null;
            $label = $field['label'] ?? 'Unknown';
            $type = $field['type'] ?? 'text';

            if (!$key) continue;

            // Skip reserved keys and any key starting with 'ttd_approver'
            if (in_array(strtolower($key), $reservedKeys) || \Illuminate\Support\Str::startsWith(strtolower($key), 'ttd_approver')) {
                continue;
            }


            $contentKey = "content.{$key}";

            switch ($type) {
                case 'long_text':
                    $schema[] = \Filament\Forms\Components\Textarea::make($contentKey)
                        ->label($label)
                        ->required()
                        ->live(debounce: 500);
                    break;
                case 'number':
                    $schema[] = \Filament\Forms\Components\TextInput::make($contentKey)
                        ->label($label)
                        ->numeric()
                        ->required()
                        ->live(debounce: 500);
                    break;
                case 'date':
                    $schema[] = \Filament\Forms\Components\DatePicker::make($contentKey)
                        ->label($label)
                        ->required()
                        ->live(debounce: 500);
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
                                ->required()
                                ->live(debounce: 500);
                        }
                    }
                    $schema[] = \Filament\Forms\Components\Repeater::make($contentKey)
                        ->label($label)
                        ->schema($subSchema)
                        ->defaultItems(1)
                        ->addActionLabel('Tambah ' . $label)
                        ->live(debounce: 500);
                    break;
                case 'signature':
                    $isOptional = $field['is_optional_signature'] ?? false;
                    $schema[] = Fieldset::make($label)
                        ->schema([

                            Radio::make($contentKey . '_method')
                                ->label('Metode Input')
                                ->options([
                                    'draw' => 'Gambar Langsung',
                                    'upload' => 'Upload File Image',
                                ])
                                ->columns(2)
                                ->default('draw')
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set) use ($contentKey) {
                                    $set($contentKey . '_draw', null);
                                    $set($contentKey . '_upload', null);
                                })->columnSpanFull(),

                            SignaturePad::make($contentKey . '_draw')
                                ->label('Gambar Tanda Tangan')
                                ->downloadable()                    // Allow download of the signature (defaults to false)
                                ->downloadableFormats([             // Available formats for download (defaults to all)
                                    DownloadableFormat::PNG,
                                    DownloadableFormat::JPG,
                                    DownloadableFormat::SVG,
                                ])
                                ->exportBackgroundColor('rgba(0,0,0,0)')
                                ->exportPenColor('#000000')
                                ->backgroundColor('#ffffff')       // White background on light mode
                                ->backgroundColorOnDark('#111111') // Transparent background to let Tailwind classes show
                                ->penColor('#000000')              // Black pen on light mode
                                ->penColorOnDark('#ffffff')        // White pen on dark mode
                                ->visible(fn(Get $get) => $get($contentKey . '_method') === 'draw')
                                ->required(!$isOptional)
                                ->default(null)
                                ->columnSpanFull()
                                ->live(debounce: 500),
                            FileUpload::make($contentKey . '_upload')
                                ->label('Upload Tanda Tangan')
                                ->image()
                                ->disk('public')
                                ->directory('signatures')
                                ->visible(fn(Get $get) => $get($contentKey . '_method') === 'upload')
                                ->required(!$isOptional)
                                ->default(null)
                                ->columnSpanFull()
                                ->live(debounce: 500),
                        ]);
                    break;
                case 'text':
                default:
                    $schema[] = TextInput::make($contentKey)
                        ->label($label)
                        ->required()
                        ->live(debounce: 500);
                    break;
            }
        }

        return $schema;
    }
}
