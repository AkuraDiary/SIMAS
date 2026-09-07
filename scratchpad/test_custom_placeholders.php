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

echo "=== MEMULAI TEST PLACEHOLDER KUSTOM & OPSI PENOMORAN ===\n\n";

$service = app(NomorSuratService::class);
$unit = UnitKerja::first() ?? UnitKerja::create(['nama_unit' => 'Fakultas Teknik', 'singkatan' => 'FT']);
$user = User::first();

// TEST 1: extractCustomTags
echo "TEST 1: extractCustomTags\n";
$pattern1 = '{NOMOR}/{KODE_KLASIFIKASI}/{KODE_UNIT}/{BULAN_ROMAWI}/{TAHUN}';
$tags1 = $service->extractCustomTags($pattern1);
echo "  Pola: {$pattern1}\n";
echo "  Hasil ekstraksi: " . json_encode($tags1) . "\n";
assert(count($tags1) === 1 && $tags1[0] === 'KODE_KLASIFIKASI', "Test 1 Gagal: Harus mengekstrak KODE_KLASIFIKASI");
echo "  -> OK! [LULUS]\n\n";

// TEST 2: Multiple custom tags
echo "TEST 2: Multiple custom tags\n";
$pattern2 = '{NOMOR}/SK-{JENIS_SK}/{KODE_KLASIFIKASI}/{KODE_UNIT}/{TAHUN}';
$tags2 = $service->extractCustomTags($pattern2);
echo "  Pola: {$pattern2}\n";
echo "  Hasil ekstraksi: " . json_encode($tags2) . "\n";
assert(count($tags2) === 2 && in_array('JENIS_SK', $tags2) && in_array('KODE_KLASIFIKASI', $tags2), "Test 2 Gagal: Harus mengekstrak JENIS_SK dan KODE_KLASIFIKASI");
echo "  -> OK! [LULUS]\n\n";

// TEST 3: Opsi 1 - Template rendering & previewNomor dengan Custom Tags
echo "TEST 3: Opsi 1 - Dynamic Custom Tag Replacement via previewNomor\n";
$formatCustom = FormatNomorSurat::create([
    'unit_kerja_id' => $unit->id,
    'tipe_surat' => 'INTERNAL',
    'nama_format' => 'Format FT Berklasifikasi',
    'format_penomoran' => '{NOMOR}/{KODE_KLASIFIKASI}/{KODE_UNIT}/{BULAN_ROMAWI}/{TAHUN}',
    'padding_digit' => 3,
    'nomor_urut_terakhir' => 10,
    'tahun' => (int) date('Y'),
    'is_active' => true,
]);

$preview = $service->previewNomor(
    $formatCustom,
    Carbon::create(2026, 9, 4),
    null,
    $unit,
    'INTERNAL',
    ['KODE_KLASIFIKASI' => 'PP.01']
);
echo "  Preview Nomor: {$preview}\n";
assert($preview === "011/PP.01/{$unit->singkatan}/IX/2026", "Test 3 Gagal: Preview nomor salah: {$preview}");
echo "  -> OK! [LULUS]\n\n";

// TEST 4: Opsi 1 - assignNomorSurat dengan Custom Tags & content persistence
echo "TEST 4: Opsi 1 - assignNomorSurat dengan penyimpanan tag ke surat->content\n";
$surat1 = Surat::create([
    'unit_pengirim_id' => $unit->id,
    'user_pembuat_id' => $user?->id,
    'tipe_surat' => 'INTERNAL',
    'perihal' => 'Surat Tugas Mengajar Semester Ganjil',
    'status_surat' => 'DRAFT',
    'content' => ['isi_surat' => 'Tugas mengajar dosen...'],
]);

$nomorResmi1 = $service->assignNomorSurat($surat1, $formatCustom, [
    'tanggal_surat' => '2026-09-04',
    'nomor_surat_preview' => $preview,
    'is_manual' => false,
    'increment_counter' => true,
    'custom_tags' => ['KODE_KLASIFIKASI' => 'PP.01'],
    'user_id' => $user?->id,
]);

$surat1->refresh();
$formatCustom->refresh();
echo "  Nomor Resmi: {$nomorResmi1}\n";
echo "  Surat nomor_surat di DB: {$surat1->nomor_surat}\n";
echo "  Surat content tags: " . json_encode($surat1->content['nomor_surat_tags'] ?? null) . "\n";
echo "  Format counter terakhir: {$formatCustom->nomor_urut_terakhir}\n";
assert($surat1->nomor_surat === "011/PP.01/{$unit->singkatan}/IX/2026", "Test 4 Gagal: nomor_surat tidak tersimpan dengan benar");
assert(($surat1->content['nomor_surat_tags']['KODE_KLASIFIKASI'] ?? null) === 'PP.01', "Test 4 Gagal: content['nomor_surat_tags'] tidak tersimpan");
assert($formatCustom->nomor_urut_terakhir === 11, "Test 4 Gagal: counter harus 11");
echo "  -> OK! [LULUS]\n\n";

// TEST 5: Opsi 2 - Sisipan Manual (045.A) dengan Counter Freeze
echo "TEST 5: Opsi 2 - Sisipan Manual (045.A) & Counter Freeze\n";
$previewSisipan = $service->previewNomor(
    $formatCustom,
    Carbon::create(2026, 8, 15),
    '010.A',
    $unit,
    'INTERNAL',
    ['KODE_KLASIFIKASI' => 'KP.02']
);
echo "  Preview Sisipan: {$previewSisipan}\n";
assert($previewSisipan === "010.A/KP.02/{$unit->singkatan}/VIII/2026", "Test 5 Gagal: Preview sisipan salah: {$previewSisipan}");

$surat2 = Surat::create([
    'unit_pengirim_id' => $unit->id,
    'user_pembuat_id' => $user?->id,
    'tipe_surat' => 'INTERNAL',
    'perihal' => 'Surat Sisipan Lampau',
    'status_surat' => 'DRAFT',
]);

$nomorResmi2 = $service->assignNomorSurat($surat2, $formatCustom, [
    'tanggal_surat' => '2026-08-15',
    'nomor_surat_preview' => $previewSisipan,
    'is_manual' => true,
    'increment_counter' => false,
    'alasan_backdate' => 'Surat sisipan nomor lampau',
    'custom_tags' => ['KODE_KLASIFIKASI' => 'KP.02'],
    'user_id' => $user?->id,
]);

$surat2->refresh();
$formatCustom->refresh();
$log2 = NomorSuratLog::where('surat_id', $surat2->id)->first();

echo "  Nomor Sisipan Resmi: {$nomorResmi2}\n";
echo "  Format counter setelah sisipan: {$formatCustom->nomor_urut_terakhir}\n";
assert($formatCustom->nomor_urut_terakhir === 11, "Test 5 Gagal: Counter seharusnya tetap 11 (tidak bertambah)");
assert($log2->is_manual == true, "Test 5 Gagal: log is_manual harus true");
assert($log2->is_backdate == true, "Test 5 Gagal: log is_backdate harus true");
echo "  -> OK! [LULUS]\n\n";

// TEST 6: Opsi 2 - Full Freeform Override
echo "TEST 6: Opsi 2 - Full Freeform Override\n";
$surat3 = Surat::create([
    'unit_pengirim_id' => $unit->id,
    'user_pembuat_id' => $user?->id,
    'tipe_surat' => 'INTERNAL',
    'perihal' => 'Surat Format Khusus Rektorat',
    'status_surat' => 'DRAFT',
]);

$customFreeform = '001/REK-KHUSUS/MOU/2026';
$nomorResmi3 = $service->assignNomorSurat($surat3, $formatCustom, [
    'tanggal_surat' => '2026-09-04',
    'nomor_surat_preview' => $customFreeform,
    'is_manual' => true,
    'increment_counter' => false,
    'user_id' => $user?->id,
]);

$surat3->refresh();
$formatCustom->refresh();
echo "  Nomor Freeform: {$surat3->nomor_surat}\n";
assert($surat3->nomor_surat === $customFreeform, "Test 6 Gagal: Freeform override harus sama persis");
assert($formatCustom->nomor_urut_terakhir === 11, "Test 6 Gagal: Counter harus tetap 11");
echo "  -> OK! [LULUS]\n\n";

// Cleanup test records
$surat1->forceDelete();
$surat2->forceDelete();
$surat3->forceDelete();
$formatCustom->forceDelete();

echo "=== SEMUA 6 PENGUJIAN KUSTOMISASI & OPSI PENOMORAN BERHASIL LULUS 100%! ===\n";
