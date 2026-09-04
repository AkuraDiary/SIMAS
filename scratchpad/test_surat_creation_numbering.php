<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FormatNomorSurat;
use App\Models\NomorSuratLog;
use App\Models\Surat;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\NomorSuratService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "=== TEST: PENOMORAN SURAT PADA PEMBUATAN SURAT ===\n\n";

DB::beginTransaction();

try {
    $service = app(NomorSuratService::class);
    $unit = UnitKerja::first();
    $user = User::first();

    // 1. Buat Format Aktif untuk Unit
    $format = FormatNomorSurat::create([
        'unit_kerja_id' => $unit->id,
        'tipe_surat' => 'INTERNAL',
        'nama_format' => 'Nota Dinas Unit Testing',
        'format_penomoran' => 'ND/{NOMOR}/{KODE_UNIT}/{BULAN_ROMAWI}/{TAHUN}',
        'padding_digit' => 3,
        'nomor_urut_terakhir' => 10,
        'tahun' => (int) date('Y'),
        'is_active' => true,
    ]);

    echo "[CASE 1] Simpan Draft dengan Penomoran Otomatis (nomor_surat NULL saat draft):\n";
    $suratDraft = Surat::create([
        'perihal' => 'Draft Surat Baru Tanpa Nomor Manual',
        'tipe_surat' => 'INTERNAL',
        'status_surat' => 'DRAFT',
        'unit_pengirim_id' => $unit->id,
        'user_pembuat_id' => $user->id,
        'nomor_surat' => null,
    ]);

    assert($suratDraft->nomor_surat === null, "Nomor surat seharusnya NULL saat draft auto");
    echo "  ✓ Draft tersimpan dengan nomor_surat = NULL\n";

    // Simulasikan Pengiriman / Submit
    $resolvedFormat = $service->resolveFormat($suratDraft->unit_pengirim_id, $suratDraft->tipe_surat);
    $nomorAuto = $resolvedFormat->generateNomorSurat($suratDraft);
    $suratDraft->refresh();

    assert($suratDraft->nomor_surat === 'ND/011/REK/IX/2026', "Hasil auto numbering tidak cocok! Got: {$suratDraft->nomor_surat}");
    assert($resolvedFormat->fresh()->nomor_urut_terakhir === 11, "Counter seharusnya naik menjadi 11");
    echo "  ✓ Saat dikirim, nomor surat otomatis terbit: '{$suratDraft->nomor_surat}'\n";
    echo "  ✓ Counter nomor urut naik menjadi 11\n\n";

    echo "[CASE 2] Simpan Surat dengan Penomoran Manual / Backdate Sisipan di Form:\n";
    $tglBackdate = Carbon::now()->subMonths(2);
    $customNomor = "ND/005.B/REK/{$service->toRomanMonth((int) $tglBackdate->format('n'))}/{$tglBackdate->format('Y')}";

    $suratManual = Surat::create([
        'perihal' => 'Surat Backdate Ditetapkan di Form Pembuatan',
        'tipe_surat' => 'INTERNAL',
        'status_surat' => 'DRAFT',
        'unit_pengirim_id' => $unit->id,
        'user_pembuat_id' => $user->id,
        'nomor_surat' => $customNomor,
    ]);

    // Simulasikan afterCreate logic
    $service->assignNomorSurat($suratManual, $format, [
        'tanggal_surat' => $tglBackdate,
        'nomor_surat_preview' => $customNomor,
        'is_manual' => true,
        'increment_counter' => false,
        'alasan_backdate' => 'Surat ketetapan lama untuk kelengkapan berkas akreditasi.',
        'user_id' => $user->id,
    ]);

    $suratManual->refresh();
    assert($suratManual->nomor_surat === $customNomor, "Nomor manual tidak sesuai!");
    assert($format->fresh()->nomor_urut_terakhir === 11, "Counter seharusnya TIDAK naik (tetap 11)!");

    $logManual = NomorSuratLog::where('surat_id', $suratManual->id)->first();
    assert($logManual->is_backdate === true, "is_backdate harusnya true");
    assert($logManual->is_manual === true, "is_manual harusnya true");
    assert($logManual->alasan_backdate !== null, "alasan_backdate harusnya tersimpan");
    echo "  ✓ Surat berhasil dibuat dengan nomor manual: '{$suratManual->nomor_surat}'\n";
    echo "  ✓ Counter nomor urut TIDAK bertambah (tetap 11)\n";
    echo "  ✓ Log audit tersimpan lengkap: is_backdate=true, is_manual=true\n\n";

    echo "=== ALL TEST CASES PASSED SUCCESSFULLY! ===\n";

} catch (\Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    DB::rollBack();
    echo "\n[Database transaction rolled back to keep environment clean.]\n";
}
