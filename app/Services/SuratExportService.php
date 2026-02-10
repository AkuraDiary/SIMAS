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

        // 1. Surat utama
        $this->generateSuratPdf($surat, $workDir);

        // 2. Lembar disposisi (kalau ada)
        if ($surat->disposisis()->exists()) {
            $this->generateDisposisiPdf($surat, $workDir);
        }

        // 3. Lampiran
        $this->collectLampiran($surat, $workDir . '/Lampiran');

        // 4. Zip
        $zipPath = $baseDir . '/' . $this->buildZipName($surat);
        $this->zipDirectory($workDir, $zipPath);

        // 5. Bersih-bersih
        File::deleteDirectory($workDir);

        return $zipPath;
    }

    /* =======================
     * PDF GENERATORS
     * ======================= */

    protected function generateSuratPdf(Surat $surat, string $dir): void
    {
        $pdf = Pdf::loadView(
            'filament.exports.surat.surat',
            [
                'surat'     => $surat,
                'isArsip'   => $surat->status_surat === 'ARSIP',
            ]
        );

        $pdf->save($dir . '/01_Surat_Utama.pdf');
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

        $pdf->save($dir . '/02_Lembar_Disposisi.pdf');
    }

    /* =======================
     * LAMPIRAN
     * ======================= */

    protected function collectLampiran(Surat $surat, string $lampiranDir): void
    {
        $mediaItems = $surat->getMedia('lampiran-surat');

        if ($mediaItems->isEmpty()) {
            return;
        }

        File::makeDirectory($lampiranDir, 0755, true);

        foreach ($mediaItems as $index => $media) {
            $source = $media->getPath();

            if (! file_exists($source)) {
                continue;
            }

            $filename = sprintf(
                'Lampiran_%02d_%s',
                $index + 1,
                $media->file_name
            );

            File::copy($source, $lampiranDir . '/' . $filename);
        }
    }



    //  UTIL


    protected function buildZipName(Surat $surat): string
    {

        $dateString = $surat->tanggal_kirim;
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
