<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\Surat;
use App\Models\Template;

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

        /**
     * Unduh Template Asli (Kosong)
     * Jika admin upload .docx, berikan file itu.
     * Jika admin pakai TinyEditor (HTML), convert HTML kosong ke .docx
     */
    public function downloadBlankDocx(Template $template): string
    {
        // 1. Coba ambil file fisik dari MediaLibrary
        $media = $template->getFirstMedia('template_file');
        if ($media && file_exists($media->getPath())) {
            return $media->getPath();
        }

        // 2. Fallback: Convert HTML (TinyEditor) ke DOCX
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $html = $template->content_html ?? '<p>Template kosong</p>';

        Html::addHtml($section, $html, false, false);

        $tempFile = tempnam(sys_get_temp_dir(), 'blank_template_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Unduh Draft Surat (Berisi Data)
     * Jika template dari .docx, kita gunakan TemplateProcessor.
     * Jika template dari HTML, kita render HTML-nya lalu convert ke .docx.
     */
    public function downloadFilledDocx(Surat $surat): string
    {
        $template = $surat->template;
        $media = $template?->getFirstMedia('template_file');

        if ($media && file_exists($media->getPath())) {
            // Skenario B.1: Template berasal dari Upload .docx
            $processor = new TemplateProcessor($media->getPath());
            $data = $surat->content ?? [];

            // Ganti tag {{ nama }} atau ${ nama } di dalam DOCX
            foreach ($data as $key => $value) {
                if (is_scalar($value)) {
                    // PhpWord TemplateProcessor by default mencari ${key}
                    $processor->setValue($key, $value);
                }
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'filled_surat_') . '.docx';
            $processor->saveAs($tempFile);
            return $tempFile;
        }

        // Skenario B.2: Template berasal dari TinyEditor (HTML)
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Render HTML yang sudah berisi data user menggunakan layanan yang sudah ada
        $renderedHtml = app(\App\Services\PlaceholderService::class)->renderHtml($template, $surat->content ?? []);

        // Konversi HTML yang sudah terisi ke Word
        Html::addHtml($section, $renderedHtml, false, false);

        $tempFile = tempnam(sys_get_temp_dir(), 'filled_surat_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return $tempFile;
    }
}
