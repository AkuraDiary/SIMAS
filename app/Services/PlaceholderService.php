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
            $unplacedTtdsByPage = [];
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
                if (abs($x) > 700) $x = 0;
                if (abs($y) > 1000) $y = 0;

                $savedPage = (int) ($data[$pKey . '_halaman'] ?? ($surat->content[$pKey . '_halaman'] ?? $ttd->halaman ?? 0));

                $canDrag = !$forPdf && $this->canUserAdjustSignature($surat, $template, $pKey);
                $lockAttr = $canDrag ? '' : ' data-locked="true"';
                $cursor = $canDrag ? 'grab' : 'default';
                $resizeHandle = $canDrag ? '<div class="signature-resize-handle" title="Tarik untuk mengubah ukuran"></div>' : '';

                $style = "text-align: left; display: inline-block; cursor: {$cursor}; position: relative; left: {$x}px; top: {$y}px; width: {$widthVal}px;";

                $ttdVisual = '<div class="draggable-signature" data-key="' . $pKey . '" data-page="' . max(1, $savedPage) . '"' . $lockAttr . ' style="' . $style . '">' .
                    $qrImg .
                    $extraTtdImg .
                    $nameHtml .
                    $resizeHandle .
                    '</div>';

                $hasTagInHtml = (bool) preg_match('/\{\{\s*' . preg_quote($pKey, '/') . '\s*\}\}/', $html);

                // Jika user sudah pernah memindahkan TTD lintas halaman secara eksplisit (atau tag tidak ada di HTML),
                // tempatkan ke anchor halaman tujuan dan kosongkan tag lama agar tidak duplikat
                if ($savedPage > 0 || !$hasTagInHtml) {
                    $data[$pKey] = '';
                    $targetPageNum = $savedPage > 0 ? $savedPage : 999; // 999 = fallback halaman terakhir
                    $unplacedTtdsByPage[$targetPageNum][$pKey] = $ttdVisual;
                } else {
                    $data[$pKey] = $ttdVisual;
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
                $isRefocatedOrHidden = ($surat && $surat->suratTtds->where('placeholder_key', $key)->isNotEmpty() && $value === '');
                if ($value !== '' || $isRefocatedOrHidden) {
                    $html = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', str_replace('$', '\$', (string) $value), $html);
                }
            }
        }

        // Handle signature vars specifically (TTD Pemohon)
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
                if (abs($x) > 700) $x = 0;
                if (abs($y) > 1000) $y = 0;

                $savedPage = (int) ($data[$key . '_halaman'] ?? ($surat ? ($surat->content[$key . '_halaman'] ?? 0) : 0));

                $canDrag = !$forPdf && $this->canUserAdjustSignature($surat, $template, $key);
                $lockAttr = $canDrag ? '' : ' data-locked="true"';
                $cursor = $canDrag ? 'grab' : 'default';
                $resizeHandle = $canDrag ? '<div class="signature-resize-handle" title="Tarik untuk mengubah ukuran"></div>' : '';

                $style = "display: inline-block; cursor: {$cursor}; position: relative; left: {$x}px; top: {$y}px; width: {$widthVal}px;";
                $wrapper = '<div class="draggable-signature" data-key="' . $key . '" data-page="' . max(1, $savedPage) . '"' . $lockAttr . ' style="' . $style . '">' . $val . $resizeHandle . '</div>';

                if ($savedPage > 0) {
                    $html = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', '', $html);
                    $unplacedTtdsByPage[$savedPage][$key] = $wrapper;
                } else {
                    $html = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', str_replace('$', '\$', $wrapper), $html);
                }
            }
        }

        // Clean up remaining un-filled placeholders
        $html = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($data, $surat, $template, $forPdf) {
            $key = $m[1];

            if ($forPdf && \Illuminate\Support\Str::startsWith(strtolower($key), 'ttd_')) {
                return '';
            }

            if (\Illuminate\Support\Str::startsWith(strtolower($key), 'ttd_')) {
                $x = (int) ($data[$key . '_posisi_x'] ?? ($surat ? ($surat->content[$key . '_posisi_x'] ?? null) : null) ?? 0);
                $y = (int) ($data[$key . '_posisi_y'] ?? ($surat ? ($surat->content[$key . '_posisi_y'] ?? null) : null) ?? 0);
                if (abs($x) > 700) $x = 0;
                if (abs($y) > 1000) $y = 0;

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

        // Pecah dokumen berdasarkan <!-- pagebreak --> menjadi array segmen halaman
        $pageSegments = preg_split('/(?:<p>\s*)?<!--\s*pagebreak\s*-->(?:\s*<\/p>)?/i', $html) ?: [$html];
        $totalPages = max(1, count($pageSegments));

        // Distribusikan TTD yang belum terpasang / dipindah lintas halaman ke segmen halaman yang sesuai
        foreach ($unplacedTtdsByPage ?? [] as $requestedPage => $ttdList) {
            $pageIdx = ($requestedPage === 999)
                ? ($totalPages - 1)
                : max(0, min($totalPages - 1, ((int) $requestedPage) - 1));

            if (!isset($pageSegments[$pageIdx])) {
                $pageIdx = $totalPages - 1;
            }
            $pageSegments[$pageIdx] .= '<!-- TTD_ANCHOR:' . implode('&nbsp;&nbsp;&nbsp;', $ttdList) . ' -->';
        }

        // Ambil aset Kop, Footer, dan Background
        $kopUri = $template->getMediaDataUri('kop_surat');
        $footerUri = $template->getMediaDataUri('footer_surat');
        $bgUri = $template->getMediaDataUri('background_surat');
        $kopPosisi = $template->kop_posisi ?? 'AWAL_SAJA';
        $footerPosisi = $template->footer_posisi ?? 'AKHIR_SAJA';
        $bgStyle = $bgUri ? "background-image: url('{$bgUri}'); background-size: 100% 100%; background-repeat: no-repeat;" : '';

        // 1. Jika dirender untuk PDF (DomPDF): satukan kembali dengan <div class="page-break"></div>
        if ($forPdf) {
            foreach ($pageSegments as $idx => $seg) {
                $anchorContent = '';
                $seg = preg_replace_callback('/<!-- TTD_ANCHOR:(.*?) -->/s', function ($m) use (&$anchorContent) {
                    $anchorContent .= $m[1];
                    return '';
                }, $seg);
                $pageSegments[$idx] = $seg . '<div class="surat-page-ttd-anchor" style="margin-top: 24px; text-align: right; min-height: 1px;">' . $anchorContent . '</div>';
            }

            $joinedPdfHtml = implode('<div class="page-break"></div>', $pageSegments);
            $pdfCss = '<style>
                .docx-preview-wrapper { position: relative; }
                .docx-preview-wrapper table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
                .page-break {
                    page-break-after: always;
                    break-after: page;
                    border: none !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    height: 0 !important;
                    background: transparent !important;
                }
            </style>';

            return '<div class="docx-preview-wrapper">' . $pdfCss . $joinedPdfHtml . '</div>';
        }

        // 2. Jika dirender untuk Browser Live Preview: bungkus setiap halaman sebagai lembar kertas A4 mandiri (.surat-page-sheet)
        $sheetsHtml = '';
        foreach ($pageSegments as $idx => $seg) {
            $pageNum = $idx + 1;
            $isFirstPage = ($pageNum === 1);
            $isLastPage = ($pageNum === $totalPages);

            $anchorContent = '';
            $seg = preg_replace_callback('/<!-- TTD_ANCHOR:(.*?) -->/s', function ($m) use (&$anchorContent) {
                $anchorContent .= $m[1];
                return '';
            }, $seg);

            $showKop = $kopUri && ($isFirstPage || $kopPosisi === 'SEMUA_HALAMAN');
            $showFooter = $footerUri && ($isLastPage || $footerPosisi === 'SEMUA_HALAMAN');

            $kopHtml = $showKop
                ? '<div class="surat-kop-preview" style="margin-bottom: 16px;"><img src="' . $kopUri . '" style="width: 100%; height: auto; display: block;" pointer-events="none" /></div>'
                : '';
            $footerHtml = $showFooter
                ? '<div class="surat-footer-preview" style="margin-top: 28px;"><img src="' . $footerUri . '" style="width: 100%; height: auto; display: block;" pointer-events="none" /></div>'
                : '';

            $pageBadge = $totalPages > 1
                ? '<div class="surat-page-badge">HALAMAN ' . $pageNum . ' / ' . $totalPages . '</div>'
                : '<div class="surat-page-badge">PREVIEW</div>';

            $sheetsHtml .= '<div class="surat-page-sheet" data-page="' . $pageNum . '" style="' . $bgStyle . '">' .
                $pageBadge .
                '<div class="surat-page-body">' .
                    $kopHtml .
                    $seg .
                    '<div class="surat-page-ttd-anchor" style="margin-top: 24px; text-align: right; min-height: 2px;">' . $anchorContent . '</div>' .
                '</div>' .
                $footerHtml .
            '</div>';
        }

        $previewCss = '<style>
            .docx-preview-wrapper { position: relative; }
            .docx-preview-wrapper table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
            .surat-page-sheet {
                position: relative;
                width: 100%;
                min-height: 840px;
                background-color: #ffffff;
                color: #000000;
                padding: 2.5rem;
                margin-bottom: 2rem;
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
                border: 1px solid rgba(0,0,0,0.08);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                transition: box-shadow 0.15s ease, border-color 0.15s ease;
            }
            .surat-page-sheet:last-child {
                margin-bottom: 0;
            }
            .surat-page-sheet.is-drop-target {
                border-color: #2563eb;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25), 0 10px 15px -3px rgba(0,0,0,0.1);
            }
            .surat-page-body {
                flex: 1 1 auto;
                position: relative;
            }
            .surat-page-badge {
                position: absolute;
                top: 0;
                right: 0;
                background: #2563eb;
                color: #ffffff;
                font-size: 11px;
                font-weight: 700;
                padding: 4px 12px;
                border-bottom-left-radius: 8px;
                z-index: 10;
                letter-spacing: 0.04em;
            }
            @media print {
                .signature-resize-handle, .surat-page-badge { display: none !important; }
            }
        </style>';

        return '<div class="docx-preview-wrapper">' . $previewCss . $sheetsHtml . '</div>';
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
