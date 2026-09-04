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

        $loadPath = $filePath;
        $tempDocx = null;

        // Preprocess word/document.xml to convert <w:tab/> into non-breaking spaces so indents/tabs are retained
        try {
            $tempDocx = tempnam(sys_get_temp_dir(), 'docx_tab_') . '.docx';
            copy($filePath, $tempDocx);

            $zip = new \ZipArchive();
            if ($zip->open($tempDocx) === true) {
                $docXml = $zip->getFromName('word/document.xml');
                if ($docXml !== false && stripos($docXml, '<w:tab') !== false) {
                    $docXml = preg_replace('/<w:tab\s*(\/)?>/i', '<w:t xml:space="preserve">&#160;&#160;&#160;&#160;</w:t>', $docXml);
                    $zip->addFromString('word/document.xml', $docXml);
                }
                $zip->close();
                $loadPath = $tempDocx;
            }
        } catch (\Throwable $e) {
            $loadPath = $filePath;
        }

        try {
            $phpWord = IOFactory::load($loadPath);
            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');

            $tmpHtmlFile = tempnam(sys_get_temp_dir(), 'html');
            $htmlWriter->save($tmpHtmlFile);
            $html = file_get_contents($tmpHtmlFile);
            @unlink($tmpHtmlFile);
        } finally {
            if ($tempDocx && file_exists($tempDocx)) {
                @unlink($tempDocx);
            }
        }

        // Preserve <style> rules from <head> to keep margins, paddings, and font metrics
        $styleContent = '';
        if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $html, $styleMatches)) {
            $cssRules = $styleMatches[1];
            // Scope generic body and * selectors to .docx-preview-wrapper so they do not leak into outer UI
            $scopedCss = preg_replace('/\b(body|\*)\b(?=[^{]*\{)/', '.docx-preview-wrapper', $cssRules);
            $styleContent = '<style>' . $scopedCss . '</style>';
        }

        $bodyContent = $html;
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $bodyContent = $matches[1];
        }

        return $styleContent . $bodyContent;
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
