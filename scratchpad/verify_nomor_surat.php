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

echo "=== STARTING VERIFICATION: PENGELOLAAN NOMOR SURAT & BACKDATE ===\n\n";

DB::beginTransaction();

try {
    $service = app(NomorSuratService::class);

    // 0. Setup Dummy Unit & User
    $unit = UnitKerja::first();
    if (!$unit) {
        $jenisUnit = \App\Models\JenisUnit::firstOrCreate(['nama_jenis' => 'Fakultas']);
        $unit = UnitKerja::create([
            'jenis_unit_id' => $jenisUnit->id,
            'nama_unit' => 'Unit Pengujian Penomoran',
            'singkatan' => 'TEST_UNIT',
            'is_active' => true,
        ]);
    }

    $user = User::first() ?? User::factory()->create();

    // 1. SETUP FORMATS
    // 1a. Global Formats
    $formatGlobalAll = FormatNomorSurat::create([
        'unit_kerja_id' => null,
        'tipe_surat' => 'ALL',
        'nama_format' => 'Format Global Standar',
        'format_penomoran' => '{NOMOR}/UN/{BULAN_ROMAWI}/{TAHUN}',
        'padding_digit' => 3,
        'nomor_urut_terakhir' => 10,
        'tahun' => (int) date('Y'),
        'is_active' => true,
    ]);

    $formatGlobalTerbitan = FormatNomorSurat::create([
        'unit_kerja_id' => null,
        'tipe_surat' => 'TERBITAN',
        'nama_format' => 'Format SK Rektorat',
        'format_penomoran' => '{NOMOR}/SK/UN/{BULAN_ROMAWI}/{TAHUN}',
        'padding_digit' => 4,
        'nomor_urut_terakhir' => 50,
        'tahun' => (int) date('Y'),
        'is_active' => true,
    ]);

    // 1b. Unit Specific Formats
    $formatUnitInternal = FormatNomorSurat::create([
        'unit_kerja_id' => $unit->id,
        'tipe_surat' => 'INTERNAL',
        'nama_format' => 'Nota Dinas Unit Test',
        'format_penomoran' => 'ND/{NOMOR}/{KODE_UNIT}/{BULAN_ROMAWI}/{TAHUN}',
        'padding_digit' => 3,
        'nomor_urut_terakhir' => 0,
        'tahun' => (int) date('Y'),
        'is_active' => true,
    ]);

    echo "[TEST 1] FORMAT RESOLUTION HIERARCHY & FALLBACK:\n";

    // Resolusi Unit + INTERNAL -> Harus ambil format unit
    $res1 = $service->resolveFormat($unit->id, 'INTERNAL');
    assert($res1->id === $formatUnitInternal->id, "Gagal resolve Unit INTERNAL");
    echo "  ✓ Unit + INTERNAL resolved to format: '{$res1->nama_format}' (ID: {$res1->id})\n";

    // Resolusi Unit + TERBITAN -> Unit belum punya, fallback ke Global TERBITAN
    $res2 = $service->resolveFormat($unit->id, 'TERBITAN');
    assert($res2->id === $formatGlobalTerbitan->id, "Gagal fallback ke Global TERBITAN");
    echo "  ✓ Unit + TERBITAN fallback to: '{$res2->nama_format}' (ID: {$res2->id})\n";

    // Resolusi Unit + PENGAJUAN -> Unit & Global belum punya spesifik, fallback ke Global ALL
    $res3 = $service->resolveFormat($unit->id, 'PENGAJUAN');
    assert($res3->id === $formatGlobalAll->id, "Gagal fallback ke Global ALL");
    echo "  ✓ Unit + PENGAJUAN fallback to: '{$res3->nama_format}' (ID: {$res3->id})\n\n";

    // 2. NORMAL SEQUENTIAL NUMBERING
    echo "[TEST 2] NORMAL SEQUENTIAL NUMBERING:\n";
    $suratNormal = Surat::create([
        'perihal' => 'Surat Undangan Rapat Internal',
        'tipe_surat' => 'INTERNAL',
        'status_surat' => 'DRAFT',
        'unit_pengirim_id' => $unit->id,
        'user_pembuat_id' => $user->id,
    ]);

    $initialCounter = $formatUnitInternal->nomor_urut_terakhir;
    $nomorResult = $formatUnitInternal->generateNomorSurat($suratNormal);

    $formatUnitInternal->refresh();
    assert($formatUnitInternal->nomor_urut_terakhir === $initialCounter + 1, "Counter tidak bertambah!");
    assert($suratNormal->fresh()->nomor_surat === $nomorResult, "Nomor surat tidak tersimpan di database!");

    $logNormal = NomorSuratLog::where('surat_id', $suratNormal->id)->latest('id')->first();
    assert($logNormal !== null, "Log penomoran tidak ditemukan!");
    assert($logNormal->is_backdate === false, "is_backdate harusnya false");
    assert($logNormal->is_manual === false, "is_manual harusnya false");
    echo "  ✓ Generated: '{$nomorResult}'\n";
    echo "  ✓ Counter updated: {$initialCounter} -> {$formatUnitInternal->nomor_urut_terakhir}\n";
    echo "  ✓ Stored on surats.nomor_surat: '{$suratNormal->fresh()->nomor_surat}'\n";
    echo "  ✓ Log audit: is_backdate=false, is_manual=false\n\n";

    // 3. BACKDATE SEQUENTIAL NUMBERING
    echo "[TEST 3] BACKDATE SEQUENTIAL NUMBERING:\n";
    $pastDate = Carbon::now()->subMonths(3); // 3 bulan lalu
    $suratBackdate = Surat::create([
        'perihal' => 'Surat Koordinasi Tiga Bulan Lalu',
        'tipe_surat' => 'INTERNAL',
        'status_surat' => 'DRAFT',
        'unit_pengirim_id' => $unit->id,
        'user_pembuat_id' => $user->id,
    ]);

    $nomorBackdate = $formatUnitInternal->generateNomorSurat($suratBackdate, [
        'tanggal_surat' => $pastDate->toDateString(),
        'alasan_backdate' => 'Surat fisik sudah ditandatangani 3 bulan lalu, proses digitalisasi arsip.',
    ]);

    $formatUnitInternal->refresh();
    $logBackdate = NomorSuratLog::where('surat_id', $suratBackdate->id)->latest('id')->first();

    $expectedRoman = $service->toRomanMonth((int) $pastDate->format('n'));
    assert(str_contains($nomorBackdate, "/{$expectedRoman}/"), "Bulan romawi tidak sesuai tanggal backdate!");
    assert($logBackdate->is_backdate === true, "is_backdate harusnya true!");
    assert($logBackdate->alasan_backdate !== null, "alasan_backdate tidak tersimpan!");
    echo "  ✓ Generated Backdate: '{$nomorBackdate}'\n";
    echo "  ✓ Detected Roman Month for month {$pastDate->format('n')}: '{$expectedRoman}'\n";
    echo "  ✓ Counter updated: {$formatUnitInternal->nomor_urut_terakhir}\n";
    echo "  ✓ Log audit: is_backdate=true, alasan='{$logBackdate->alasan_backdate}'\n\n";

    // 4. BACKDATE SISIPAN / KUSTOM SEBAGIAN (NO COUNTER INCREMENT)
    echo "[TEST 4] BACKDATE SISIPAN / KUSTOM SEBAGIAN (USER SCENARIO):\n";
    $currentCounterBefore = $formatUnitInternal->nomor_urut_terakhir;

    $suratSisipan = Surat::create([
        'perihal' => 'Surat Sisipan Terlewat Masa Lalu',
        'tipe_surat' => 'INTERNAL',
        'status_surat' => 'DRAFT',
        'unit_pengirim_id' => $unit->id,
        'user_pembuat_id' => $user->id,
    ]);

    $customNomorString = "ND/001.A/{$unit->singkatan}.KHUSUS/{$expectedRoman}/{$pastDate->format('Y')}";
    $nomorSisipan = $formatUnitInternal->generateNomorSurat($suratSisipan, [
        'tanggal_surat' => $pastDate->toDateString(),
        'is_manual' => true,
        'increment_counter' => false, // Counter TIDAK dinaikkan
        'nomor_surat_preview' => $customNomorString,
        'alasan_backdate' => 'Penyisipan nomor surat 001.A untuk audit akreditasi tahun berjalan.',
    ]);

    $formatUnitInternal->refresh();
    assert($formatUnitInternal->nomor_urut_terakhir === $currentCounterBefore, "Counter seharusnya TIDAK bertambah pada mode sisipan!");
    assert($suratSisipan->fresh()->nomor_surat === $customNomorString, "Nomor kustom tidak tersimpan!");

    $logSisipan = NomorSuratLog::where('surat_id', $suratSisipan->id)->latest('id')->first();
    assert($logSisipan->is_manual === true, "is_manual harusnya true");
    assert($logSisipan->is_backdate === true, "is_backdate harusnya true");
    echo "  ✓ Generated Sisipan: '{$nomorSisipan}'\n";
    echo "  ✓ Counter UNCHANGED: {$currentCounterBefore} == {$formatUnitInternal->nomor_urut_terakhir} (Urutan hari ini aman!)\n";
    echo "  ✓ Log audit: is_manual=true, is_backdate=true\n\n";

    // 5. SEARCH CAPABILITY
    echo "[TEST 5] DATABASE SEARCH BY NOMOR SURAT:\n";
    $found = Surat::where('nomor_surat', 'like', '%001.A%')->first();
    assert($found !== null && $found->id === $suratSisipan->id, "Pencarian surat via nomor_surat gagal!");
    echo "  ✓ Successfully found Surat ID {$found->id} using query on 'nomor_surat' column: '{$found->nomor_surat}'\n\n";

    echo "=== ALL VERIFICATION TESTS PASSED SUCCESSFULLY! ===\n";

} catch (\Throwable $e) {
    echo "❌ ERROR OCCURRED: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    // Rollback changes to keep database clean
    DB::rollBack();
    echo "\n[Database transaction rolled back to keep environment clean.]\n";
}
