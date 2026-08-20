<?php

namespace App\Filament\Resources\TemplateResource\Pages;

use App\Filament\Resources\TemplateResource\TemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTemplate extends EditRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->render_engine === 'DOCX') {
            $media = $this->record->getFirstMedia('template_file');
            if ($media) {
                try {
                    $html = app(\App\Services\DocxTemplateService::class)->convertToHtml($media->getPath());
                    $this->record->updateQuietly(['content_html' => $html]);
                } catch (\Exception $e) {
                    // Ignore exception if parsing fails
                }
            }
        }
    }
}
