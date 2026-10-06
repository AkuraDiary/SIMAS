<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\Surat;
use App\Models\Template;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class DocxTemplateService
{
    /**
     * Convert a DOCX file to pure HTML, termasuk ekstraksi otomatis Kop Surat (Header), Footer, dan media/logo.
     */
    public function convertToHtml(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File template DOCX tidak ditemukan.");
        }

        $loadPath = $filePath;
        $tempDocx = null;
        $headerHtml = '';
        $footerHtml = '';

        // 1. Ekstraksi Kop Surat (Header) & Footer langsung dari OpenXML ZIP
        try {
            $headerHtml = $this->extractHeaderFromDocx($filePath);
            $footerHtml = $this->extractFooterFromDocx($filePath);
        } catch (\Throwable $e) {
            // Fallback gracefully jika arsip ZIP tidak memiliki header/footer
            $headerHtml = '';
            $footerHtml = '';
        }

        // 2. Preprocess word/document.xml untuk mempertahankan indentasi & tabulasi
        try {
            $tempDocx = tempnam(sys_get_temp_dir(), 'docx_tab_') . '.docx';
            copy($filePath, $tempDocx);

            $zip = new ZipArchive();
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

        // 3. Konversi Body Utama menggunakan PhpWord HTML Writer
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

        // 4. Scope CSS dari <head> agar tidak bocor ke UI luar
        $styleContent = '';
        if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $html, $styleMatches)) {
            $cssRules = $styleMatches[1];
            $scopedCss = preg_replace('/\b(body|\*)\b(?=[^{]*\{)/', '.docx-preview-wrapper', $cssRules);
            $styleContent = '<style>' . $scopedCss . '</style>';
        }

        $bodyContent = $html;
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $bodyContent = $matches[1];
        }

        // Rapikan spacing paragraf kosong berurutan agar tampilan editor tidak renggang
        $bodyContent = preg_replace('/(<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>\s*){2,}/i', '<p style="margin: 4px 0;">&nbsp;</p>', $bodyContent);

        // 5. Gabungkan Kop Surat (Header) di atas, Body di tengah, dan Footer di bawah
        $fullHtml = $headerHtml . $bodyContent . $footerHtml;

        return $styleContent . $fullHtml;
    }

    /**
     * Ekstrak Kop Surat (Header) beserta logo/gambar dari part word/header*.xml menjadi HTML dengan Base64 image.
     */
    protected function extractHeaderFromDocx(string $filePath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        // Cari file header utama (biasanya word/header1.xml)
        $headerXmlContent = null;
        $relsXmlContent = null;

        for ($i = 1; $i <= 5; $i++) {
            $hName = "word/header{$i}.xml";
            if ($zip->locateName($hName) !== false) {
                $content = $zip->getFromName($hName);
                if (!empty(trim($content))) {
                    $headerXmlContent = $content;
                    $relsXmlContent = $zip->getFromName("word/_rels/header{$i}.xml.rels");
                    break;
                }
            }
        }

        if (!$headerXmlContent) {
            $zip->close();
            return '';
        }

        $html = $this->parseOpenXmlPartToHtml($headerXmlContent, $relsXmlContent, $zip);
        $zip->close();

        if (empty(trim(strip_tags($html, '<img>')))) {
            return '';
        }

        // Bungkus Kop Surat dengan pembatas garis ganda resmi
        return '<div class="docx-kop-surat" style="margin-bottom: 25px; border-bottom: 3px double #000; padding-bottom: 12px; text-align: center;">' .
            $html .
            '</div>';
    }

    /**
     * Ekstrak Footer dari part word/footer*.xml menjadi HTML.
     */
    protected function extractFooterFromDocx(string $filePath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $footerXmlContent = null;
        $relsXmlContent = null;

        for ($i = 1; $i <= 5; $i++) {
            $fName = "word/footer{$i}.xml";
            if ($zip->locateName($fName) !== false) {
                $content = $zip->getFromName($fName);
                if (!empty(trim($content))) {
                    $footerXmlContent = $content;
                    $relsXmlContent = $zip->getFromName("word/_rels/footer{$i}.xml.rels");
                    break;
                }
            }
        }

        if (!$footerXmlContent) {
            $zip->close();
            return '';
        }

        $html = $this->parseOpenXmlPartToHtml($footerXmlContent, $relsXmlContent, $zip);
        $zip->close();

        if (empty(trim(strip_tags($html, '<img>')))) {
            return '';
        }

        return '<div class="docx-footer" style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 9pt; color: #666; text-align: center;">' .
            $html .
            '</div>';
    }

    /**
     * Parser sederhana OpenXML (w:tbl, w:p, w:t, gambar) ke HTML bersih dengan Base64 Data URI.
     */
    protected function parseOpenXmlPartToHtml(string $xmlContent, ?string $relsXmlContent, ZipArchive $zip): string
    {
        // 1. Petakan Relasi Gambar (rId -> media path)
        $mediaMap = [];
        if ($relsXmlContent) {
            try {
                $sRels = simplexml_load_string($relsXmlContent);
                if ($sRels) {
                    foreach ($sRels->Relationship as $rel) {
                        $id = (string) $rel['Id'];
                        $target = (string) $rel['Target'];
                        $mediaMap[$id] = 'word/' . ltrim($target, '/');
                    }
                }
            } catch (\Throwable $e) {}
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadXML($xmlContent);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xpath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');

        $output = '';

        // Cari tabel (<w:tbl>) atau paragraf langsung (<w:p>)
        $bodyNodes = $xpath->query('//w:tbl | //w:hdr/w:p | //w:ftr/w:p');

        foreach ($bodyNodes as $node) {
            if ($node->nodeName === 'w:tbl') {
                $output .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 8px; border: none;">';
                $rows = $xpath->query('.//w:tr', $node);
                foreach ($rows as $row) {
                    $output .= '<tr>';
                    $cells = $xpath->query('.//w:tc', $row);
                    foreach ($cells as $cell) {
                        $cellHtml = '';
                        $cellParas = $xpath->query('.//w:p', $cell);
                        foreach ($cellParas as $cp) {
                            $cellHtml .= $this->renderParagraphNode($cp, $xpath, $mediaMap, $zip);
                        }
                        $output .= '<td style="vertical-align: middle; padding: 4px; border: none;">' . $cellHtml . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            } elseif ($node->nodeName === 'w:p') {
                // Pastikan bukan paragraf di dalam tabel
                if ($node->parentNode && $node->parentNode->nodeName !== 'w:tc') {
                    $output .= $this->renderParagraphNode($node, $xpath, $mediaMap, $zip);
                }
            }
        }

        return $output;
    }

    /**
     * Render satu paragraf OpenXML menjadi tag <p> HTML dengan styling & gambar.
     */
    protected function renderParagraphNode(\DOMNode $pNode, \DOMXPath $xpath, array $mediaMap, ZipArchive $zip): string
    {
        $align = 'left';
        $jcNodes = $xpath->query('.//w:pPr/w:jc/@w:val', $pNode);
        if ($jcNodes->length > 0) {
            $val = $jcNodes->item(0)->nodeValue;
            if (in_array($val, ['center', 'right', 'both'])) {
                $align = $val === 'both' ? 'justify' : $val;
            }
        }

        $pContent = '';

        // 1. Deteksi gambar di dalam paragraf
        $blipNodes = $xpath->query('.//a:blip/@r:embed | .//v:imagedata/@r:id', $pNode);
        foreach ($blipNodes as $blip) {
            $rId = $blip->nodeValue;
            if (isset($mediaMap[$rId])) {
                $imgPath = $mediaMap[$rId];
                if ($zip->locateName($imgPath) !== false) {
                    $imgData = $zip->getFromName($imgPath);
                    $ext = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
                    $mime = ($ext === 'png') ? 'image/png' : (($ext === 'gif') ? 'image/gif' : 'image/jpeg');
                    $base64 = base64_encode($imgData);
                    $pContent .= '<img src="data:' . $mime . ';base64,' . $base64 . '" style="max-height: 80px; max-width: 140px; object-fit: contain; display: inline-block; margin: 4px;" />';
                }
            }
        }

        // 2. Deteksi teks dan styling (bold, italic, dsb.)
        $runNodes = $xpath->query('.//w:r', $pNode);
        foreach ($runNodes as $run) {
            $isBold = $xpath->query('.//w:rPr/w:b', $run)->length > 0;
            $isItalic = $xpath->query('.//w:rPr/w:i', $run)->length > 0;
            $textNodes = $xpath->query('.//w:t', $run);

            $runText = '';
            foreach ($textNodes as $t) {
                $runText .= htmlspecialchars($t->nodeValue);
            }

            if (!empty($runText)) {
                if ($isBold) $runText = '<strong>' . $runText . '</strong>';
                if ($isItalic) $runText = '<em>' . $runText . '</em>';
                $pContent .= $runText;
            }
        }

        if (empty(trim(strip_tags($pContent, '<img>')))) {
            return '';
        }

        return '<p style="text-align: ' . $align . '; margin: 2px 0; line-height: 1.25;">' . $pContent . '</p>';
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
     * Unduh Template Asli (Kosong).
     */
    public function downloadBlankDocx(Template $template): string
    {
        $media = $template->getFirstMedia('template_file');
        if ($media && file_exists($media->getPath())) {
            return $media->getPath();
        }

        // Fallback: Convert HTML ke DOCX
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $html = $template->content_html ?? '<p>Template kosong</p>';

        Html::addHtml($section, $html, false, false);

        $tempFile = tempnam(sys_get_temp_dir(), 'blank_template_') . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Unduh Draft Surat Terisi (.docx) dengan penanganan placeholder Tanda Tangan & QR Code.
     */
    public function downloadFilledDocx(Surat $surat): string
    {
        $template = $surat->template;
        $media = $template?->getFirstMedia('template_file');
        $data = $surat->content ?? [];
        $tempFilesToClean = [];

        if (!empty($surat->nomor_surat)) {
            $data['nomor_surat'] = $surat->nomor_surat;
        }
        if (!empty($surat->tanggal_kirim)) {
            $data['tanggal_surat'] = \Carbon\Carbon::parse($surat->tanggal_kirim)->translatedFormat('d F Y');
            $data['tanggal_terbit'] = $data['tanggal_surat'];
        }
        if (isset($data['nomor_surat_tags']) && is_array($data['nomor_surat_tags'])) {
            foreach ($data['nomor_surat_tags'] as $k => $v) {
                $data[$k] = $v;
                $data[strtolower($k)] = $v;
            }
        }

        try {
            if ($media && file_exists($media->getPath())) {
                // Skenario B.1: Template berasal dari Upload .docx (TemplateProcessor)
                $processor = new TemplateProcessor($media->getPath());
                $docVariables = $processor->getVariables();

                // 1. Penanganan Tanda Tangan Pemohon / Variabel Signature Form
                foreach ($template->field_variables ?? [] as $field) {
                    $key = $field['key'] ?? '';
                    if (!$key || ($field['type'] ?? '') !== 'signature') continue;

                    $method = $data[$key . '_method'] ?? 'draw';
                    $imagePath = null;

                    if ($method === 'draw' && !empty($data[$key . '_draw'])) {
                        $imagePath = $this->createTempImageFromDataUri($data[$key . '_draw']);
                    } elseif ($method === 'upload' && !empty($data[$key . '_upload'])) {
                        $rawPath = is_array($data[$key . '_upload']) ? reset($data[$key . '_upload']) : $data[$key . '_upload'];
                        $fullPath = storage_path('app/private/' . $rawPath);
                        if (!file_exists($fullPath)) {
                            $fullPath = storage_path('app/public/' . $rawPath);
                        }
                        if (file_exists($fullPath)) {
                            $imagePath = $fullPath;
                        }
                    }

                    if ($imagePath && file_exists($imagePath)) {
                        if (str_starts_with($imagePath, sys_get_temp_dir())) {
                            $tempFilesToClean[] = $imagePath;
                        }
                        if (in_array($key, $docVariables)) {
                            $processor->setImageValue($key, [
                                'path' => $imagePath,
                                'width' => 140,
                                'height' => 70,
                                'ratio' => true,
                            ]);
                        }
                    }
                }

                // 2. Penanganan TTD Pejabat / Approver (suratTtds)
                foreach ($surat->suratTtds as $ttd) {
                    if ($ttd->placeholder_key && in_array($ttd->placeholder_key, $docVariables)) {
                        if ($ttd->qr_code_path) {
                            $fullPath = storage_path('app/private/' . $ttd->qr_code_path);
                            if (!file_exists($fullPath)) {
                                $fullPath = storage_path('app/public/' . $ttd->qr_code_path);
                            }
                            if (file_exists($fullPath)) {
                                $processor->setImageValue($ttd->placeholder_key, [
                                    'path' => $fullPath,
                                    'width' => 80,
                                    'height' => 80,
                                    'ratio' => true,
                                ]);
                            }
                        }
                    }
                }

                // 3. Penanganan QR Code Dokumen Utama (${qr_code})
                if (in_array('qr_code', $docVariables)) {
                    $verifyUrl = url('/lacak?code=' . ($surat->tracking_code ?: 'REQ-' . $surat->id));
                    $qrTemp = tempnam(sys_get_temp_dir(), 'qr_doc_') . '.png';
                    QrCode::format('png')->size(200)->margin(1)->generate($verifyUrl, $qrTemp);
                    $tempFilesToClean[] = $qrTemp;

                    $processor->setImageValue('qr_code', [
                        'path' => $qrTemp,
                        'width' => 80,
                        'height' => 80,
                        'ratio' => true,
                    ]);
                }

                // 4. Ganti nilai teks skalar
                foreach ($data as $key => $value) {
                    if (is_scalar($value) && in_array($key, $docVariables)) {
                        $valStr = (string) $value;
                        // Rapikan multiple redundant spaces/newlines (max 2 consecutive newlines)
                        $valStr = preg_replace("/[\r\n]{3,}/", "\n\n", $valStr);
                        if (str_contains($valStr, "\n")) {
                            $escaped = htmlspecialchars($valStr);
                            $formatted = str_replace("\n", '<w:br/>', $escaped);
                            $processor->setValue($key, $formatted);
                        } else {
                            $processor->setValue($key, $valStr);
                        }
                    }
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'filled_surat_') . '.docx';
                $processor->saveAs($tempFile);
                return $tempFile;
            }

            // Skenario B.2: Template berasal dari TinyEditor (HTML)
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            $renderedHtml = app(\App\Services\PlaceholderService::class)->renderHtml($template, $data, $surat);
            Html::addHtml($section, $renderedHtml, false, false);

            $tempFile = tempnam(sys_get_temp_dir(), 'filled_surat_') . '.docx';
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            return $tempFile;
        } finally {
            // Bersihkan semua file gambar sementara
            foreach ($tempFilesToClean as $tf) {
                if (file_exists($tf)) {
                    @unlink($tf);
                }
            }
        }
    }

    /**
     * Helper untuk membuat file gambar temporer dari Base64 Data URI.
     */
    protected function createTempImageFromDataUri(string $dataUri): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUri, $type)) {
            return null;
        }

        $data = substr($dataUri, strpos($dataUri, ',') + 1);
        $decoded = base64_decode($data);
        if ($decoded === false) {
            return null;
        }

        $ext = strtolower($type[1]);
        $tempPath = tempnam(sys_get_temp_dir(), 'sig_') . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        file_put_contents($tempPath, $decoded);

        return $tempPath;
    }
}
