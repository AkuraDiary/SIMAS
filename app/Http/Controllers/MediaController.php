<?php

namespace App\Http\Controllers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function thumb(Media $media)
    {

        $path = $media->getPath('thumb');

        abort_unless(file_exists($path), 404);

        return response()->file($path,  ['Content-Type' => $media->mime_type]);
    }

    public function download(Media $media)
    {


        return response()->download(
            $media->getPath(),
            $media->file_name
        );
    }
}
