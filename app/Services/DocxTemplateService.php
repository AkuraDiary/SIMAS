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
