<?php

namespace App\Services;

use App\Models\Template;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Saade\FilamentAutograph\Forms\Components\Enums\DownloadableFormat;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class PlaceholderService
{

    /**
     * Cek apakah user yang sedang login berhak menggeser / mengubah ukuran TTD/QR tertentu.
     */
    public function canUserAdjustSignature(?\App\Models\Surat $surat, ?Template $template, string $key): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return $surat === null; // Guest saat mengisi form pengajuan baru
        }

        // Saat masih di form pembuatan surat baru ($surat === null)
        if (!$surat) {
            // Pembuat hanya boleh mengatur field signature miliknya atau qr_code dokumen
            if ($key === 'qr_code') {
                return true;
            }
            foreach ($template?->field_variables ?? [] as $field) {
                if (($field['key'] ?? '') === $key && ($field['type'] ?? '') === 'signature') {
                    return true;
                }
            }
            return false;
        }

        // Jika surat sudah selesai/terbit/ditolak, kunci posisi di UI kecuali sedang proses revisi/approval
        if (in_array($surat->status_surat, ['DITOLAK', 'ARSIP'], true)) {
            return false;
        }

        // 1. Jika key berasal dari field_variables bertipe signature (TTD pemohon/pengirim)
        foreach ($template?->field_variables ?? [] as $field) {
            if (($field['key'] ?? '') === $key && ($field['type'] ?? '') === 'signature') {
                return (int) $surat->user_pembuat_id === (int) $user->id;
            }
        }

        // 2. Jika sudah ada record di surat_ttds untuk placeholder_key ini (atau fallback ttd_user_{id}), hanya pemilik TTD yang berhak
        if ($key === 'ttd_user_' . $user->id) {
            return true;
        }
        $existingTtd = $surat->suratTtds->firstWhere('placeholder_key', $key);
        if ($existingTtd) {
            return (int) $existingTtd->user_id === (int) $user->id;
        }

        // 3. Cek dari approval_path: apakah jabatan aktif user saat ini adalah pemilik placeholder_key tersebut
        $userJabatanIds = [];
        if ($activeJab = $user->getActiveJabatan()) {
            $userJabatanIds[] = (int) $activeJab->jabatan_id;
        }
        if ($pegawai = $user->pegawai) {
            $userJabatanIds = array_merge(
                $userJabatanIds,
                $pegawai->jabatanAktif()->pluck('jabatan_id')->map(fn($id) => (int) $id)->toArray()
            );
        }
        $userJabatanIds = array_unique(array_filter($userJabatanIds));

        $approvalPath = $surat->approval_path ?? $template?->approval_path ?? [];
        if (is_array($approvalPath)) {
            foreach ($approvalPath as $step) {
                if (($step['placeholder_key'] ?? '') === $key) {
                    $stepJabatanId = (int) ($step['jabatan_id'] ?? 0);
                    return in_array($stepJabatanId, $userJabatanIds, true);
                }
            }
        }

        // 4. Untuk qr_code dokumen utama: pembuat atau pejabat aktif boleh menyesuaikan
        if ($key === 'qr_code') {
            return (int) $surat->user_pembuat_id === (int) $user->id || !empty($userJabatanIds);
        }

        return false;
    }

    /**
     * Render the template's HTML by injecting the provided data.
     */
    public function renderHtml(Template $template, array $data, ?\App\Models\Surat $surat = null, bool $forPdf = false): string
    {
        $html = $template->content_html ?? '';

        // Inject Reserved System Variables if a Surat instance is provided
        if (!isset($data['nomor_surat']) || empty($data['nomor_surat'])) {
            $data['nomor_surat'] = $surat?->nomor_surat ?? '_______________________';
        }
        if (!isset($data['tanggal_surat']) || empty($data['tanggal_surat'])) {
            $data['tanggal_surat'] = ($surat && $surat->tanggal_kirim)
                ? \Carbon\Carbon::parse($surat->tanggal_kirim)->translatedFormat('d F Y')
                : '.......................';
        }
        $data['tanggal_terbit'] = $data['tanggal_surat']; // Alias just in case

        $canDragQr = !$forPdf && $this->canUserAdjustSignature($surat, $template, 'qr_code');
        $qrLockAttr = $canDragQr ? '' : ' data-locked="true"';
        $qrCursor = $canDragQr ? 'grab' : 'default';
        $qrResizeHandle = $canDragQr ? '<div class="signature-resize-handle" title="Tarik untuk mengubah ukuran"></div>' : '';

        if (!isset($data['qr_code']) || empty($data['qr_code'])) {
            $data['qr_code'] = '<div class="draggable-signature" data-key="qr_code"' . $qrLockAttr . ' style="text-align: center; display: inline-block; cursor: ' . $qrCursor . '; position: relative; width: 80px;">' .
                '<img src="' . asset('images/qr_placeholder.png') . '" style="width: 100%; height: auto; display: block;" pointer-events="none" />' .
                $qrResizeHandle .
                '</div>';
        }

        if ($surat) {
            // Inject QR Code Dokumen Utama (Fleksibel & Draggable)
            $qrX = (int) ($data['qr_code_posisi_x'] ?? ($surat->content['qr_code_posisi_x'] ?? 0));
            $qrY = (int) ($data['qr_code_posisi_y'] ?? ($surat->content['qr_code_posisi_y'] ?? 0));
            if (abs($qrX) > 700) $qrX = 0;
            if (abs($qrY) > 1000) $qrY = 0;
            $qrWidth = (int) ($data['qr_code_width'] ?? ($surat->content['qr_code_width'] ?? 80));
            $qrStyle = "text-align: center; display: inline-block; cursor: {$qrCursor}; position: relative; left: {$qrX}px; top: {$qrY}px; width: {$qrWidth}px;";
            $data['qr_code'] = '<div class="draggable-signature" data-key="qr_code"' . $qrLockAttr . ' style="' . $qrStyle . '">' .
                '<img src="' . asset('images/qr_placeholder.png') . '" style="width: 100%; height: auto; display: block;" pointer-events="none" />' .
                $qrResizeHandle .
                '</div>';

            // Inject TTD & QR Code dari Database (surat_ttds)
            $unplacedTtdHtmls = [];
            foreach ($surat->suratTtds as $ttd) {
                $pKey = $ttd->placeholder_key ?: ('ttd_user_' . $ttd->user_id);

                // Jika penandatangan memilih menyembunyikan visual TTD/QR pada lembar surat
                if (!$ttd->is_visible) {
                    $data[$pKey] = '';
                    continue;
                }

                $width = $data[$pKey . '_width'] ?? ($surat->content[$pKey . '_width'] ?? null);
                $widthVal = $width ? (int) $width : 160;
                $imgSize = max(60, min(220, $widthVal - 20));

                $qrImg = '';
                if ($ttd->qr_code_path) {
                    $fullPath = storage_path('app/private/' . $ttd->qr_code_path);
                    if (!file_exists($fullPath)) {
                        $fullPath = storage_path('app/public/' . $ttd->qr_code_path);
                    }
                    if (file_exists($fullPath)) {
                        $mime = mime_content_type($fullPath) ?: 'image/png';
                        $base64Data = base64_encode(file_get_contents($fullPath));
                        $qrImg = '<img src="data:' . $mime . ';base64,' . $base64Data . '" style="width: 100%; max-width: ' . $imgSize . 'px; height: auto; margin-bottom: 4px; display: block;" pointer-events="none" />';
                    }
                }

                // Cek jika mode kombinasi (both): ada TTD goresan tambahan di content
                $extraTtdImg = '';
                $extraTtdPath = $surat->content[$pKey . '_ttd_path'] ?? null;
                if ($extraTtdPath) {
                    $extraFull = storage_path('app/private/' . $extraTtdPath);
                    if (file_exists($extraFull)) {
                        $extraMime = mime_content_type($extraFull) ?: 'image/png';
                        $extraB64 = base64_encode(file_get_contents($extraFull));
                        $extraTtdImg = '<img src="data:' . $extraMime . ';base64,' . $extraB64 . '" style="width: 100%; max-width: ' . $imgSize . 'px; height: auto; margin-bottom: 4px; display: block;" pointer-events="none" />';
                    }
                }

                $showName = (bool) ($data[$pKey . '_show_name'] ?? ($surat->content[$pKey . '_show_name'] ?? true));
                $namaTerang = $ttd->user->nama_lengkap ?? 'Pejabat Berwenang';
                $nameHtml = $showName
                    ? ('<b><u>' . htmlspecialchars($namaTerang) . '</u></b><br><span style="font-size: 10pt;">' . htmlspecialchars($ttd->jabatan_saat_ttd ?? '') . '</span>')
                    : '';

                $x = (int) ($data[$pKey . '_posisi_x'] ?? ($surat->content[$pKey . '_posisi_x'] ?? $ttd->posisi_x ?? 0));
                $y = (int) ($data[$pKey . '_posisi_y'] ?? ($surat->content[$pKey . '_posisi_y'] ?? $ttd->posisi_y ?? 0));
                if (abs($qrX) > 700) $qrX = 0;
                if (abs($qrY) > 1000) $qrY = 0;

                $canDrag = !$forPdf && $this->canUserAdjustSignature($surat, $template, $pKey);
                $lockAttr = $canDrag ? '' : ' data-locked="true"';
                $cursor = $canDrag ? 'grab' : 'default';
                $resizeHandle = $canDrag ? '<div class="signature-resize-handle" title="Tarik untuk mengubah ukuran"></div>' : '';

                $style = "text-align: left; display: inline-block; cursor: {$cursor}; position: relative; left: {$x}px; top: {$y}px; width: {$widthVal}px;";

                $ttdVisual = '<div class="draggable-signature" data-key="' . $pKey . '"' . $lockAttr . ' style="' . $style . '">' .
                    $qrImg .
                    $extraTtdImg .
                    $nameHtml .
                    $resizeHandle .
                    '</div>';

                $data[$pKey] = $ttdVisual;

                // Jika tag {{ placeholder_key }} tidak tertulis di dalam template HTML, simpan ke daftar fallback bawah surat
                if (!preg_match('/\{\{\s*' . preg_quote($pKey, '/') . '\s*\}\}/', $html)) {
                    $unplacedTtdHtmls[$pKey] = $ttdVisual;
                }
            }
        }

        // Fallback : if content_html is empty but it's a DOCX template, generate it once and save it
        if (empty($html) && $template->render_engine === 'DOCX') {
            $media = $template->getFirstMedia('template_file');
            if ($media && file_exists($media->getPath())) {
                try {
                    $html = app(\App\Services\DocxTemplateService::class)->convertToHtml($media->getPath());
                    $template->updateQuietly(['content_html' => $html]);
                } catch (\Exception $e) {
                    $html = '';
                }
            }
        }

        if (empty($html)) return '';

        $html = $this->normalizeTableLoops($html);

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
                        if (is_array($row)) {
                            foreach ($row as $childKey => $childValue) {
                                $rowContent = preg_replace('/\{\{\s*' . preg_quote($childKey, '/') . '\s*\}\}/', (string) $childValue, $rowContent);
                            }
                        }
                        $renderedRows .= $rowContent;
                    }
                }

                $html = str_replace($match[0], $renderedRows, $html);
            }
        }

        // Handle flat vars (text, date, number, etc.) - amankan karakter '$'
        foreach ($data as $key => $value) {
            if (!is_array($value) && $value !== null) {
                // Jika sengaja dikosongkan karena is_visible = false pada surat_ttds, tetap replace jadi kosong
                $isHiddenTtd = $surat && $surat->suratTtds->where('placeholder_key', $key)->where('is_visible', false)->isNotEmpty();
                if ($value !== '' || $isHiddenTtd) {
                    $html = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', str_replace('$', '\$', (string) $value), $html);
                }
            }
        }

        // Handle signature vars specifically
        foreach ($template->field_variables ?? [] as $field) {
            $key = $field['key'] ?? '';
            if (!$key || $field['type'] !== 'signature') continue;

            $method = $data[$key . '_method'] ?? 'draw';
            $val = '';
            $width = $data[$key . '_width'] ?? ($surat ? ($surat->content[$key . '_width'] ?? null) : null);
            $widthVal = $width ? (int) $width : 160;

            if ($method === 'draw') {
                $val = $data[$key . '_draw'] ?? '';
                if ($val) {
                    $val = '<img src="' . htmlspecialchars($val) . '" style="width: 100%; max-width: ' . $widthVal . 'px; height: auto; max-height: 180px; pointer-events: none; display: block;" />';
                }
            } elseif ($method === 'upload') {
                $uploadFile = $data[$key . '_upload'] ?? null;
                if (is_array($uploadFile)) {
                    $uploadFile = reset($uploadFile);
                }
                if ($uploadFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    try {
                        $mime = $uploadFile->getMimeType() ?: 'image/png';
                        $base64 = base64_encode(file_get_contents($uploadFile->getRealPath()));
                        $val = '<img src="data:' . $mime . ';base64,' . $base64 . '" style="width: 100%; max-width: ' . $widthVal . 'px; height: auto; max-height: 180px; pointer-events: none; display: block;" />';
                    } catch (\Exception $e) {
                        $val = '';
                    }
                } elseif (is_string($uploadFile) && !empty($uploadFile)) {
                    if (str_starts_with($uploadFile, 'data:image/')) {
                        $val = '<img src="' . htmlspecialchars($uploadFile) . '" style="width: 100%; max-width: ' . $widthVal . 'px; height: auto; max-height: 180px; pointer-events: none; display: block;" />';
                    } else {
                        $filePath = storage_path('app/private/' . $uploadFile);
                        if (!file_exists($filePath)) {
                            $filePath = public_path('storage/' . $uploadFile);
                        }
                        if (file_exists($filePath)) {
                            $mime = mime_content_type($filePath) ?: 'image/png';
                            $base64 = base64_encode(file_get_contents($filePath));
                            $val = '<img src="data:' . $mime . ';base64,' . $base64 . '" style="width: 100%; max-width: ' . $widthVal . 'px; height: auto; max-height: 180px; pointer-events: none; display: block;" />';
                        } else {
                            $val = '<img src="/storage/' . htmlspecialchars($uploadFile) . '" style="width: 100%; max-width: ' . $widthVal . 'px; height: auto; max-height: 180px; pointer-events: none; display: block;" />';
                        }
                    }
                }
            }

            if ($val) {
                $x = (int) ($data[$key . '_posisi_x'] ?? ($surat ? ($surat->content[$key . '_posisi_x'] ?? null) : null) ?? 0);
                $y = (int) ($data[$key . '_posisi_y'] ?? ($surat ? ($surat->content[$key . '_posisi_y'] ?? null) : null) ?? 0);
                if (abs($qrX) > 700) $qrX = 0;
                if (abs($qrY) > 1000) $qrY = 0;

                $canDrag = !$forPdf && $this->canUserAdjustSignature($surat, $template, $key);
                $lockAttr = $canDrag ? '' : ' data-locked="true"';
                $cursor = $canDrag ? 'grab' : 'default';
                $resizeHandle = $canDrag ? '<div class="signature-resize-handle" title="Tarik untuk mengubah ukuran"></div>' : '';

                $style = "display: inline-block; cursor: {$cursor}; position: relative; left: {$x}px; top: {$y}px; width: {$widthVal}px;";
                $wrapper = '<div class="draggable-signature" data-key="' . $key . '"' . $lockAttr . ' style="' . $style . '">' . $val . $resizeHandle . '</div>';
                $html = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', str_replace('$', '\$', $wrapper), $html);
            }
        }

        // Clean up remaining un-filled placeholders
        $html = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($data, $surat, $template, $forPdf) {
            $key = $m[1];

            // Pada saat cetak PDF final, jangan tampilkan kotak merah placeholder yang belum ditandatangani
            if ($forPdf && \Illuminate\Support\Str::startsWith(strtolower($key), 'ttd_')) {
                return '';
            }

            if (\Illuminate\Support\Str::startsWith(strtolower($key), 'ttd_')) {
                $x = (int) ($data[$key . '_posisi_x'] ?? ($surat ? ($surat->content[$key . '_posisi_x'] ?? null) : null) ?? 0);
                $y = (int) ($data[$key . '_posisi_y'] ?? ($surat ? ($surat->content[$key . '_posisi_y'] ?? null) : null) ?? 0);
                if (abs($qrX) > 700) $qrX = 0;
                if (abs($qrY) > 1000) $qrY = 0;

                $width = $data[$key . '_width'] ?? ($surat ? ($surat->content[$key . '_width'] ?? null) : null);
                $widthVal = $width ? (int) $width : 140;

                $canDrag = !$forPdf && $this->canUserAdjustSignature($surat, $template, $key);
                $lockAttr = $canDrag ? '' : ' data-locked="true"';
                $cursor = $canDrag ? 'grab' : 'default';
                $borderColor = $canDrag ? '#2563eb' : '#94a3b8';
                $textColor = $canDrag ? '#1d4ed8' : '#64748b';

                $style = "color:{$textColor}; font-weight:bold; cursor: {$cursor}; display: inline-block; border: 1px dashed {$borderColor}; padding: 0.25rem; user-select: none; position: relative; left: {$x}px; top: {$y}px; width: {$widthVal}px;";
                $resizeHandle = $canDrag
                    ? '<div class="signature-resize-handle" style="position: absolute; right: -4px; bottom: -4px; width: 10px; height: 10px; background: #2563eb; border: 1px solid white; border-radius: 50%; cursor: se-resize; z-index: 10;"></div>'
                    : '';
                return '<div class="draggable-signature" data-key="' . $key . '"' . $lockAttr . ' style="' . $style . '">[' . $key . ']' . $resizeHandle . '</div>';
            }

            return '<span style="color:#ef4444; font-weight:bold;">[' . $key . ']</span>';
        }, $html);

        // Jika ada TTD dari surat_ttds atau approval_path yang belum memiliki tag {{ ... }} di dalam isi HTML,
        // sematkan otomatis di bagian bawah dokumen agar tetap muncul & dapat digeser oleh penyetuju!
        if (!empty($unplacedTtdHtmls)) {
            $html .= '<div style="margin-top: 24px; text-align: right;">' . implode('&nbsp;&nbsp;&nbsp;', $unplacedTtdHtmls) . '</div>';
        } elseif (!$forPdf && $surat) {
            // Saat belum ditandatangani (atau preview di modal), tampilkan kotak placeholder bagi approver aktif jika belum ada di HTML
            $approvalPath = $surat->approval_path ?? $template->approval_path ?? [];
            if (is_array($approvalPath)) {
                foreach ($approvalPath as $step) {
                    $stepKey = $step['placeholder_key'] ?? null;
                    if ($stepKey && $this->canUserAdjustSignature($surat, $template, $stepKey)) {
                        $alreadyInHtml = strpos($html, 'data-key="' . $stepKey . '"') !== false;
                        $alreadySigned = $surat->suratTtds->where('placeholder_key', $stepKey)->isNotEmpty();
                        if (!$alreadyInHtml && !$alreadySigned) {
                            $x = (int) ($data[$stepKey . '_posisi_x'] ?? ($surat->content[$stepKey . '_posisi_x'] ?? 0));
                            $y = (int) ($data[$stepKey . '_posisi_y'] ?? ($surat->content[$stepKey . '_posisi_y'] ?? 0));
                            $w = (int) ($data[$stepKey . '_width'] ?? ($surat->content[$stepKey . '_width'] ?? 160));
                            $previewBoxContent = $data['__live_approver_preview_' . $stepKey] ?? ('[' . $stepKey . ']');
                            $style = "color:#1d4ed8; font-weight:bold; cursor: grab; display: inline-block; border: 1px dashed #2563eb; padding: 0.25rem; user-select: none; position: relative; left: {$x}px; top: {$y}px; width: {$w}px; text-align: left;";
                            $resizeHandle = '<div class="signature-resize-handle" title="Tarik untuk mengubah ukuran"></div>';
                            $html .= '<div style="margin-top: 24px; text-align: right;"><div class="draggable-signature" data-key="' . $stepKey . '" style="' . $style . '">' . $previewBoxContent . $resizeHandle . '</div></div>';
                        }
                    }
                }
            }
        }

        // Normalisasi token pemisah halaman (<!-- pagebreak --> dari TinyMCE) menjadi elemen .page-break
        $html = preg_replace('/(?:<p>\s*)?<!--\s*pagebreak\s*-->(?:\s*<\/p>)?/i', '<div class="page-break"></div>', $html);

        // Untuk mode Live Preview di Browser (bukan DomPDF), tampilkan Kop, Footer, Background, dan garis pemisah halaman
        $kopPreviewHtml = '';
        $footerPreviewHtml = '';
        $bgStyle = '';
        $pageBreakScreenCss = '';

        if (!$forPdf) {
            if ($kopUri = $template->getMediaDataUri('kop_surat')) {
                $kopPreviewHtml = '<div class="surat-kop-preview" style="margin-bottom: 16px;"><img src="' . $kopUri . '" style="width: 100%; height: auto; display: block;" /></div>';
            }
            if ($footerUri = $template->getMediaDataUri('footer_surat')) {
                $footerPreviewHtml = '<div class="surat-footer-preview" style="margin-top: 24px;"><img src="' . $footerUri . '" style="width: 100%; height: auto; display: block;" /></div>';
            }
            if ($bgUri = $template->getMediaDataUri('background_surat')) {
                $bgStyle = "background-image: url('{$bgUri}'); background-size: 100% 100%; background-repeat: no-repeat;";
            }
            $pageBreakScreenCss = '
                .page-break {
                    border-top: 2px dashed #94a3b8;
                    margin: 28px -2.5rem;
                    position: relative;
                    height: 16px;
                    background: #f1f5f9;
                }
                .page-break::after {
                    content: "PEMISAH HALAMAN (HALAMAN BARU)";
                    position: absolute;
                    top: -9px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #e2e8f0;
                    color: #475569;
                    font-size: 10px;
                    font-weight: 700;
                    padding: 1px 10px;
                    border-radius: 9999px;
                    letter-spacing: 0.05em;
                }
            ';
        } else {
            // Saat cetak PDF di DomPDF, pastikan elemen .page-break tidak memiliki tinggi/garis/teks visual apa pun
            $pageBreakScreenCss = '
                .page-break {
                    border: none !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    height: 0 !important;
                    background: transparent !important;
                }
            ';
        }

        $css = '<style>
            .docx-preview-wrapper { position: relative; ' . $bgStyle . ' }
            .docx-preview-wrapper table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
            .page-break {
                page-break-after: always;
                break-after: page;
            }
            ' . $pageBreakScreenCss . '
            @media print {
                .signature-resize-handle { display: none !important; }
            }
        </style>';

        return '<div class="docx-preview-wrapper">' . $css . $kopPreviewHtml . $html . $footerPreviewHtml . '</div>';
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
     * Fixes table row loops in Rich Text Editors by moving [loop] tags outside the <tr>
     * if they were placed inside table cells.
     */
    private function normalizeTableLoops(string $html): string
    {
        if (preg_match_all('/\[loop:([a-zA-Z0-9_]+)\].*?\[\/loop:\1\]/is', $html, $matches, PREG_OFFSET_CAPTURE)) {
            // Process from end to start to avoid offset shifting issues
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $fullMatch = $matches[0][$i][0];
                $startPos = $matches[0][$i][1];
                $loopName = $matches[1][$i][0];

                // Check if it crosses cell boundaries
                if (stripos($fullMatch, '</td>') !== false) {
                    // Find TR start before the loop
                    $trStart = strrpos(substr($html, 0, $startPos), '<tr');

                    // Find TR end after the loop
                    $endPos = $startPos + strlen($fullMatch);
                    $trEndPos = stripos($html, '</tr>', $endPos);

                    if ($trStart !== false && $trEndPos !== false) {
                        $trEnd = $trEndPos + 5; // include </tr>

                        // Extract the whole TR block
                        $trBlock = substr($html, $trStart, $trEnd - $trStart);

                        // Remove paragraph wrappers that ONLY contain the loop tag
                        $trBlockClean = preg_replace('/<p>(?:\s|&nbsp;|<br>)*\[\/?loop:' . $loopName . '\](?:\s|&nbsp;|<br>)*<\/p>/is', '', $trBlock);

                        // Remove the loop tags from INSIDE the TR block and swallow surrounding spaces
                        $trBlockClean = preg_replace('/(?:\s|&nbsp;)*\[\/?loop:' . $loopName . '\](?:\s|&nbsp;)*/is', '', $trBlockClean);

                        // Wrap the clean TR block with the loop tags
                        $newBlock = "[loop:$loopName]\n$trBlockClean\n[/loop:$loopName]";

                        // Replace in original HTML
                        $html = substr_replace($html, $newBlock, $trStart, $trEnd - $trStart);
                    }
                }
            }
        }
        return $html;
    }

    /**
     * Render HTML untuk Surat Scratch / Bebas dengan menyematkan blok tanda tangan resmi di bagian bawah.
     */
    public function renderScratchHtml(\App\Models\Surat $surat): string
    {
        $content = $surat->content['isi_surat'] ?? '';

        // Sematkan blok tanda tangan jika sudah ada pejabat yang menandatangani
        $ttds = $surat->suratTtds;
        if ($ttds->isNotEmpty()) {
            $ttdHtml = '<div style="margin-top: 40px; page-break-inside: avoid; width: 100%;">';
            $ttdHtml .= '<table style="width: 100%; border: none; border-collapse: collapse;"><tr>';

            foreach ($ttds as $ttd) {
                $qrImg = '';
                if ($ttd->qr_code_path) {
                    $fullPath = storage_path('app/private/' . $ttd->qr_code_path);
                    if (!file_exists($fullPath)) {
                        $fullPath = storage_path('app/public/' . $ttd->qr_code_path);
                    }
                    if (file_exists($fullPath)) {
                        $mime = mime_content_type($fullPath) ?: 'image/png';
                        $base64Data = base64_encode(file_get_contents($fullPath));
                        $qrImg = '<img src="data:' . $mime . ';base64,' . $base64Data . '" style="width: 80px; height: 80px; margin-bottom: 5px; display: block;" /><br>';
                    }
                }

                $namaTerang = $ttd->user->nama_lengkap ?? 'Pejabat Berwenang';

                $ttdHtml .= '<td style="vertical-align: top; text-align: left; padding: 10px; width: ' . floor(100 / max(count($ttds), 1)) . '%;">';
                $ttdHtml .= '<p style="margin: 0 0 5px 0; font-size: 10pt;">' . htmlspecialchars($ttd->jabatan_saat_ttd ?? 'Pejabat Berwenang') . '</p>';
                $ttdHtml .= $qrImg;
                $ttdHtml .= '<b style="text-decoration: underline;">' . htmlspecialchars($namaTerang) . '</b><br>';
                $ttdHtml .= '<span style="font-size: 9pt; color: #555;">' . htmlspecialchars($ttd->unit_saat_ttd ?? '') . '</span>';
                $ttdHtml .= '</td>';
            }

            $ttdHtml .= '</tr></table></div>';
            $content .= $ttdHtml;
        }

        return $content;
    }
}
