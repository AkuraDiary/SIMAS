<?php

namespace App\Services;

use App\Models\Surat;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTime;
use ZipArchive;

use function Symfony\Component\Clock\now;

class SuratExportService
{
    public function export(Surat $surat): string
    {
        $baseDir = storage_path('app/tmp/exports');
        File::ensureDirectoryExists($baseDir);

        $workDir = $baseDir . '/' . Str::uuid();
        File::makeDirectory($workDir, 0755, true);

        // 1. Surat utama (Naskah dokumen asli murni)
        $this->generateSuratPdf($surat, $workDir);

        // 2. Lembar Kendali & Persetujuan (Metadata + Riwayat Alur + TTD / QR Code)
        $this->generateLembarKendaliPdf($surat, $workDir);

        // 3. Lembar disposisi (kalau ada)
        if ($surat->disposisis()->exists()) {
            $this->generateDisposisiPdf($surat, $workDir);
        }

        // 4. Lampiran berkas resmi
        $this->collectLampiran($surat, $workDir . '/Lampiran');

        // 5. Zip
        $zipPath = $baseDir . '/' . $this->buildZipName($surat);
        $this->zipDirectory($workDir, $zipPath);

        // 6. Bersih-bersih folder temporary
        File::deleteDirectory($workDir);

        return $zipPath;
    }

    /* =======================
     * PDF GENERATORS
     * ======================= */

    /**
     * Engine Terpusat: Generate naskah resmi PDF murni (Template / Scratch)
     * dan lampirkan ke koleksi media 'dokumen-final' milik model Surat.
     */
    public function generateAndAttachDokumenFinal(Surat $surat, ?string $nomor = null): void
    {
        $renderedHtml = ($surat->template_id && $surat->template)
            ? app(\App\Services\PlaceholderService::class)->renderHtml($surat->template, $surat->content ?? [], $surat)
            : app(\App\Services\PlaceholderService::class)->renderScratchHtml($surat);

        $suratHtml = view('filament.exports.surat.surat', [
            'surat'        => $surat,
            'isArsip'      => $surat->status_surat === 'ARSIP',
            'renderedHtml' => $renderedHtml,
        ])->render();

        $pdf = Pdf::loadHTML($suratHtml)->setPaper('A4', 'portrait');
        $pdfContent = $pdf->output();

        $nomorFinal = $nomor ?? $surat->nomor_surat;
        $safeNomor = !empty($nomorFinal)
            ? str_replace(['/', '\\'], '_', $nomorFinal)
            : 'Disahkan_' . $surat->id;
        $fileName = 'Surat_Utama_' . $safeNomor . '.pdf';

        $surat->clearMediaCollection('dokumen-final');
        $surat->addMediaFromString($pdfContent)
            ->usingName('Dokumen Final Resmi')
            ->usingFileName($fileName)
            ->toMediaCollection('dokumen-final');
    }

       protected function generateSuratPdf(Surat $surat, string $dir): void
    {
        // Jika belum memiliki file dokumen-final resmi, generate sekarang
        $dokumenFinal = $surat->getFirstMedia('dokumen-final');
        if (!$dokumenFinal || !file_exists($dokumenFinal->getPath())) {
            $this->generateAndAttachDokumenFinal($surat);
            $dokumenFinal = $surat->fresh()->getFirstMedia('dokumen-final');
        }

        if ($dokumenFinal && file_exists($dokumenFinal->getPath())) {
            copy($dokumenFinal->getPath(), $dir . '/01_Surat_Utama.pdf');
        }
    }
    protected function generateLembarKendaliPdf(Surat $surat, string $dir): void
    {
        // Format TTD / QR Code pejabat ke Data URI Base64 agar DomPDF dapat merender langsung
        $ttdsWithImages = $surat->suratTtds()->with('user.pegawai')->get()->map(function ($ttd) {
            $base64Img = null;
            if ($ttd->qr_code_path) {
                $fullPath = storage_path('app/private/' . $ttd->qr_code_path);
                if (!file_exists($fullPath)) {
                    $fullPath = storage_path('app/public/' . $ttd->qr_code_path);
                }
                if (file_exists($fullPath)) {
                    $mime = mime_content_type($fullPath) ?: 'image/png';
                    $base64Img = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }
            return [
                'model'      => $ttd,
                'image_data' => $base64Img,
            ];
        });

        $pdf = Pdf::loadView(
            'filament.exports.surat.lembar-kendali-persetujuan',
            [
                'surat'          => $surat,
                'riwayats'       => $surat->riwayats()->with(['unitAsal', 'unitTujuan', 'userAktor'])->orderBy('id')->get(),
                'ttdsWithImages' => $ttdsWithImages,
                'lampirans'      => $surat->getSemuaLampiran(),
            ]
        )->setPaper('A4', 'portrait');

        $pdf->save($dir . '/02_Lembar_Kendali_dan_Persetujuan.pdf');
    }
    protected function generateDisposisiPdf(Surat $surat, string $dir): void
    {
        $pdf = Pdf::loadView(

            'filament.exports.surat.lembar-disposisi',
            [
                'surat'      => $surat,
                'disposisis' => $surat->disposisis,
            ]
        );

        $pdf->save($dir . '/03_Lembar_Disposisi.pdf');
    }

    /* =======================
     * LAMPIRAN
     * ======================= */

    protected function collectLampiran(Surat $surat, string $lampiranDir): void
    {
        $allLampirans = $surat->getSemuaLampiran();
        if ($allLampirans->isEmpty()) {
            return;
        }

        File::makeDirectory($lampiranDir, 0755, true);
        $counter = 1;

        foreach ($allLampirans as $media) {
            $source = $media->getPath();
            if (!file_exists($source)) continue;

            $filename = sprintf('Lampiran_%02d_%s', $counter++, $media->file_name);
            File::copy($source, $lampiranDir . '/' . $filename);
        }
    }
    // protected function collectLampiran(Surat $surat, string $lampiranDir): void
    // {
    //     $hasOwnMedia = $surat->getMedia('lampiran-surat')->isNotEmpty();
    //     $hasParentMedia = $surat->terbitan_for_surat_id && $surat->terbitanForSurat && $surat->terbitanForSurat->getMedia('lampiran-surat')->isNotEmpty();

    //     if (!$hasOwnMedia && !$hasParentMedia) {
    //         return;
    //     }

    //     File::makeDirectory($lampiranDir, 0755, true);
    //     $counter = 1;

    //     // 1. Lampiran dari Surat ini sendiri
    //     foreach ($surat->getMedia('lampiran-surat') as $media) {
    //         $source = $media->getPath();
    //         if (!file_exists($source)) continue;

    //         $filename = sprintf('Lampiran_%02d_%s', $counter++, $media->file_name);
    //         File::copy($source, $lampiranDir . '/' . $filename);
    //     }

    //     // 2. Lampiran dari Surat Pengajuan Pemohon (jika ini surat terbitan rujukan)
    //     if ($hasParentMedia) {
    //         foreach ($surat->terbitanForSurat->getMedia('lampiran-surat') as $media) {
    //             $source = $media->getPath();
    //             if (!file_exists($source)) continue;

    //             $filename = sprintf('Lampiran_Pengajuan_%02d_%s', $counter++, $media->file_name);
    //             File::copy($source, $lampiranDir . '/' . $filename);
    //         }
    //     }
    // }


    /**
     * Download draf surat & lembar metadata dalam format PDF berdasarkan state form pengajuan.
     */
    public function downloadDraftPdf(array $state): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Ekstrak nama lampiran dari upload temporary jika belum ada
        if (empty($state['lampiran_names']) && !empty($state['lampiran'])) {
            $lampiranNames = [];
            foreach ($state['lampiran'] as $file) {
                if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                    $lampiranNames[] = $file->getClientOriginalName();
                } elseif (is_string($file)) {
                    $lampiranNames[] = basename($file);
                }
            }
            $state['lampiran_names'] = $lampiranNames;
        }

        $isScratch = ($state['template_id'] ?? '') === 'scratch';
        $user = auth()->user();
        $pengirim = $state['pengirim_nama'] ?? $user?->nama_lengkap ?? 'Pemohon';
        $tujuan = '-';
        $perihal = $state['perihal'] ?? '-';
        $renderedHtml = '';

        if ($isScratch) {
            $unitId = $state['unit_tujuan'] ?? null;
            if ($unitId) {
                $tujuan = \App\Models\UnitKerja::find($unitId)?->nama_unit ?? '-';
            }
            $renderedHtml = $state['content_scratch'] ?? '';
        } else {
            $templateId = $state['template_id'] ?? null;
            if ($templateId) {
                $template = \App\Models\Template::with('entryPointUnit')->find($templateId);
                $perihal = $state['perihal'] ?? ('Pengajuan ' . ($template?->nama_template ?? ''));
                $tujuan = $template?->entryPointUnit?->nama_unit ?? 'Sesuai Template';

                $renderedHtml = app(\App\Services\PlaceholderService::class)->renderHtml($template, $state['content'] ?? []);
            }
        }

        // Mock Surat untuk rendering view surat kedinasan
        $mockSurat = new \App\Models\Surat([
            'nomor_surat' => 'DRAF',
            'nomor_agenda' => '-',
            'perihal' => $perihal,
            'tanggal_kirim' => now(),
        ]);
        $mockSurat->setRelation('unitPengirim', new \App\Models\UnitKerja(['nama_unit' => $tujuan]));
        $mockSurat->setRelation('pembuat', new \App\Models\User(['nama_lengkap' => $pengirim]));

        // Render HTML surat utama
        $suratHtml = view('filament.exports.surat.surat', [
            'surat' => $mockSurat,
            'isArsip' => false,
            'renderedHtml' => $renderedHtml
        ])->render();

        // Render Lembar Kendali / Metadata
        $metadataHtml = view('filament.exports.surat.metadata', [
            'state' => $state,
            'tujuan' => $tujuan,
        ])->render();

        $combinedHtml = str_replace('</body>', '<div style="page-break-before: always;"></div>' . $metadataHtml . '</body>', $suratHtml);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($combinedHtml);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Draf_Pengajuan_' . date('Ymd_His') . '.pdf');
    }

    //  UTIL


    protected function buildZipName(Surat $surat): string
    {

        $dateString = $surat->created_at;
        $dateObject =  $dateString ? new DateTime($dateString) : now();

        $formattedDate = $dateObject->format('Y-m-d');
        return sprintf(
            '%s_%s.zip',
            $formattedDate,
            Str::slug($surat->perihal)
        );
    }

    protected function zipDirectory(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = File::allFiles($sourceDir);

        foreach ($files as $file) {
            $relativePath = Str::after($file->getPathname(), $sourceDir . '/');
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();
    }
}
