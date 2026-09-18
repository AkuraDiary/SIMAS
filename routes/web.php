<?php

use App\Http\Controllers\MediaController;
use App\Http\Controllers\SuratExportController;
use App\Http\Controllers\TtdVerificationController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/pengajuan', \App\Livewire\GuestPengajuan::class)->name('pengajuan');
Route::get('/lacak', \App\Livewire\GuestLacak::class)->name('lacak');


Route::get('/pengajuan', \App\Livewire\GuestPengajuan::class)->name('pengajuan');
Route::get('/lacak', \App\Livewire\GuestLacak::class)->name('lacak');
Route::get('/aktivasi', \App\Livewire\Auth\AktivasiAkun::class)->name('aktivasi');

Route::get('/verify/ttd/{surat}/{user}', [TtdVerificationController::class, 'verify'])
    ->name('verify.ttd');
    
Route::get('/verify/ttd/{surat}/download', [\App\Http\Controllers\TtdVerificationController::class, 'downloadDokumen'])
    ->name('verify.ttd.download');

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
