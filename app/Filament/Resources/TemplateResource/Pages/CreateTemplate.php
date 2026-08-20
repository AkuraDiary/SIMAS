<?php

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource\TemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->render_engine === 'DOCX') {
            $media = $this->record->getFirstMedia('template_file');
            if ($media) {
                try {
                    $html = app(\App\Services\DocxTemplateService::class)->convertToHtml($media->getPath());
                    $this->record->updateQuietly(['content_html' => $html]);
                } catch (\Exception $e) {
                    // Ignore exception if parsing fails, preview will just be empty
                }
            }
        }
    }
}
