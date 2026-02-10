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
    Route::get('/media/{media}/thumb', [MediaController::class, 'thumb'])
        ->name('media.thumb');

    Route::get('/media/{media}/download', [MediaController::class, 'download'])
        ->name('media.download');

    Route::get('/surat/{surat}/export', SuratExportController::class)
        ->name('surat.export');
});
