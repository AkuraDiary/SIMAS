<?php

namespace App\Http\Controllers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use PhpOffice\PhpWord\IOFactory;


class MediaController extends Controller
{
    public function thumb(Media $media)
    {
        $path = $media->getPath('thumb');
        abort_unless(file_exists($path), 404);

        return response()->file($path,  ['Content-Type' => $media->mime_type]);
    }
    public function previewWord(Media $media)
    {
        $path = $media->getPath();
        abort_unless(file_exists($path), 404);

        $phpWord = IOFactory::load($path);

        $writer = IOFactory::createWriter($phpWord, 'HTML');

        ob_start();
        $writer->save('php://output');
        $html = ob_get_clean();

        return response()->json([
            'html' => $html
        ]);
    }
    public function file(Media $media)
    {
        $path = $media->getPath();
        abort_unless(file_exists($path), 404);
        return response()->file($path,  ['Content-Type' => $media->mime_type]);
    }
    public function preview(Media $media)
    {
        $path = $media->getPath();
        abort_unless(file_exists($path), 404);
        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }
    public function download(Media $media)
    {
        return response()->download(
            $media->getPath(),
            $media->file_name
        );
    }
}
