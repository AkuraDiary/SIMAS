<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class ConvertWordToPdf
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        // dd('Listener fired');
        $media = $event->media;

        if ($media->collection_name !== 'lampiran-surat') {
            return;
        }

        if (!in_array($media->mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ])) {
            return;
        }

        if ($media->getCustomProperty('has_preview_pdf')) {
            return;
        }

        Settings::setPdfRenderer(
            Settings::PDF_RENDERER_DOMPDF,
            base_path('vendor/dompdf/dompdf')
        );

        $phpWord = IOFactory::load($media->getPath());
        if (!file_exists($media->getPath())) {
            dd('Original file not found: ' . $media->getPath());
            return;
        }
        $writer = IOFactory::createWriter($phpWord, 'PDF');

        $pdfPath = storage_path('app/temp_preview_'.$media->id.'.pdf');
        $writer->save($pdfPath);
        if (!file_exists($pdfPath)) {
            dd('PDF generation failed for media ID: ' . $media->id);
            return;
        }
        $previewMedia = $media->model
            ->addMedia($pdfPath)
            ->usingFileName(pathinfo($media->file_name, PATHINFO_FILENAME).'_preview.pdf')
            ->withCustomProperties([
                'preview_for' => $media->id
            ])
            ->toMediaCollection('lampiran-preview');

        $media->setCustomProperty('has_preview_pdf', true);
        $media->setCustomProperty('preview_media_id', $previewMedia->id);
        $media->save();

        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }
    }
}
