<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SuratExportController;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::middleware('auth')->group(function () {
    Route::get('/media/{media}/file', [MediaController::class, 'file'])
        ->name('media.file');
    
        Route::get('/media/{media}/preview-word', [MediaController::class, 'previewWord'])
        ->name('media.preview.word');

    Route::get('/media/{media}/preview', [MediaController::class, 'preview'])
        ->name('media.preview');

    Route::get('/media/{media}/thumb', [MediaController::class, 'thumb'])
        ->name('media.thumb');

    Route::get('/media/{media}/download', [MediaController::class, 'download'])
        ->name('media.download');

    Route::get('/surat/{surat}/export', SuratExportController::class)
        ->name('surat.export');
});
