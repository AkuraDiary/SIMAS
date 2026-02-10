<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Surat;
use App\Services\SuratExportService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class SuratExportController extends Controller
{
    public function __invoke(
        Surat $surat,
        SuratExportService $exportService
    ): BinaryFileResponse {
        Gate::authorize('view', $surat);

        $zipPath = $exportService->export($surat);

        return response()->download($zipPath)->deleteFileAfterSend();
    }
}
